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
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Image upload failed.",
        "upload_error" => $file["error"]
    ]);
    exit;
}

/*
 * ---------------------------------------------------------
 * FILE SIZE VALIDATION
 * ---------------------------------------------------------
 *
 * Maximum: 5 MB
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
 * VERIFY ACTUAL IMAGE
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
 * CLOUDINARY CONFIGURATION
 * ---------------------------------------------------------
 */

$cloud_name = getenv("CLOUDINARY_CLOUD_NAME");
$api_key = getenv("CLOUDINARY_API_KEY");
$api_secret = getenv("CLOUDINARY_API_SECRET");

if (!$cloud_name || !$api_key || !$api_secret) {
    error_log("Cloudinary configuration is incomplete.");

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Cloudinary is not configured."
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
 * UPLOAD IMAGE TO CLOUDINARY
 * ---------------------------------------------------------
 */

$random_name = bin2hex(random_bytes(8));

$public_id = "product_" . $product_id . "_" . $random_name;
$timestamp = time();
$folder = "kohicebu/products";

$signature_parameters = [
    "folder" => $folder,
    "public_id" => $public_id,
    "timestamp" => $timestamp
];

ksort($signature_parameters);

$signature_parts = [];

foreach ($signature_parameters as $key => $value) {
    $signature_parts[] = $key . "=" . $value;
}

$signature_string = implode("&", $signature_parts);
$signature = sha1($signature_string . $api_secret);

$upload_url = "https://api.cloudinary.com/v1_1/"
    . rawurlencode($cloud_name)
    . "/image/upload";

$curl = curl_init($upload_url);

curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        "file" => new CURLFile(
            $file["tmp_name"],
            $file_mime,
            "product." . $extension
        ),
        "api_key" => $api_key,
        "timestamp" => $timestamp,
        "folder" => $folder,
        "public_id" => $public_id,
        "signature" => $signature
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 60
]);

$cloudinary_response = curl_exec($curl);
$curl_error = curl_error($curl);
$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

curl_close($curl);

$cloudinary_data = is_string($cloudinary_response)
    ? json_decode($cloudinary_response, true)
    : null;

if (
    $cloudinary_response === false ||
    $http_status < 200 ||
    $http_status >= 300 ||
    empty($cloudinary_data["secure_url"]) ||
    empty($cloudinary_data["public_id"])
) {
    error_log(
        "Cloudinary upload failed. HTTP status: "
        . $http_status
        . "; error: "
        . $curl_error
    );

    http_response_code(502);
    echo json_encode([
        "success" => false,
        "message" => "Failed to upload image to cloud storage."
    ]);
    exit;
}

$image_url = $cloudinary_data["secure_url"];
$uploaded_public_id = $cloudinary_data["public_id"];

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
     * Remove the newly uploaded image from Cloudinary
     * if the database update fails.
     */

    $destroy_timestamp = time();

    $destroy_parameters = [
        "public_id" => $uploaded_public_id,
        "timestamp" => $destroy_timestamp
    ];

    ksort($destroy_parameters);

    $destroy_parts = [];

    foreach ($destroy_parameters as $key => $value) {
        $destroy_parts[] = $key . "=" . $value;
    }

    $destroy_signature = sha1(
        implode("&", $destroy_parts) . $api_secret
    );

    $destroy_url = "https://api.cloudinary.com/v1_1/"
        . rawurlencode($cloud_name)
        . "/image/destroy";

    $cleanup = curl_init($destroy_url);

    curl_setopt_array($cleanup, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            "public_id" => $uploaded_public_id,
            "api_key" => $api_key,
            "timestamp" => $destroy_timestamp,
            "signature" => $destroy_signature
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20
    ]);

    $cleanup_response = curl_exec($cleanup);

    if ($cleanup_response === false) {
        error_log(
            "Cloudinary cleanup request failed: "
            . curl_error($cleanup)
        );
    }

    curl_close($cleanup);

    error_log(
        "Product image uploaded to Cloudinary, but the Neon "
        . "database update failed for product ID "
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
        "cloudinary_public_id" => $uploaded_public_id,
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
