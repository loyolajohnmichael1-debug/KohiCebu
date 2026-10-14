<?php

header("Content-Type: application/json");

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/audit.php";

use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\Notification;


// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

// Only STAFF and ADMIN can update order status.
requireRole($user, ["STAFF", "ADMIN"]);

$staff_user_id = (int) $user["user_id"];


// =========================================================
// REQUEST METHOD
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

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
    !isset($data["order_status"])
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Order ID and order status are required."
    ]);

    exit;
}

$order_id =
    intval($data["order_id"]);

$order_status =
    strtoupper(trim($data["order_status"]));


// =========================================================
// VALIDATE ORDER ID
// =========================================================

if ($order_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid order ID."
    ]);

    exit;
}


// =========================================================
// NORMALIZE STATUS
// =========================================================

$status_map = [

    "PENDING" =>
        "Pending",

    "CONFIRMED" =>
        "Confirmed",

    "PAID" =>
        "PAID",

    "PREPARING" =>
        "Preparing",

    "READY" =>
        "Ready",

    "COMPLETED" =>
        "Completed",

    "CANCELLED" =>
        "Cancelled"
];

if (!isset($status_map[$order_status])) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid order status."
    ]);

    exit;
}

$new_status =
    $status_map[$order_status];


// =========================================================
// GET CURRENT ORDER
// =========================================================

$order_query = "
    SELECT
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        order_source,
        order_date
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

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to verify order."
    ]);

    exit;
}

if (pg_num_rows($order_result) === 0) {

    echo json_encode([
        "success" => false,
        "message" => "Order not found."
    ]);

    exit;
}

$current_order =
    pg_fetch_assoc($order_result);

$current_status =
    $current_order["order_status"];

$payment_status =
    $current_order["payment_status"];

$customer_user_id =
    (int) $current_order["user_id"];


// =========================================================
// PROTECT PAYMENT STATUS
// =========================================================

if (
    $new_status === "Completed"
    &&
    $payment_status !== "PAID"
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "An order must be paid before it can be completed."
    ]);

    exit;
}


// =========================================================
// PREVENT CHANGING A PAID ORDER BACK TO PAYMENT STATE
// =========================================================

if (
    $current_status === "PAID" &&
    $new_status === "Pending"
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "A paid order cannot be moved back to Pending."
    ]);

    exit;
}


// =========================================================
// ORDER STATUS TRANSITIONS
// =========================================================

$allowed_transitions = [

    "Pending" => [
        "Confirmed",
        "Cancelled"
    ],

    "Confirmed" => [
        "Preparing",
        "Cancelled"
    ],

    "PAID" => [
        "Preparing"
    ],

    "Preparing" => [
        "Ready"
    ],

    "Ready" => [
        "Completed"
    ],

    "AWAITING_PAYMENT" => [
        "Cancelled"
    ]
];


// =========================================================
// CHECK TRANSITION
// =========================================================

if (
    !isset(
        $allowed_transitions[$current_status]
    )
    ||
    !in_array(
        $new_status,
        $allowed_transitions[$current_status],
        true
    )
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid order status transition from '" .
            $current_status .
            "' to '" .
            $new_status .
            "'."
    ]);

    exit;
}


// =========================================================
// START TRANSACTION
// =========================================================
//
// Order update + inventory reversal + database
// notification are atomic.
//
// Firebase push is intentionally sent AFTER COMMIT.
// A temporary Firebase problem must not undo a valid
// order status change.
//

if (!pg_query($conn, "BEGIN")) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to start database transaction."
    ]);

    exit;
}


// =========================================================
// INVENTORY REVERSAL
// =========================================================
//
// Only unpaid AWAITING_PAYMENT orders can release
// reserved recipe inventory.
//
// PAID orders are intentionally excluded.
// Paid-order cancellation/refund will require a
// separate financial workflow.
//
// Legacy orders without ORDER_DEDUCTION transactions
// are also left untouched.
//

$inventory_reversed = false;
$reversal_transaction_count = 0;

if (
    $new_status === "Cancelled"
    &&
    $current_status === "AWAITING_PAYMENT"
    &&
    $payment_status === "UNPAID"
) {

    // -----------------------------------------------------
    // Check whether this order has recipe deductions.
    // -----------------------------------------------------

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

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to verify reserved inventory."
        ]);

        exit;
    }

    $deduction_check =
        pg_fetch_assoc(
            $deduction_check_result
        );

    $has_recipe_deduction =
        ($deduction_check["has_deduction"] === "t");


    // -----------------------------------------------------
    // Reverse recipe inventory if this order reserved it.
    // -----------------------------------------------------

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
                    "Release reserved inventory for cancelled unpaid order."
                ]
            );

        if (!$reversal_result) {

            pg_query($conn, "ROLLBACK");

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
}


// =========================================================
// UPDATE ORDER
// =========================================================

$update_query = "
    UPDATE orders
    SET order_status = $1
    WHERE order_id = $2
    RETURNING
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        order_source,
        order_date
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        $new_status,
        $order_id
    ]
);

if (
    !$update_result ||
    pg_num_rows($update_result) === 0
) {

    pg_query($conn, "ROLLBACK");

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to update order status."
    ]);

    exit;
}

$updated_order =
    pg_fetch_assoc($update_result);


// =========================================================
// NOTIFICATION LIFECYCLE
// =========================================================
//
// READY:
//     Create a new ORDER_READY notification.
//
// COMPLETED:
//     Resolve the existing ORDER_READY notification.
//
// The database notification remains the source of
// record even if Firebase delivery temporarily fails.
//

$notification_created = false;
$notification_resolved = false;

$notification_id = null;


// =========================================================
// CREATE READY NOTIFICATION
// =========================================================

if ($new_status === "Ready") {

    $notification_query = "
        INSERT INTO notifications (
            user_id,
            order_id,
            title,
            message,
            notification_type,
            is_read,
            is_resolved
        )
        VALUES (
            $1,
            $2,
            $3,
            $4,
            $5,
            FALSE,
            FALSE
        )
        RETURNING notification_id
    ";

    $notification_result =
        pg_query_params(
            $conn,
            $notification_query,
            [
                $customer_user_id,
                $order_id,
                "Order Ready!",
                "Your Order #" .
                    $order_id .
                    " is ready for pickup. Please proceed to the counter.",
                "ORDER_READY"
            ]
        );

    if (
        !$notification_result ||
        pg_num_rows($notification_result) === 0
    ) {

        pg_query($conn, "ROLLBACK");

        echo json_encode([
            "success" => false,
            "message" =>
                "Order status could not be updated because the customer notification could not be created."
        ]);

        exit;
    }

    $notification_row =
        pg_fetch_assoc(
            $notification_result
        );

    $notification_id =
        (int) $notification_row["notification_id"];

    $notification_created = true;
}


// =========================================================
// RESOLVE READY NOTIFICATION WHEN COMPLETED
// =========================================================

if ($new_status === "Completed") {

    $resolve_notification_query = "
        UPDATE notifications
        SET is_resolved = TRUE
        WHERE order_id = $1
          AND user_id = $2
          AND notification_type = 'ORDER_READY'
          AND is_resolved = FALSE
    ";

    $resolve_notification_result =
        pg_query_params(
            $conn,
            $resolve_notification_query,
            [
                $order_id,
                $customer_user_id
            ]
        );

    if (!$resolve_notification_result) {

        pg_query($conn, "ROLLBACK");

        echo json_encode([
            "success" => false,
            "message" =>
                "Order status could not be completed because the customer notification could not be resolved."
        ]);

        exit;
    }

    $notification_resolved =
        pg_affected_rows(
            $resolve_notification_result
        ) > 0;
}


// =========================================================
// COMMIT TRANSACTION
// =========================================================

if (!pg_query($conn, "COMMIT")) {

    pg_query($conn, "ROLLBACK");

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to complete the order status update."
    ]);

    exit;
}


// =========================================================
// AUDIT LOG
// =========================================================
//
// The order status change has successfully committed.
// Record the business event after COMMIT so the audit
// trail does not contain a false status change if the
// database transaction rolls back.
//

$auditAction =
    $new_status === "Cancelled"
        ? "CANCEL"
        : "STATUS_UPDATE";

$auditDescription =
    $new_status === "Cancelled"
        ? "Cancelled an order."
        : "Updated order status.";

logAudit(
    $conn,
    $user,
    $auditAction,
    "ORDERS",
    "ORDER",
    $order_id,
    $auditDescription,
    [
        "order_id" =>
            $order_id,

        "customer_user_id" =>
            $customer_user_id,

        "total_amount" =>
            (float)
            $updated_order["total_amount"],

        "payment_method" =>
            $updated_order["payment_method"],

        "payment_status" =>
            $updated_order["payment_status"],

        "order_source" =>
            $updated_order["order_source"],

        "previous_status" =>
            $current_status,

        "new_status" =>
            $new_status,

        "inventory_reversed" =>
            $inventory_reversed,

        "reversal_transaction_count" =>
            $reversal_transaction_count,

        "notification_created" =>
            $notification_created,

        "notification_id" =>
            $notification_id,

        "notification_resolved" =>
            $notification_resolved

    ]
);


// =========================================================
// FORMAT RESPONSE
// =========================================================

$updated_order["order_id"] =
    (int) $updated_order["order_id"];

$updated_order["user_id"] =
    (int) $updated_order["user_id"];

$updated_order["total_amount"] =
    (float) $updated_order["total_amount"];


// =========================================================
// FIREBASE PUSH NOTIFICATION
// =========================================================
//
// Only send FCM when the order becomes READY.
//
// The database transaction has already committed,
// so Firebase failure will NOT roll back the order.
//
// The customer's latest registered FCM token is used.
//

$fcm_sent = false;
$fcm_error = null;

if (
    $new_status === "Ready"
    &&
    $notification_created
) {

    try {

        // -----------------------------------------------------
        // Get customer's latest FCM token
        // -----------------------------------------------------

        $fcm_token_query = "
            SELECT
                fcm_token
            FROM user_fcm_tokens
            WHERE user_id = $1
            ORDER BY updated_at DESC
            LIMIT 1
        ";

        $fcm_token_result =
            pg_query_params(
                $conn,
                $fcm_token_query,
                [$customer_user_id]
            );

        if (
            $fcm_token_result
            &&
            pg_num_rows($fcm_token_result) > 0
        ) {

            $fcm_token_row =
                pg_fetch_assoc(
                    $fcm_token_result
                );

            $fcm_token =
                trim(
                    $fcm_token_row["fcm_token"]
                );

            if ($fcm_token !== "") {

                // Firebase is optional for the order update itself. Load
                // its credentials only when a push notification is needed;
                // firebase.php can throw when credentials are unavailable.
                require_once __DIR__ . "/firebase.php";

                // -------------------------------------------------
                // Build Firebase notification
                // -------------------------------------------------

                $notification =
                    Notification::create(
                        "Order Ready!",
                        "Your Order #" .
                            $order_id .
                            " is ready for pickup. Please proceed to the counter."
                    );

                
                        $androidConfig = AndroidConfig::fromArray([
                            "priority" => "high",
                            "notification" => [
                                "channel_id" => "kohicebu_orders_v3",
                                "sound" => "default"
                            ]
                        ]);
$message =
                    CloudMessage::new()
                        ->toToken($fcm_token)
                        ->withNotification(
                            $notification
                        )
                        ->withAndroidConfig($androidConfig)
                        ->withData([
                            "notification_type" =>
                                "ORDER_READY",

                            "order_id" =>
                                (string) $order_id,

                            "notification_id" =>
                                (string) $notification_id,

                            "message" =>
                                "Your Order #" .
                                $order_id .
                                " is ready for pickup."
                        ]);

                $messaging->send(
                    $message
                );

                $fcm_sent = true;

            } else {

                $fcm_error =
                    "Customer FCM token is empty.";
            }

        } else {

            $fcm_error =
                "No registered FCM token found for this customer.";
        }

    } catch (Throwable $e) {

        $fcm_error =
            $e->getMessage();

        error_log(
            "KohiCebu FCM ORDER_READY error: " .
            $e->getMessage()
        );
    }
}


// =========================================================
// RESPONSE
// =========================================================

$response = [
    "success" => true,

    "message" =>
        "Order status updated successfully.",

    "updated_by" => [
        "user_id" =>
            $staff_user_id,

        "role" =>
            $user["role"]
    ],

    "order" =>
        $updated_order,

    "inventory" => [
        "reversed" =>
            $inventory_reversed,

        "reversal_transaction_count" =>
            $reversal_transaction_count
    ]
];


// =========================================================
// NOTIFICATION RESPONSE
// =========================================================

if ($notification_created) {

    $response["notification"] = [
        "created" => true,
        "type" => "ORDER_READY",
        "notification_id" =>
            $notification_id,
        "fcm_sent" =>
            $fcm_sent
    ];

    if (!$fcm_sent) {

        $response["notification"]["fcm_error"] =
            $fcm_error;
    }
}

if ($new_status === "Completed") {

    $response["notification"] = [
        "resolved" =>
            $notification_resolved,

        "type" =>
            "ORDER_READY"
    ];
}


echo json_encode($response);

?>
