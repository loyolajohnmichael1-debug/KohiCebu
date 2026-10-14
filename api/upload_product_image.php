<?php

header("Content-Type: application/json; charset=UTF-8");

// Keep PHP notices, warnings, and fatal errors from corrupting the JSON
// response expected by the admin upload flow.
ini_set("display_errors", "0");
ob_start();

set_exception_handler(function (Throwable $exception) {
    error_log(
        "Product image upload exception: " .
        $exception->getMessage()
    );

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
    }

    echo json_encode([
        "success" => false,
        "message" => "The image upload could not be completed. Check the server error log."
    ]);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];

    if (!$error || !in_array($error["type"], $fatalTypes, true)) {
        return;
    }

    error_log(
        "Product image upload fatal error: " .
        $error["message"] .
        " in " .
        $error["file"] .
        ":" .
        $error["line"]
    );

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code(500);
        header("Content-Type: application/json; charset=UTF-8");
    }

    echo json_encode([
        "success" => false,
        "message" => "The image upload stopped because of a server error. Check the server error log."
    ]);
});

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";

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
 * AUTHENTICATION AND AUTHORIZATION
 * ---------------------------------------------------------
 */

$user = requireAuth($conn);
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
    SELECT product_id, product_name, image_url
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
    error_log("Failed to verify product before image upload.");

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to verify product."
    ]);
    exit;
}

if (pg_num_rows($product_result) === 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Product not found."
    ]);
    exit;
}

$product = pg_fetch_assoc($product_result);
$previous_image_url = $product["image_url"];

/*
 * ---------------------------------------------------------
 * CHECK UPLOAD
 * ---------------------------------------------------------
 */

if (!isset($_FILES["image"]) || !is_array($_FILES["image"])) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Product image is required."
    ]);
    exit;
}

$file = $_FILES["image"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    $upload_error_messages = [
        UPLOAD_ERR_INI_SIZE => "Image exceeds the server upload limit. Choose an image no larger than 5 MB.",
        UPLOAD_ERR_FORM_SIZE => "Image exceeds the form upload limit.",
        UPLOAD_ERR_PARTIAL => "Image upload was interrupted. Please try again.",
        UPLOAD_ERR_NO_FILE => "No image file was received.",
        UPLOAD_ERR_NO_TMP_DIR => "The server is missing its temporary upload folder.",
        UPLOAD_ERR_CANT_WRITE => "The server could not save the uploaded image temporarily.",
        UPLOAD_ERR_EXTENSION => "A server extension stopped the image upload."
    ];

    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $upload_error_messages[$file["error"]]
            ?? "Image upload failed. Please try again.",
        "upload_error" => $file["error"]
    ]);
    exit;
}

/*
 * ---------------------------------------------------------
 * FILE SIZE VALIDATION: MAXIMUM 5 MB
 * ---------------------------------------------------------
 */

$max_file_size = 5 * 1024 * 1024;

if ($file["size"] <= 0) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Uploaded image is empty."
    ]);
    exit;
}

if ($file["size"] > $max_file_size) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Image must not exceed 5 MB."
    ]);
    exit;
}

/*
 * ---------------------------------------------------------
 * VERIFY ACTUAL IMAGE CONTENT
 * ---------------------------------------------------------
 */

$image_info = @getimagesize($file["tmp_name"]);

if ($image_info === false) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Uploaded file is not a valid image."
    ]);
    exit;
}

$allowed_mime_types = [
    "image/jpeg" => "jpg",
    "image/png"  => "png",
    "image/webp" => "webp"
];

$file_mime = $image_info["mime"] ?? "";

if (!isset($allowed_mime_types[$file_mime])) {
    http_response_code(422);
    echo json_encode([
        "success" => false,
        "message" => "Only JPG, PNG, and WEBP images are allowed."
    ]);
    exit;
}

$extension = $allowed_mime_types[$file_mime];

/*
 * ---------------------------------------------------------
 * SUPABASE CONFIGURATION
 * ---------------------------------------------------------
 */

$supabase_url = rtrim(
    getenv("SUPABASE_URL") ?: "",
    "/"
);

$supabase_key = getenv("SUPABASE_SECRET_KEY") ?: "";

$bucket = "product-images";

if ($supabase_url === "" || $supabase_key === "") {
    error_log("Supabase Storage configuration is incomplete.");

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Image storage is not configured."
    ]);
    exit;
}

if (!function_exists("curl_init")) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "The server's cURL extension is unavailable."
    ]);
    exit;
}

/*
 * ---------------------------------------------------------
 * UPLOAD IMAGE TO SUPABASE STORAGE
 * ---------------------------------------------------------
 */

try {
    $random_name = bin2hex(random_bytes(12));
} catch (Throwable $e) {
    error_log("Failed to generate a unique image filename.");

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not prepare the image upload."
    ]);
    exit;
}

$object_path = "products/"
    . $product_id
    . "/product_"
    . $product_id
    . "_"
    . $random_name
    . "."
    . $extension;

/*
 * Encode each path segment separately so slashes remain
 * directory separators in the Storage API URL.
 */

$encoded_path = implode(
    "/",
    array_map("rawurlencode", explode("/", $object_path))
);

$encoded_bucket = rawurlencode($bucket);

$upload_url = $supabase_url
    . "/storage/v1/object/"
    . $encoded_bucket
    . "/"
    . $encoded_path;

$image_contents = file_get_contents($file["tmp_name"]);

if ($image_contents === false) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Could not read the uploaded image."
    ]);
    exit;
}

$supabase_headers = [
    "apikey: " . $supabase_key,
    "Content-Type: " . $file_mime,
    "x-upsert: false"
];

// Current Supabase secret keys (sb_secret_...) are API keys, not JWTs.
// Sending one as a Bearer token causes Supabase to reject it as an invalid
// JWT. Legacy service-role keys are JWTs and still need Bearer authorization.
if (!str_starts_with($supabase_key, "sb_secret_")) {
    $supabase_headers[] = "Authorization: Bearer " . $supabase_key;
}

$curl = curl_init($upload_url);

curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $image_contents,
    CURLOPT_HTTPHEADER => $supabase_headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 60
]);

$supabase_response = curl_exec($curl);
$curl_error = curl_error($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

$supabase_data = is_string($supabase_response)
    ? json_decode($supabase_response, true)
    : null;

if (
    $supabase_response === false ||
    $http_status < 200 ||
    $http_status >= 300
) {
    $provider_message = is_array($supabase_data)
        ? ($supabase_data["message"] ?? $supabase_data["error"] ?? "")
        : "";

    error_log(
        "Supabase image upload failed. HTTP status: "
        . $http_status
        . "; message: "
        . (is_string($provider_message) ? $provider_message : "")
        . "; transport error: "
        . $curl_error
    );

    http_response_code(502);
    echo json_encode([
        "success" => false,
        "message" => "Failed to upload image to cloud storage."
            . ($http_status > 0 ? " (HTTP " . $http_status . ")." : "")
    ]);
    exit;
}

/*
 * ---------------------------------------------------------
 * BUILD PUBLIC IMAGE URL
 * ---------------------------------------------------------
 *
 * The bucket is public. Existing Cloudinary URLs are not
 * modified by this upload.
 */

$image_url = $supabase_url
    . "/storage/v1/object/public/"
    . $encoded_bucket
    . "/"
    . $encoded_path;

/*
 * ---------------------------------------------------------
 * UPDATE PRODUCT IN NEON POSTGRESQL
 * ---------------------------------------------------------
 */

$update_query = "
    UPDATE products
    SET image_url = $1
    WHERE product_id = $2
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        $image_url,
        $product_id
    ]
);

if (
    !$update_result ||
    pg_affected_rows($update_result) !== 1
) {
    /*
     * Clean up only the newly uploaded object if the
     * database update fails. Never delete the old image.
     */

    $delete_url = $supabase_url
        . "/storage/v1/object/"
        . $encoded_bucket
        . "/"
        . $encoded_path;

    $cleanup = curl_init($delete_url);

    curl_setopt_array($cleanup, [
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => array_values(array_filter([
            "apikey: " . $supabase_key,
            str_starts_with($supabase_key, "sb_secret_")
                ? null
                : "Authorization: Bearer " . $supabase_key
        ])),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20
    ]);

    $cleanup_response = curl_exec($cleanup);
    $cleanup_status = curl_getinfo(
        $cleanup,
        CURLINFO_HTTP_CODE
    );

    if (
        $cleanup_response === false ||
        $cleanup_status < 200 ||
        $cleanup_status >= 300
    ) {
        error_log(
            "Supabase cleanup failed after database update failure. "
            . "HTTP status: "
            . $cleanup_status
        );
    }

    curl_close($cleanup);

    error_log(
        "Supabase image uploaded, but Neon database update failed "
        . "for product ID "
        . $product_id
        . "."
    );

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Image could not be linked to the product."
    ]);
    exit;
}

/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
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
        "product_id" => (int) $product["product_id"],
        "product_name" => $product["product_name"],
        "previous_image_url" => $previous_image_url,
        "new_image_url" => $image_url,
        "storage_bucket" => $bucket,
        "storage_object_path" => $object_path,
        "mime_type" => $file_mime,
        "file_extension" => $extension,
        "file_size" => (int) $file["size"]
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
    "message" => "Product image uploaded successfully.",
    "product" => [
        "product_id" => (int) $product["product_id"],
        "product_name" => $product["product_name"],
        "image_url" => $image_url
    ],
    "uploaded_by" => (int) $user["user_id"],
    "uploaded_by_role" => $user["role"]
]);

?>
