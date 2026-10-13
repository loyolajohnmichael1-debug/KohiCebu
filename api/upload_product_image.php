<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";
require_once "audit.php";


/*
 * ---------------------------------------------------------
 * ONLY POST
 * ---------------------------------------------------------
 */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * AUTHENTICATION
 * ---------------------------------------------------------
 */

$user = requireAuth($conn);


/*
 * ---------------------------------------------------------
 * AUTHORIZATION
 * ---------------------------------------------------------
 *
 * Only ADMIN accounts can upload product images.
 */

requireRole($user, ["ADMIN"]);


/*
 * ---------------------------------------------------------
 * PRODUCT ID
 * ---------------------------------------------------------
 */

$product_id = isset($_POST["product_id"])
    ? (int) $_POST["product_id"]
    : 0;

if ($product_id <= 0) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Valid product ID is required."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * VERIFY PRODUCT EXISTS
 * ---------------------------------------------------------
 */

$product_query = "
    SELECT
        product_id,
        product_name,
        image_url
    FROM products
    WHERE product_id = $1
    LIMIT 1
";

$product_result = pg_query_params(
    $conn,
    $product_query,
    [$product_id]
);

if (!$product_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to verify product."
    ]);

    exit;
}

if (pg_num_rows($product_result) === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" =>
            "Product not found."
    ]);

    exit;
}

$product =
    pg_fetch_assoc(
        $product_result
    );


/*
 * ---------------------------------------------------------
 * STORE PREVIOUS IMAGE
 * ---------------------------------------------------------
 *
 * Keep the current image path so the audit log can show
 * what image was replaced, if any.
 */

$previous_image_url =
    $product["image_url"];


/*
 * ---------------------------------------------------------
 * CHECK UPLOAD
 * ---------------------------------------------------------
 */

if (
    !isset($_FILES["image"]) ||
    !is_array($_FILES["image"])
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Product image is required."
    ]);

    exit;
}


$file = $_FILES["image"];


/*
 * ---------------------------------------------------------
 * UPLOAD ERROR
 * ---------------------------------------------------------
 */

if ($file["error"] !== UPLOAD_ERR_OK) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Image upload failed.",
        "upload_error" =>
            $file["error"]
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * FILE SIZE
 * ---------------------------------------------------------
 *
 * Maximum: 5 MB
 */

$max_file_size =
    5 * 1024 * 1024;

if ($file["size"] <= 0) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Uploaded image is empty."
    ]);

    exit;
}

if ($file["size"] > $max_file_size) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Image must not exceed 5 MB."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * VERIFY ACTUAL IMAGE
 * ---------------------------------------------------------
 */

$image_info =
    @getimagesize(
        $file["tmp_name"]
    );

if ($image_info === false) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Uploaded file is not a valid image."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * ALLOWED MIME TYPES
 * ---------------------------------------------------------
 */

$allowed_mime_types = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];

$file_mime =
    $image_info["mime"] ?? "";

if (
    !isset(
        $allowed_mime_types[$file_mime]
    )
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Only JPG, PNG, and WEBP images are allowed."
    ]);

    exit;
}

$extension =
    $allowed_mime_types[$file_mime];


/*
 * ---------------------------------------------------------
 * UPLOAD DIRECTORY
 * ---------------------------------------------------------
 */

$upload_directory =
    dirname(__DIR__) .
    DIRECTORY_SEPARATOR .
    "uploads" .
    DIRECTORY_SEPARATOR .
    "products";


if (
    !is_dir($upload_directory)
) {

    if (
        !mkdir(
            $upload_directory,
            0755,
            true
        )
    ) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to create product image directory."
        ]);

        exit;
    }
}


/*
 * ---------------------------------------------------------
 * GENERATE SAFE FILE NAME
 * ---------------------------------------------------------
 */

$random_name =
    bin2hex(
        random_bytes(8)
    );

$file_name =
    "product_" .
    $product_id .
    "_" .
    $random_name .
    "." .
    $extension;


/*
 * ---------------------------------------------------------
 * FINAL FILE PATH
 * ---------------------------------------------------------
 */

$destination =
    $upload_directory .
    DIRECTORY_SEPARATOR .
    $file_name;


/*
 * ---------------------------------------------------------
 * MOVE UPLOADED FILE
 * ---------------------------------------------------------
 */

if (
    !move_uploaded_file(
        $file["tmp_name"],
        $destination
    )
) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to save uploaded image."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * DATABASE IMAGE PATH
 * ---------------------------------------------------------
 *
 * Store a relative path rather than localhost/ngrok.
 *
 * This keeps the database independent from the
 * current development domain.
 */

$image_path =
    "uploads/products/" .
    $file_name;


/*
 * ---------------------------------------------------------
 * UPDATE PRODUCT
 * ---------------------------------------------------------
 */

$update_query = "
    UPDATE products
    SET
        image_url = $1
    WHERE product_id = $2
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        $image_path,
        $product_id
    ]
);

if (
    !$update_result ||
    pg_affected_rows($update_result) !== 1
) {

    /*
     * Remove the uploaded file if the
     * database update failed.
     */

    if (
        file_exists($destination)
    ) {
        @unlink($destination);
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Image was uploaded but could not be linked to the product."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 *
 * The new image has been successfully saved and linked
 * to the product.
 *
 * Audit logging must not invalidate the successful upload.
 */

logAudit(
    $conn,
    $user,
    "UPLOAD",
    "PRODUCTS",
    "PRODUCT",
    (int) $product["product_id"],
    "Uploaded a product image.",
    [
        "product_id" =>
            (int)
            $product["product_id"],

        "product_name" =>
            $product["product_name"],

        "previous_image_url" =>
            $previous_image_url,

        "new_image_url" =>
            $image_path,

        "mime_type" =>
            $file_mime,

        "file_extension" =>
            $extension,

        "file_size" =>
            (int) $file["size"]
    ]
);


/*
 * ---------------------------------------------------------
 * SUCCESS
 * ---------------------------------------------------------
 */

http_response_code(200);

echo json_encode([
    "success" => true,

    "message" =>
        "Product image uploaded successfully.",

    "product" => [
        "product_id" =>
            (int) $product["product_id"],

        "product_name" =>
            $product["product_name"],

        "image_url" =>
            $image_path
    ],

    "uploaded_by" =>
        (int) $user["user_id"],

    "uploaded_by_role" =>
        $user["role"]
]);

?>