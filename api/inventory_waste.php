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
 * Validate waste quantity
 * ---------------------------------------------------------
 */

if (
    !isset($data["quantity"]) ||
    !is_numeric($data["quantity"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid waste quantity is required."
    ]);

    exit;
}

$quantity = (float) $data["quantity"];

if ($quantity <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Waste quantity must be greater than zero."
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
        "message" => "A reason is required for inventory waste."
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
 * Make waste quantity negative
 * ---------------------------------------------------------
 *
 * The client sends a positive amount:
 *
 *     500
 *
 * The inventory transaction records:
 *
 *     -500
 */

$quantity_change =
    -abs($quantity);


/*
 * ---------------------------------------------------------
 * Apply inventory transaction
 * ---------------------------------------------------------
 */

$waste_query = "
    SELECT *
    FROM apply_inventory_transaction(
        $1,
        $2,
        'WASTE',
        $3,
        NULL,
        $4
    )
";

$waste_result = pg_query_params(
    $conn,
    $waste_query,
    [
        $ingredient_id,
        (int) $user["user_id"],
        $quantity_change,
        $reason
    ]
);

if (!$waste_result) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Inventory waste could not be recorded."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Retrieve transaction result
 * ---------------------------------------------------------
 */

$waste =
    pg_fetch_assoc(
        $waste_result
    );

if (!$waste) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Waste was recorded but transaction details could not be retrieved."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 *
 * The waste transaction has successfully completed.
 *
 * The underlying inventory transaction stores the
 * quantity change as a negative value.
 *
 * The audit log stores quantity_wasted as a positive
 * human-readable value.
 */

logAudit(
    $conn,
    $user,
    "WASTE",
    "INVENTORY",
    "INGREDIENT",
    (int) $ingredient["ingredient_id"],
    "Recorded inventory waste.",
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
            $waste["quantity_before"],

        "quantity_wasted" =>
            abs(
                (float)
                $waste["quantity_change"]
            ),

        "quantity_after" =>
            (float)
            $waste["quantity_after"],

        "reason" =>
            $reason,

        "transaction_id" =>
            (int)
            $waste["transaction_id"],

        "transaction_type" =>
            "WASTE"
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
        "Inventory waste recorded successfully.",

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
            $waste["quantity_before"],

        "quantity_wasted" =>
            abs(
                (float)
                $waste["quantity_change"]
            ),

        "quantity_after" =>
            (float)
            $waste["quantity_after"]
    ],

    "transaction" => [
        "transaction_id" =>
            (int)
            $waste["transaction_id"],

        "transaction_type" =>
            "WASTE",

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