<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

$user = requireAuth($conn);
requireRole($user, ["ADMIN"]);

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);
    exit;
}

$productId = filter_var(
    $data["product_id"] ?? null,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);
$rawPrice = $data["price"] ?? null;

if ($productId === false || !is_numeric($rawPrice)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "A valid product ID and price are required."
    ]);
    exit;
}

$price = round((float) $rawPrice, 2);
if (!is_finite($price) || $price <= 0 || $price > 99999999.99) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Price must be greater than zero and within the supported range."
    ]);
    exit;
}

$productResult = pg_query_params(
    $conn,
    "SELECT product_id, product_name, price FROM products WHERE product_id = $1",
    [$productId]
);

if (!$productResult || pg_num_rows($productResult) === 0) {
    http_response_code($productResult ? 404 : 500);
    echo json_encode([
        "success" => false,
        "message" => $productResult ? "Product not found." : "Unable to load the product."
    ]);
    exit;
}

$previous = pg_fetch_assoc($productResult);
$updateResult = pg_query_params(
    $conn,
    "UPDATE products SET price = $1 WHERE product_id = $2 RETURNING product_id, product_name, price",
    [$price, $productId]
);

if (!$updateResult || pg_num_rows($updateResult) === 0) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Unable to update the product price."
    ]);
    exit;
}

$product = pg_fetch_assoc($updateResult);
logAudit(
    $conn,
    $user,
    "UPDATE",
    "PRODUCTS",
    "PRODUCT",
    (int) $product["product_id"],
    "Updated product price.",
    [
        "product_id" => (int) $product["product_id"],
        "product_name" => $product["product_name"],
        "previous_price" => (float) $previous["price"],
        "new_price" => (float) $product["price"]
    ]
);

echo json_encode([
    "success" => true,
    "message" => "Product price updated successfully.",
    "product" => [
        "product_id" => (int) $product["product_id"],
        "product_name" => $product["product_name"],
        "price" => (float) $product["price"]
    ]
]);

