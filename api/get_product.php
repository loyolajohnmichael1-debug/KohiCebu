<?php

header("Content-Type: application/json");

require_once "db.php";

// =========================================================
// REQUEST METHOD
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}

// =========================================================
// VALIDATE PRODUCT ID
// =========================================================

if (
    !isset($_GET["id"]) ||
    empty($_GET["id"])
) {

    echo json_encode([
        "success" => false,
        "message" => "Product ID is required."
    ]);

    exit;
}

$product_id = intval($_GET["id"]);

if ($product_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid product ID."
    ]);

    exit;
}

// =========================================================
// GET PRODUCT
// =========================================================

$query = "
    SELECT
        product_id,
        product_name,
        description,
        category,
        price,
        image_url,
        stock_quantity,
        is_available,
        created_at
    FROM products
    WHERE product_id = $1
    LIMIT 1
";

$result = pg_query_params(
    $conn,
    $query,
    [$product_id]
);

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to retrieve product."
    ]);

    exit;
}

if (pg_num_rows($result) === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Product not found."
    ]);

    exit;
}

// =========================================================
// FORMAT PRODUCT
// =========================================================

$row = pg_fetch_assoc($result);

$product = [
    "product_id" =>
        (int) $row["product_id"],

    "product_name" =>
        $row["product_name"],

    "description" =>
        $row["description"],

    "category" =>
        $row["category"],

    "price" =>
        (float) $row["price"],

    "image_url" =>
        $row["image_url"],

    "stock_quantity" =>
        (int) $row["stock_quantity"],

    "is_available" =>
        ($row["is_available"] === "t"),

    "created_at" =>
        $row["created_at"]
];

// =========================================================
// RESPONSE
// =========================================================

echo json_encode([
    "success" => true,
    "message" =>
        "Product retrieved successfully.",
    "product" => $product
]);

?>