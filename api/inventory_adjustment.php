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
 * STAFF and ADMIN may perform inventory adjustments.
 */

requireRole($user, ["STAFF", "ADMIN"]);


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
 * Validate actual quantity
 * ---------------------------------------------------------
 */

if (
    !isset($data["actual_quantity"]) ||
    !is_numeric($data["actual_quantity"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid actual quantity is required."
    ]);

    exit;
}

$actual_quantity =
    (float) $data["actual_quantity"];

if ($actual_quantity < 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Actual quantity cannot be negative."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Validate reason
 * ---------------------------------------------------------
 */

$reason = isset($data["reason"])
    ? trim((string) $data["reason"])
    : "";

if ($reason === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "A reason is required for inventory adjustment."
    ]);

    exit;
}

if (strlen($reason) > 500) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Reason must not exceed 500 characters."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Verify ingredient
 * ---------------------------------------------------------
 */

$ingredient_query = "
    SELECT
        i.ingredient_id,
        i.ingredient_name,
        i.current_quantity,
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
 * Apply inventory adjustment
 * ---------------------------------------------------------
 */

$adjustment_query = "
    SELECT *
    FROM apply_inventory_adjustment(
        $1,
        $2,
        $3,
        $4
    )
";

$adjustment_result = pg_query_params(
    $conn,
    $adjustment_query,
    [
        $ingredient_id,
        (int) $user["user_id"],
        $actual_quantity,
        $reason
    ]
);

if (!$adjustment_result) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Inventory adjustment could not be completed."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Retrieve adjustment result
 * ---------------------------------------------------------
 */

$adjustment =
    pg_fetch_assoc(
        $adjustment_result
    );

if (!$adjustment) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Adjustment completed but transaction details could not be retrieved."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 *
 * The database function calculates the quantity change
 * between the previous quantity and the actual physical
 * quantity entered by the user.
 */

logAudit(
    $conn,
    $user,
    "ADJUSTMENT",
    "INVENTORY",
    "INGREDIENT",
    (int) $ingredient["ingredient_id"],
    "Adjusted inventory quantity.",
    [
        "ingredient_id" =>
            (int)
            $ingredient["ingredient_id"],

        "ingredient_name" =>
            $ingredient["ingredient_name"],

        "unit_name" =>
            $ingredient["unit_name"],

        "unit_code" =>
            $ingredient["unit_code"],

        "actual_quantity" =>
            $actual_quantity,

        "quantity_before" =>
            (float)
            $adjustment["quantity_before"],

        "quantity_change" =>
            (float)
            $adjustment["quantity_change"],

        "quantity_after" =>
            (float)
            $adjustment["quantity_after"],

        "reason" =>
            $reason,

        "transaction_id" =>
            (int)
            $adjustment["transaction_id"],

        "transaction_type" =>
            "ADJUSTMENT"
    ]
);


/*
 * ---------------------------------------------------------
 * Success response
 * ---------------------------------------------------------
 */

http_response_code(200);

echo json_encode([
    "success" => true,

    "message" =>
        "Inventory adjustment completed successfully.",

    "ingredient" => [
        "ingredient_id" =>
            (int)
            $ingredient["ingredient_id"],

        "ingredient_name" =>
            $ingredient["ingredient_name"],

        "unit_name" =>
            $ingredient["unit_name"],

        "unit_code" =>
            $ingredient["unit_code"],

        "quantity_before" =>
            (float)
            $adjustment["quantity_before"],

        "quantity_change" =>
            (float)
            $adjustment["quantity_change"],

        "quantity_after" =>
            (float)
            $adjustment["quantity_after"]
    ],

    "transaction" => [
        "transaction_id" =>
            (int)
            $adjustment["transaction_id"],

        "transaction_type" =>
            "ADJUSTMENT",

        "reason" =>
            $reason,

        "performed_by" =>
            (int)
            $user["user_id"],

        "performed_by_role" =>
            $user["role"]
    ]
]);

?>