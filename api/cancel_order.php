<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

// Only STAFF and ADMIN can cancel orders.
requireRole($user, ["STAFF", "ADMIN"]);

$staff_user_id = (int) $user["user_id"];
$staff_role = $user["role"];

// =========================================================
// REQUEST METHOD
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

// =========================================================
// READ JSON REQUEST
// =========================================================

$raw_input = file_get_contents("php://input");

$data = json_decode(
    $raw_input,
    true
);

if (json_last_error() !== JSON_ERROR_NONE) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);

    exit;
}

// =========================================================
// VALIDATE REQUIRED FIELDS
// =========================================================

if (
    !isset($data["order_id"]) ||
    !isset($data["reason"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order ID and cancellation reason are required."
    ]);

    exit;
}

$order_id =
    intval($data["order_id"]);

$reason =
    trim((string) $data["reason"]);

// =========================================================
// VALIDATE ORDER ID
// =========================================================

if ($order_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid order ID."
    ]);

    exit;
}

// =========================================================
// VALIDATE CANCELLATION REASON
// =========================================================

if ($reason === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Cancellation reason is required."
    ]);

    exit;
}

// Prevent unnecessarily large audit-log entries.
if (mb_strlen($reason) > 1000) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Cancellation reason must not exceed 1000 characters."
    ]);

    exit;
}

// =========================================================
// START TRANSACTION
// =========================================================
//
// Everything related to cancellation must succeed together:
//
// - order validation
// - inventory reversal
// - order status update
// - payment status update
// - audit log
//
// If anything fails, everything is rolled back.
//

if (!pg_query($conn, "BEGIN")) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to start database transaction."
    ]);

    exit;
}

// =========================================================
// VERIFY ACTIVE SHIFT
// =========================================================
//
// STAFF users must have an active shift.
//
// ADMIN users are allowed to perform administrative
// cancellation without being tied to a cashier shift.
//

if ($staff_role === "STAFF") {

    $active_shift_query = "
        SELECT
            shift_id
        FROM staff_shifts
        WHERE user_id = $1
          AND status = 'OPEN'
        ORDER BY started_at DESC
        LIMIT 1
        FOR UPDATE
    ";

    $active_shift_result =
        pg_query_params(
            $conn,
            $active_shift_query,
            [$staff_user_id]
        );

    if (!$active_shift_result) {

        pg_query($conn, "ROLLBACK");

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to verify your active shift."
        ]);

        exit;
    }

    if (pg_num_rows($active_shift_result) === 0) {

        pg_query($conn, "ROLLBACK");

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" =>
                "You must have an active shift before cancelling an order."
        ]);

        exit;
    }
}

// =========================================================
// GET AND LOCK ORDER
// =========================================================
//
// FOR UPDATE prevents two staff actions from modifying
// the same order simultaneously.
//

$order_query = "
    SELECT
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        order_source,
        order_date,
        shift_id
    FROM orders
    WHERE order_id = $1
    FOR UPDATE
";

$order_result =
    pg_query_params(
        $conn,
        $order_query,
        [$order_id]
    );

if (!$order_result) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to verify order."
    ]);

    exit;
}

if (pg_num_rows($order_result) === 0) {

    pg_query($conn, "ROLLBACK");

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order not found."
    ]);

    exit;
}

$current_order =
    pg_fetch_assoc($order_result);

$current_status =
    $current_order["order_status"];

$current_payment_status =
    $current_order["payment_status"];

$customer_user_id =
    $current_order["user_id"] !== null
        ? (int) $current_order["user_id"]
        : null;

// =========================================================
// PREVENT DOUBLE CANCELLATION
// =========================================================

if (
    strtoupper(
        trim($current_status)
    ) === "CANCELLED"
    ||
    strtoupper(
        trim($current_status)
    ) === "CANCELED"
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "This order has already been cancelled."
    ]);

    exit;
}

// =========================================================
// PROTECT PAID ORDERS
// =========================================================
//
// A paid order must NOT be cancelled through this endpoint.
//
// Paid-order cancellation requires a separate refund/
// financial workflow.
//

if (
    strtoupper(
        trim($current_payment_status)
    ) === "PAID"
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "Paid orders cannot be cancelled using this process. A separate refund workflow is required."
    ]);

    exit;
}

// =========================================================
// VALIDATE CANCELLATION STATUS
// =========================================================
//
// Current supported cancellable states:
//
// AWAITING_PAYMENT
// Pending
// Confirmed
//
// We intentionally do not allow cancellation once the order
// has entered PAID, Preparing, Ready, or Completed.
//

$cancellable_statuses = [
    "AWAITING_PAYMENT",
    "Pending",
    "Confirmed"
];

if (
    !in_array(
        $current_status,
        $cancellable_statuses,
        true
    )
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order cannot be cancelled from its current status '" .
            $current_status .
            "'."
    ]);

    exit;
}

// =========================================================
// VERIFY PAYMENT STATE
// =========================================================
//
// This endpoint is specifically for cancellation without
// a financial refund.
//
// Only UNPAID orders are allowed.
//

if (
    strtoupper(
        trim($current_payment_status)
    ) !== "UNPAID"
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "Only unpaid orders can be cancelled using this process."
    ]);

    exit;
}

// =========================================================
// CHECK INVENTORY DEDUCTIONS
// =========================================================

$deduction_check_query = "
    SELECT EXISTS (
        SELECT 1
        FROM inventory_transactions
        WHERE order_id = $1
          AND transaction_type = 'ORDER_DEDUCTION'
    ) AS has_deduction
";

$deduction_check_result =
    pg_query_params(
        $conn,
        $deduction_check_query,
        [$order_id]
    );

if (!$deduction_check_result) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to verify order inventory."
    ]);

    exit;
}

$deduction_check =
    pg_fetch_assoc(
        $deduction_check_result
    );

$has_recipe_deduction =
    ($deduction_check["has_deduction"] === "t");

// =========================================================
// REVERSE INVENTORY
// =========================================================
//
// If this order deducted recipe inventory, release it.
//
// The database function itself:
//
// - locks the order
// - verifies it is unpaid
// - verifies deductions exist
// - prevents double reversal
// - creates REVERSAL transactions
// - records the staff user
// - records the reason
//
// Because we are already inside a transaction, any failure
// causes the complete cancellation to roll back.
//

$inventory_reversed = false;
$reversal_transaction_count = 0;

if ($has_recipe_deduction) {

    $reversal_query = "
        SELECT *
        FROM reverse_order_recipe_inventory(
            $1,
            $2,
            $3
        )
    ";

    $reversal_result =
        pg_query_params(
            $conn,
            $reversal_query,
            [
                $order_id,
                $staff_user_id,
                $reason
            ]
        );

    if (!$reversal_result) {

        pg_query($conn, "ROLLBACK");

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Order could not be cancelled because reserved inventory could not be released."
        ]);

        exit;
    }

    $reversal_transaction_count =
        pg_num_rows(
            $reversal_result
        );

    $inventory_reversed =
        $reversal_transaction_count > 0;
}

// =========================================================
// UPDATE ORDER
// =========================================================
//
// Keep the existing KohiCebu status naming convention:
// "Cancelled"
//
// Unpaid cancellation also changes payment_status to
// "CANCELLED".
//

$update_query = "
    UPDATE orders
    SET
        order_status = 'Cancelled',
        payment_status = 'CANCELLED'
    WHERE order_id = $1
    RETURNING
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        order_source,
        order_date,
        shift_id
";

$update_result =
    pg_query_params(
        $conn,
        $update_query,
        [$order_id]
    );

if (
    !$update_result ||
    pg_num_rows($update_result) === 0
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to cancel order."
    ]);

    exit;
}

$updated_order =
    pg_fetch_assoc(
        $update_result
    );

// =========================================================
// RECORD ACTION AUDIT
// =========================================================
//
// This is the audit trail we created in Step 1.
//
// It records:
//
// - which order
// - which staff member
// - what action
// - why
// - previous status
// - new status
// - previous payment state
// - new payment state
//

$action_log_query = "
    INSERT INTO order_action_logs (
        order_id,
        user_id,
        action_type,
        reason,
        previous_order_status,
        new_order_status,
        previous_payment_status,
        new_payment_status
    )
    VALUES (
        $1,
        $2,
        'CANCEL',
        $3,
        $4,
        $5,
        $6,
        $7
    )
    RETURNING
        action_id,
        created_at
";

$action_log_result =
    pg_query_params(
        $conn,
        $action_log_query,
        [
            $order_id,
            $staff_user_id,
            $reason,
            $current_status,
            "Cancelled",
            $current_payment_status,
            "CANCELLED"
        ]
    );

if (
    !$action_log_result ||
    pg_num_rows($action_log_result) === 0
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order could not be cancelled because the audit record could not be created."
    ]);

    exit;
}

$action_log =
    pg_fetch_assoc(
        $action_log_result
    );

// =========================================================
// COMMIT
// =========================================================

if (!pg_query($conn, "COMMIT")) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to complete order cancellation."
    ]);

    exit;
}

// =========================================================
// FORMAT RESPONSE
// =========================================================

$updated_order["order_id"] =
    (int) $updated_order["order_id"];

$updated_order["user_id"] =
    $updated_order["user_id"] !== null
        ? (int) $updated_order["user_id"]
        : null;

$updated_order["total_amount"] =
    (float) $updated_order["total_amount"];

$updated_order["shift_id"] =
    $updated_order["shift_id"] !== null
        ? (int) $updated_order["shift_id"]
        : null;

$action_log["action_id"] =
    (int) $action_log["action_id"];

// =========================================================
// SUCCESS RESPONSE
// =========================================================

echo json_encode([
    "success" => true,

    "message" =>
        "Order cancelled successfully.",

    "cancelled_by" => [
        "user_id" =>
            $staff_user_id,

        "role" =>
            $staff_role
    ],

    "order" =>
        $updated_order,

    "cancellation" => [
        "reason" =>
            $reason,

        "action_id" =>
            $action_log["action_id"],

        "created_at" =>
            $action_log["created_at"]
    ],

    "inventory" => [
        "reversed" =>
            $inventory_reversed,

        "reversal_transaction_count" =>
            $reversal_transaction_count
    ]
]);

?>