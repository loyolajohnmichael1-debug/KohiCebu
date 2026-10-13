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
 * Both STAFF and ADMIN can receive inventory.
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
 * Validate quantity
 * ---------------------------------------------------------
 */

$quantity = isset($data["quantity"])
    ? (float) $data["quantity"]
    : 0;

if ($quantity <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Quantity must be greater than zero."
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
        "message" => "A reason is required for stock-in."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Limit reason length
 * ---------------------------------------------------------
 */

if (strlen($reason) > 500) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Reason must not exceed 500 characters."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Verify ingredient exists
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
 * Apply inventory transaction
 * ---------------------------------------------------------
 *
 * The database function:
 *
 * 1. Locks the ingredient row
 * 2. Reads current quantity
 * 3. Calculates new quantity
 * 4. Updates ingredients.current_quantity
 * 5. Creates inventory_transactions record
 *
 * The authenticated user's ID is recorded as the
 * person who performed the stock-in.
 */

$transaction_query = "
    SELECT *
    FROM apply_inventory_transaction(
        $1,
        $2,
        'STOCK_IN',
        $3,
        NULL,
        $4
    )
";

$transaction_result = pg_query_params(
    $conn,
    $transaction_query,
    [
        $ingredient_id,
        (int) $user["user_id"],
        $quantity,
        $reason
    ]
);

if (!$transaction_result) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Inventory stock-in could not be completed."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Retrieve transaction result
 * ---------------------------------------------------------
 */

$transaction =
    pg_fetch_assoc(
        $transaction_result
    );

if (!$transaction) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Stock-in completed but transaction details could not be retrieved."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 *
 * The inventory transaction has successfully completed.
 */

logAudit(
    $conn,
    $user,
    "STOCK_IN",
    "INVENTORY",
    "INGREDIENT",
    (int) $ingredient["ingredient_id"],
    "Received inventory stock.",
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

        "quantity_before" =>
            (float)
            $transaction["quantity_before"],

        "quantity_added" =>
            (float)
            $transaction["quantity_change"],

        "quantity_after" =>
            (float)
            $transaction["quantity_after"],

        "reason" =>
            $reason,

        "transaction_id" =>
            (int)
            $transaction["transaction_id"],

        "transaction_type" =>
            "STOCK_IN"
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
        "Inventory stock-in completed successfully.",

    "ingredient" => [
        "ingredient_id" =>
            (int) $ingredient["ingredient_id"],

        "ingredient_name" =>
            $ingredient["ingredient_name"],

        "unit_name" =>
            $ingredient["unit_name"],

        "unit_code" =>
            $ingredient["unit_code"],

        "quantity_before" =>
            (float)
            $transaction["quantity_before"],

        "quantity_added" =>
            (float)
            $transaction["quantity_change"],

        "quantity_after" =>
            (float)
            $transaction["quantity_after"]
    ],

    "transaction" => [
        "transaction_id" =>
            (int)
            $transaction["transaction_id"],

        "transaction_type" =>
            "STOCK_IN",

        "reason" =>
            $reason,

        "performed_by" =>
            (int) $user["user_id"],

        "performed_by_role" =>
            $user["role"]
    ]
]);

?>