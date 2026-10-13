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
 *
 * Recipe configuration is ADMIN-only.
 */
requireRole($user, ["ADMIN"]);

/*
 * ---------------------------------------------------------
 * Request method
 * ---------------------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] !== "PUT") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only PUT requests are allowed."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Read JSON body
 * ---------------------------------------------------------
 */
$raw_input = file_get_contents("php://input");

$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request body."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Validate recipe ID
 * ---------------------------------------------------------
 */
$recipe_id = isset($data["recipe_id"])
    ? (int) $data["recipe_id"]
    : 0;

if ($recipe_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid recipe ID is required."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Validate quantity
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

$quantity_required = (float) $data["quantity_required"];

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
 * Find recipe
 * ---------------------------------------------------------
 */
$recipe_query = "
    SELECT
        pr.recipe_id,
        pr.product_id,
        pr.ingredient_id,
        p.product_name,
        i.ingredient_name,
        u.unit_name,
        u.unit_code,
        pr.quantity_required AS old_quantity
    FROM product_recipes pr

    INNER JOIN products p
        ON p.product_id = pr.product_id

    INNER JOIN ingredients i
        ON i.ingredient_id = pr.ingredient_id

    INNER JOIN inventory_units u
        ON u.unit_id = i.unit_id

    WHERE pr.recipe_id = $1

    LIMIT 1
";

$recipe_result = pg_query_params(
    $conn,
    $recipe_query,
    [$recipe_id]
);

if (!$recipe_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve recipe."
    ]);

    exit;
}

if (pg_num_rows($recipe_result) === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Recipe not found."
    ]);

    exit;
}

$recipe = pg_fetch_assoc($recipe_result);

/*
 * ---------------------------------------------------------
 * Update recipe quantity
 * ---------------------------------------------------------
 */
$update_query = "
    UPDATE product_recipes
    SET
        quantity_required = $1,
        updated_at = CURRENT_TIMESTAMP
    WHERE recipe_id = $2
    RETURNING
        recipe_id,
        product_id,
        ingredient_id,
        quantity_required,
        created_at,
        updated_at
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        $quantity_required,
        $recipe_id
    ]
);

if (!$update_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update product recipe."
    ]);

    exit;
}

$updated = pg_fetch_assoc($update_result);

/*
 * ---------------------------------------------------------
 * Success response
 * ---------------------------------------------------------
 */
http_response_code(200);

echo json_encode([
    "success" => true,

    "message" =>
        "Product recipe updated successfully.",

    "recipe" => [
        "recipe_id" =>
            (int) $updated["recipe_id"],

        "product_id" =>
            (int) $updated["product_id"],

        "product_name" =>
            $recipe["product_name"],

        "ingredient_id" =>
            (int) $updated["ingredient_id"],

        "ingredient_name" =>
            $recipe["ingredient_name"],

        "quantity_before" =>
            (float) $recipe["old_quantity"],

        "quantity_required" =>
            (float) $updated["quantity_required"],

        "unit_name" =>
            $recipe["unit_name"],

        "unit_code" =>
            $recipe["unit_code"],

        "updated_at" =>
            $updated["updated_at"]
    ],

    "updated_by" =>
        (int) $user["user_id"],

    "updated_by_role" =>
        $user["role"]
]);

?>