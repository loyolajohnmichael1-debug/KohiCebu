<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

/*
 * ---------------------------------------------------------
 * Authentication
 * ---------------------------------------------------------
 */
$user = requireAuth($conn);

/*
 * ---------------------------------------------------------
 * Authorization
 * ---------------------------------------------------------
 */
requireRole($user, ["STAFF", "ADMIN"]);

/*
 * ---------------------------------------------------------
 * Request method
 * ---------------------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Validate product ID
 * ---------------------------------------------------------
 */
$product_id = isset($_GET["product_id"])
    ? (int) $_GET["product_id"]
    : 0;

if ($product_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid product ID is required."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Verify product
 * ---------------------------------------------------------
 */
$product_query = "
    SELECT
        product_id,
        product_name,
        category
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
        "message" => "Failed to retrieve product."
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

/*
 * ---------------------------------------------------------
 * Retrieve recipe
 * ---------------------------------------------------------
 */
$recipe_query = "
    SELECT
        pr.recipe_id,
        pr.product_id,
        pr.ingredient_id,
        i.ingredient_name,
        pr.quantity_required,
        u.unit_name,
        u.unit_code,
        pr.created_at,
        pr.updated_at
    FROM product_recipes pr

    INNER JOIN ingredients i
        ON i.ingredient_id = pr.ingredient_id

    INNER JOIN inventory_units u
        ON u.unit_id = i.unit_id

    WHERE pr.product_id = $1

    ORDER BY pr.recipe_id
";

$recipe_result = pg_query_params(
    $conn,
    $recipe_query,
    [$product_id]
);

if (!$recipe_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve product recipe."
    ]);

    exit;
}

$recipes = [];

while ($row = pg_fetch_assoc($recipe_result)) {

    $recipes[] = [
        "recipe_id" =>
            (int) $row["recipe_id"],

        "product_id" =>
            (int) $row["product_id"],

        "ingredient_id" =>
            (int) $row["ingredient_id"],

        "ingredient_name" =>
            $row["ingredient_name"],

        "quantity_required" =>
            (float) $row["quantity_required"],

        "unit_name" =>
            $row["unit_name"],

        "unit_code" =>
            $row["unit_code"],

        "created_at" =>
            $row["created_at"],

        "updated_at" =>
            $row["updated_at"]
    ];
}

/*
 * ---------------------------------------------------------
 * Success response
 * ---------------------------------------------------------
 */
http_response_code(200);

echo json_encode([
    "success" => true,

    "message" =>
        "Product recipe retrieved successfully.",

    "product" => [
        "product_id" =>
            (int) $product["product_id"],

        "product_name" =>
            $product["product_name"],

        "category" =>
            $product["category"]
    ],

    "recipe_count" =>
        count($recipes),

    "has_recipe" =>
        count($recipes) > 0,

    "recipes" =>
        $recipes
]);

?>