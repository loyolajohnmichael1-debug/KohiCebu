<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";
require_once "audit.php";


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
 *
 * Only ADMIN can manage recipes.
 */

requireRole($user, ["ADMIN"]);


/*
 * ---------------------------------------------------------
 * Request method
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
 * Read JSON body
 * ---------------------------------------------------------
 */

$raw_input = file_get_contents("php://input");

$data = json_decode(
    $raw_input,
    true
);

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($data)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request body."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Validate product ID
 * ---------------------------------------------------------
 */

$product_id = isset($data["product_id"])
    ? (int) $data["product_id"]
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
 * Validate ingredient ID
 * ---------------------------------------------------------
 */

$ingredient_id = isset($data["ingredient_id"])
    ? (int) $data["ingredient_id"]
    : 0;

if ($ingredient_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid ingredient ID is required."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Validate recipe quantity
 * ---------------------------------------------------------
 */

if (
    !isset($data["quantity_required"]) ||
    !is_numeric($data["quantity_required"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid recipe quantity is required."
    ]);

    exit;
}

$quantity_required =
    (float) $data["quantity_required"];

if ($quantity_required <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Recipe quantity must be greater than zero."
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
        product_name
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

$product =
    pg_fetch_assoc(
        $product_result
    );


/*
 * ---------------------------------------------------------
 * Verify ingredient
 * ---------------------------------------------------------
 */

$ingredient_query = "
    SELECT
        i.ingredient_id,
        i.ingredient_name,
        u.unit_name,
        u.unit_code
    FROM ingredients i
    INNER JOIN inventory_units u
        ON u.unit_id = i.unit_id
    WHERE i.ingredient_id = $1
    LIMIT 1
";

$ingredient_result = pg_query_params(
    $conn,
    $ingredient_query,
    [$ingredient_id]
);

if (!$ingredient_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to verify ingredient."
    ]);

    exit;
}

if (pg_num_rows($ingredient_result) === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Ingredient not found."
    ]);

    exit;
}

$ingredient =
    pg_fetch_assoc(
        $ingredient_result
    );


/*
 * ---------------------------------------------------------
 * Check for duplicate recipe ingredient
 * ---------------------------------------------------------
 */

$duplicate_query = "
    SELECT
        recipe_id
    FROM product_recipes
    WHERE product_id = $1
      AND ingredient_id = $2
    LIMIT 1
";

$duplicate_result = pg_query_params(
    $conn,
    $duplicate_query,
    [
        $product_id,
        $ingredient_id
    ]
);

if (!$duplicate_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to check existing recipe."
    ]);

    exit;
}

if (pg_num_rows($duplicate_result) > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "This ingredient is already part of the product recipe."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Insert recipe item
 * ---------------------------------------------------------
 */

$insert_query = "
    INSERT INTO product_recipes (
        product_id,
        ingredient_id,
        quantity_required
    )
    VALUES ($1, $2, $3)
    RETURNING
        recipe_id,
        product_id,
        ingredient_id,
        quantity_required,
        created_at,
        updated_at
";

$insert_result = pg_query_params(
    $conn,
    $insert_query,
    [
        $product_id,
        $ingredient_id,
        $quantity_required
    ]
);

if (!$insert_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to add product recipe."
    ]);

    exit;
}

$recipe =
    pg_fetch_assoc(
        $insert_result
    );


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 *
 * Record the recipe ingredient after the database
 * insertion has succeeded.
 */

logAudit(
    $conn,
    $user,
    "CREATE",
    "PRODUCTS",
    "PRODUCT_RECIPE",
    (int) $recipe["recipe_id"],
    "Added an ingredient to a product recipe.",
    [
        "recipe_id" =>
            (int)
            $recipe["recipe_id"],

        "product_id" =>
            (int)
            $recipe["product_id"],

        "product_name" =>
            $product["product_name"],

        "ingredient_id" =>
            (int)
            $recipe["ingredient_id"],

        "ingredient_name" =>
            $ingredient["ingredient_name"],

        "quantity_required" =>
            (float)
            $recipe["quantity_required"],

        "unit_name" =>
            $ingredient["unit_name"],

        "unit_code" =>
            $ingredient["unit_code"]
    ]
);


/*
 * ---------------------------------------------------------
 * Success response
 * ---------------------------------------------------------
 */

http_response_code(201);

echo json_encode([
    "success" => true,

    "message" =>
        "Product recipe ingredient added successfully.",

    "recipe" => [
        "recipe_id" =>
            (int) $recipe["recipe_id"],

        "product_id" =>
            (int) $recipe["product_id"],

        "product_name" =>
            $product["product_name"],

        "ingredient_id" =>
            (int) $recipe["ingredient_id"],

        "ingredient_name" =>
            $ingredient["ingredient_name"],

        "quantity_required" =>
            (float) $recipe["quantity_required"],

        "unit_name" =>
            $ingredient["unit_name"],

        "unit_code" =>
            $ingredient["unit_code"],

        "created_at" =>
            $recipe["created_at"],

        "updated_at" =>
            $recipe["updated_at"]
    ],

    "created_by" =>
        (int) $user["user_id"],

    "created_by_role" =>
        $user["role"]
]);

?>