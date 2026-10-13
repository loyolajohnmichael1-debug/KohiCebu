<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";
require_once "audit.php";


/*
 * ---------------------------------------------------------
 * Authentication
 * ---------------------------------------------------------
 *
 * Only authenticated users can confirm cash payments.
 */
$user = requireAuth($conn);


/*
 * ---------------------------------------------------------
 * Authorization
 * ---------------------------------------------------------
 *
 * Only STAFF and ADMIN accounts can confirm
 * cashier payments.
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
 * Validate order ID
 * ---------------------------------------------------------
 */
$order_id =
    isset($data["order_id"])
        ? (int) $data["order_id"]
        : 0;

if ($order_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Valid order ID is required."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Find the order
 * ---------------------------------------------------------
 */
$order_query = "
    SELECT
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        paid_at
    FROM orders
    WHERE order_id = $1
    LIMIT 1
";

$order_result = pg_query_params(
    $conn,
    $order_query,
    [$order_id]
);

if (!$order_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to find order."
    ]);

    exit;
}


/*
 * Order does not exist.
 */
if (pg_num_rows($order_result) === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Order not found."
    ]);

    exit;
}

$order =
    pg_fetch_assoc(
        $order_result
    );


/*
 * ---------------------------------------------------------
 * Verify payment method
 * ---------------------------------------------------------
 *
 * This endpoint is ONLY for cashier payments.
 *
 * It must never be used to manually mark
 * an ONLINE/Maya order as paid.
 */
if (
    strtoupper(
        trim(
            $order["payment_method"] ?? ""
        )
    ) !== "CASHIER"
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Only CASHIER orders can be confirmed through this endpoint."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Prevent duplicate payment confirmation
 * ---------------------------------------------------------
 */
if (
    strtoupper(
        trim(
            $order["payment_status"] ?? ""
        )
    ) === "PAID"
) {

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" =>
            "Order payment was already confirmed.",
        "order_id" =>
            $order_id,
        "payment_status" =>
            "PAID",
        "order_status" =>
            $order["order_status"],
        "confirmed_by" =>
            (int) $user["user_id"],
        "confirmed_by_role" =>
            $user["role"],
        "paid_at" =>
            $order["paid_at"]
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Validate current order/payment state
 * ---------------------------------------------------------
 *
 * A normal cashier order should be:
 *
 * AWAITING_PAYMENT + UNPAID
 *
 * Do not allow arbitrary payment-state changes.
 */
$current_payment_status =
    strtoupper(
        trim(
            $order["payment_status"] ?? ""
        )
    );

$current_order_status =
    strtoupper(
        trim(
            $order["order_status"] ?? ""
        )
    );

if (
    $current_payment_status !== "UNPAID"
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order cannot be confirmed because its current payment status is '" .
            $order["payment_status"] .
            "'."
    ]);

    exit;
}

if (
    $current_order_status !== "AWAITING_PAYMENT"
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order cannot be confirmed because its current order status is '" .
            $order["order_status"] .
            "'."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Preserve previous state for audit
 * ---------------------------------------------------------
 */
$previous_payment_status =
    $order["payment_status"];

$previous_order_status =
    $order["order_status"];


/*
 * ---------------------------------------------------------
 * Confirm cashier payment
 * ---------------------------------------------------------
 *
 * Only the authenticated STAFF/ADMIN account
 * performing this request is allowed to be recorded
 * as the confirming user.
 */
$update_query = "
    UPDATE orders
    SET
        payment_status = 'PAID',
        order_status = 'PAID',
        paid_at = CURRENT_TIMESTAMP
    WHERE
        order_id = $1
        AND payment_method = 'CASHIER'
        AND payment_status = 'UNPAID'
        AND order_status = 'AWAITING_PAYMENT'
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [$order_id]
);

if (!$update_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to confirm cashier payment."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Verify update
 * ---------------------------------------------------------
 */
if (
    pg_affected_rows(
        $update_result
    ) !== 1
) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "Payment could not be confirmed because the order state changed."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Return updated order information
 * ---------------------------------------------------------
 */
$updated_query = "
    SELECT
        order_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        paid_at
    FROM orders
    WHERE order_id = $1
    LIMIT 1
";

$updated_result = pg_query_params(
    $conn,
    $updated_query,
    [$order_id]
);

if (
    !$updated_result ||
    pg_num_rows($updated_result) === 0
) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Payment was confirmed, but the updated order could not be retrieved."
    ]);

    exit;
}

$updated_order =
    pg_fetch_assoc(
        $updated_result
    );


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 *
 * The payment and order status have successfully
 * changed from:
 *
 * AWAITING_PAYMENT + UNPAID
 *
 * to:
 *
 * PAID + PAID
 *
 * Record the payment confirmation event.
 */
logAudit(
    $conn,
    $user,
    "PAYMENT_CONFIRMED",
    "ORDERS",
    "ORDER",
    $order_id,
    "Confirmed cashier payment for an order.",
    [
        "order_id" =>
            $order_id,

        "total_amount" =>
            (float)
            $updated_order["total_amount"],

        "payment_method" =>
            $updated_order["payment_method"],

        "previous_payment_status" =>
            $previous_payment_status,

        "new_payment_status" =>
            $updated_order["payment_status"],

        "previous_order_status" =>
            $previous_order_status,

        "new_order_status" =>
            $updated_order["order_status"],

        "paid_at" =>
            $updated_order["paid_at"],

        "confirmed_by_user_id" =>
            (int) $user["user_id"],

        "confirmed_by_role" =>
            $user["role"]
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
        "Cashier payment confirmed successfully.",

    "order" => [
        "order_id" =>
            (int)
            $updated_order["order_id"],

        "total_amount" =>
            $updated_order["total_amount"],

        "order_status" =>
            $updated_order["order_status"],

        "payment_method" =>
            $updated_order["payment_method"],

        "payment_status" =>
            $updated_order["payment_status"],

        "paid_at" =>
            $updated_order["paid_at"]
    ],

    "confirmed_by" =>
        (int) $user["user_id"],

    "confirmed_by_role" =>
        $user["role"]
]);

?>