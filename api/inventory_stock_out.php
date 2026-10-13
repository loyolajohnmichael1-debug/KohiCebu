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

if (
    !isset($data["quantity"]) ||
    !is_numeric($data["quantity"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid stock-out quantity is required."
    ]);

    exit;
}

$quantity = (float) $data["quantity"];

if ($quantity <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Stock-out quantity must be greater than zero."
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
        "message" => "A reason is required for stock-out."
    ]);

    exit;
}

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
 * Convert stock-out quantity to a negative change
 * ---------------------------------------------------------
 */

$quantity_change =
    -abs($quantity);


/*
 * ---------------------------------------------------------
 * Apply inventory transaction
 * ---------------------------------------------------------
 */

$stock_out_query = "
    SELECT *
    FROM apply_inventory_transaction(
        $1,
        $2,
        'STOCK_OUT',
        $3,
        NULL,
        $4
    )
";

$stock_out_result = pg_query_params(
    $conn,
    $stock_out_query,
    [
        $ingredient_id,
        (int) $user["user_id"],
        $quantity_change,
        $reason
    ]
);

if (!$stock_out_result) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Inventory stock-out could not be completed."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Retrieve transaction result
 * ---------------------------------------------------------
 */

$stock_out =
    pg_fetch_assoc(
        $stock_out_result
    );

if (!$stock_out) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Stock-out completed but transaction details could not be retrieved."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 *
 * The inventory transaction has successfully completed.
 *
 * The inventory transaction stores quantity_change as
 * a negative value for STOCK_OUT.
 *
 * The audit metadata records quantity_removed as a
 * positive value for easier human-readable reporting.
 */

logAudit(
    $conn,
    $user,
    "STOCK_OUT",
    "INVENTORY",
    "INGREDIENT",
    (int) $ingredient["ingredient_id"],
    "Removed inventory stock.",
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
            $stock_out["quantity_before"],

        "quantity_removed" =>
            abs(
                (float)
                $stock_out["quantity_change"]
            ),

        "quantity_after" =>
            (float)
            $stock_out["quantity_after"],

        "reason" =>
            $reason,

        "transaction_id" =>
            (int)
            $stock_out["transaction_id"],

        "transaction_type" =>
            "STOCK_OUT"
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
        "Inventory stock-out completed successfully.",

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
            $stock_out["quantity_before"],

        "quantity_removed" =>
            abs(
                (float)
                $stock_out["quantity_change"]
            ),

        "quantity_after" =>
            (float)
            $stock_out["quantity_after"]
    ],

    "transaction" => [
        "transaction_id" =>
            (int)
            $stock_out["transaction_id"],

        "transaction_type" =>
            "STOCK_OUT",

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