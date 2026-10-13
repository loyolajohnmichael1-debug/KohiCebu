<?php

header("Content-Type: application/json");

require_once "db.php";

/*
 * Only allow POST requests.
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
 * Read Maya webhook payload.
 */
$raw_input = file_get_contents("php://input");

/*
 * Log the raw webhook payload for development/testing.
 *
 * IMPORTANT:
 * Never log API keys or secret credentials.
 */
file_put_contents(
    __DIR__ . "/maya_webhook.log",
    date("Y-m-d H:i:s") .
    " " .
    $raw_input .
    PHP_EOL,
    FILE_APPEND
);

/*
 * Decode JSON.
 */
$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON webhook payload."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Get Maya payment ID
 * ---------------------------------------------------------
 *
 * Maya sends the transaction/checkout identifier
 * in the "id" field.
 */
$maya_payment_id =
    isset($data["id"])
        ? trim($data["id"])
        : "";

if ($maya_payment_id === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Maya payment ID is missing."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Get payment status
 * ---------------------------------------------------------
 *
 * Maya Sandbox may send:
 *
 * "status": "PAYMENT_FAILED"
 *
 * Some documented webhook examples may use:
 *
 * "paymentStatus": "PAYMENT_SUCCESS"
 *
 * Support both formats.
 */
if (isset($data["status"])) {

    $payment_status =
        strtoupper(trim($data["status"]));

} elseif (isset($data["paymentStatus"])) {

    $payment_status =
        strtoupper(trim($data["paymentStatus"]));

} else {

    $payment_status = "";
}

/*
 * Validate payment status.
 */
if ($payment_status === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Maya payment status is missing."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Supported Maya Checkout events
 * ---------------------------------------------------------
 */
$allowed_statuses = [
    "PAYMENT_SUCCESS",
    "PAYMENT_FAILED",
    "PAYMENT_EXPIRED",
    "PAYMENT_CANCELLED"
];

if (!in_array($payment_status, $allowed_statuses, true)) {

    /*
     * Return 200 for events that KohiCebu
     * does not currently process.
     */
    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" =>
            "Webhook received but event is not handled.",
        "payment_status" =>
            $payment_status
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * Find KohiCebu order
 * ---------------------------------------------------------
 *
 * Match the Maya ID against either:
 *
 * maya_checkout_id
 * maya_payment_id
 */
$order_query = "
    SELECT
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        order_source,
        maya_checkout_id,
        maya_payment_id,
        paid_at
    FROM orders
    WHERE
        maya_checkout_id = $1
        OR maya_payment_id = $1
    LIMIT 1
";

$order_result = pg_query_params(
    $conn,
    $order_query,
    [$maya_payment_id]
);

if (!$order_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to find KohiCebu order."
    ]);

    exit;
}

/*
 * No matching order.
 */
if (pg_num_rows($order_result) === 0) {

    /*
     * Log unknown Maya payment ID.
     */
    file_put_contents(
        __DIR__ . "/maya_webhook.log",
        date("Y-m-d H:i:s") .
        " UNKNOWN_PAYMENT_ID: " .
        $maya_payment_id .
        PHP_EOL,
        FILE_APPEND
    );

    /*
     * Acknowledge the webhook.
     */
    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" =>
            "Webhook received, but no matching KohiCebu order was found."
    ]);

    exit;
}

$order = pg_fetch_assoc($order_result);

$order_id =
    (int) $order["order_id"];

/*
 * ---------------------------------------------------------
 * Validate payment amount
 * ---------------------------------------------------------
 *
 * Actual Maya Sandbox payload:
 *
 * "amount": "100",
 * "currency": "PHP"
 *
 * Also support:
 *
 * "totalAmount": {
 *     "value": "100.00",
 *     "currency": "PHP"
 * }
 */

/*
 * Format 1:
 *
 * "amount": "100"
 */
$maya_amount = null;

if (isset($data["amount"])) {

    $maya_amount =
        (float) $data["amount"];
}

/*
 * Format 2:
 *
 * "totalAmount": {
 *     "value": "100.00"
 * }
 */
if (
    $maya_amount === null &&
    isset($data["totalAmount"]) &&
    is_array($data["totalAmount"]) &&
    isset($data["totalAmount"]["value"])
) {

    $maya_amount =
        (float) $data["totalAmount"]["value"];
}

/*
 * If Maya provided an amount,
 * compare it with our database amount.
 */
if ($maya_amount !== null) {

    $order_amount =
        (float) $order["total_amount"];

    /*
     * Allow a very small decimal difference.
     */
    if (
        abs(
            $maya_amount -
            $order_amount
        ) > 0.01
    ) {

        /*
         * Log amount mismatch.
         */
        file_put_contents(
            __DIR__ . "/maya_webhook.log",
            date("Y-m-d H:i:s") .
            " AMOUNT_MISMATCH: Order #" .
            $order_id .
            " Maya=" .
            $maya_amount .
            " DB=" .
            $order_amount .
            PHP_EOL,
            FILE_APPEND
        );

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Payment amount does not match the order amount."
        ]);

        exit;
    }
}

/*
 * ---------------------------------------------------------
 * Validate currency when provided
 * ---------------------------------------------------------
 */
$maya_currency = null;

/*
 * Actual Maya format:
 *
 * "currency": "PHP"
 */
if (isset($data["currency"])) {

    $maya_currency =
        strtoupper(trim($data["currency"]));
}

/*
 * Documented nested format:
 *
 * "totalAmount": {
 *     "currency": "PHP"
 * }
 */
if (
    $maya_currency === null &&
    isset($data["totalAmount"]) &&
    is_array($data["totalAmount"]) &&
    isset($data["totalAmount"]["currency"])
) {

    $maya_currency =
        strtoupper(
            trim(
                $data["totalAmount"]["currency"]
            )
        );
}

/*
 * KohiCebu currently uses PHP.
 */
if (
    $maya_currency !== null &&
    $maya_currency !== "PHP"
) {

    file_put_contents(
        __DIR__ . "/maya_webhook.log",
        date("Y-m-d H:i:s") .
        " CURRENCY_MISMATCH: Order #" .
        $order_id .
        " Currency=" .
        $maya_currency .
        PHP_EOL,
        FILE_APPEND
    );

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Payment currency is not PHP."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * PAYMENT_SUCCESS
 * ---------------------------------------------------------
 */
if ($payment_status === "PAYMENT_SUCCESS") {

    /*
     * If already paid, treat this as a duplicate webhook.
     */
    if ($order["payment_status"] === "PAID") {

        http_response_code(200);

        echo json_encode([
            "success" => true,
            "message" =>
                "Payment was already processed.",
            "order_id" =>
                $order_id,
            "payment_status" =>
                "PAID",
            "order_status" =>
                $order["order_status"]
        ]);

        exit;
    }

    /*
     * Only ONLINE orders should be paid through Maya.
     */
    if ($order["payment_method"] !== "ONLINE") {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Maya payment received for a non-online order."
        ]);

        exit;
    }

    /*
     * Update the order to PAID.
     *
     * UNPAID / FAILED / EXPIRED / CANCELLED
     *          ↓
     *         PAID
     */
    $update_query = "
        UPDATE orders
        SET
            payment_status = 'PAID',
            order_status = 'PAID',
            maya_payment_id = $1,
            paid_at = CURRENT_TIMESTAMP
        WHERE
            order_id = $2
            AND payment_method = 'ONLINE'
            AND payment_status <> 'PAID'
    ";

    $update_result = pg_query_params(
        $conn,
        $update_query,
        [
            $maya_payment_id,
            $order_id
        ]
    );

    if (!$update_result) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to update order payment status."
        ]);

        exit;
    }

    /*
     * Check whether the order was updated.
     */
    if (pg_affected_rows($update_result) === 1) {

        /*
         * Log successful payment.
         */
        file_put_contents(
            __DIR__ . "/maya_webhook.log",
            date("Y-m-d H:i:s") .
            " PAYMENT_SUCCESS: Order #" .
            $order_id .
            PHP_EOL,
            FILE_APPEND
        );

        http_response_code(200);

        echo json_encode([
            "success" => true,
            "message" =>
                "Payment confirmed and order marked as paid.",
            "order_id" =>
                $order_id,
            "payment_status" =>
                "PAID",
            "order_status" =>
                "PAID"
        ]);

        exit;
    }

    /*
     * If another webhook already processed the order,
     * verify the current database state.
     */
    $verify_query = "
        SELECT
            payment_status,
            order_status
        FROM orders
        WHERE order_id = $1
    ";

    $verify_result = pg_query_params(
        $conn,
        $verify_query,
        [$order_id]
    );

    if (
        $verify_result &&
        pg_num_rows($verify_result) === 1
    ) {

        $current_order =
            pg_fetch_assoc($verify_result);

        if (
            $current_order["payment_status"] ===
            "PAID"
        ) {

            http_response_code(200);

            echo json_encode([
                "success" => true,
                "message" =>
                    "Payment was already processed.",
                "order_id" =>
                    $order_id,
                "payment_status" =>
                    "PAID",
                "order_status" =>
                    $current_order["order_status"]
            ]);

            exit;
        }
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Payment was received but order status could not be confirmed."
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * PAYMENT_FAILED
 * ---------------------------------------------------------
 */
if ($payment_status === "PAYMENT_FAILED") {

    /*
     * Record the failed payment.
     *
     * Never change an already-paid order back to FAILED.
     *
     * Also update order_status so the History screen
     * no longer remains on AWAITING_PAYMENT.
     */
    $update_query = "
        UPDATE orders
        SET
            payment_status = 'FAILED',
            order_status = 'PAYMENT_FAILED'
        WHERE
            order_id = $1
            AND payment_status <> 'PAID'
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
                "Failed to record payment failure."
        ]);

        exit;
    }

    /*
     * Log payment failure.
     */
    file_put_contents(
        __DIR__ . "/maya_webhook.log",
        date("Y-m-d H:i:s") .
        " PAYMENT_FAILED: Order #" .
        $order_id .
        PHP_EOL,
        FILE_APPEND
    );

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" =>
            "Payment failure recorded.",
        "order_id" =>
            $order_id,
        "payment_status" =>
            "FAILED",
        "order_status" =>
            "PAYMENT_FAILED"
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * PAYMENT_EXPIRED
 * ---------------------------------------------------------
 */
if ($payment_status === "PAYMENT_EXPIRED") {

    /*
     * Do not change an already-paid order.
     */
    $update_query = "
        UPDATE orders
        SET
            payment_status = 'EXPIRED',
            order_status = 'PAYMENT_EXPIRED'
        WHERE
            order_id = $1
            AND payment_status <> 'PAID'
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
                "Failed to record payment expiration."
        ]);

        exit;
    }

    /*
     * Log expiration.
     */
    file_put_contents(
        __DIR__ . "/maya_webhook.log",
        date("Y-m-d H:i:s") .
        " PAYMENT_EXPIRED: Order #" .
        $order_id .
        PHP_EOL,
        FILE_APPEND
    );

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" =>
            "Payment expiration recorded.",
        "order_id" =>
            $order_id,
        "payment_status" =>
            "EXPIRED",
        "order_status" =>
            "PAYMENT_EXPIRED"
    ]);

    exit;
}

/*
 * ---------------------------------------------------------
 * PAYMENT_CANCELLED
 * ---------------------------------------------------------
 */
if ($payment_status === "PAYMENT_CANCELLED") {

    /*
     * Do not change an already-paid order.
     */
    $update_query = "
        UPDATE orders
        SET
            payment_status = 'CANCELLED',
            order_status = 'PAYMENT_CANCELLED'
        WHERE
            order_id = $1
            AND payment_status <> 'PAID'
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
                "Failed to record payment cancellation."
        ]);

        exit;
    }

    /*
     * Log cancellation.
     */
    file_put_contents(
        __DIR__ . "/maya_webhook.log",
        date("Y-m-d H:i:s") .
        " PAYMENT_CANCELLED: Order #" .
        $order_id .
        PHP_EOL,
        FILE_APPEND
    );

    http_response_code(200);

    echo json_encode([
        "success" => true,
        "message" =>
            "Payment cancellation recorded.",
        "order_id" =>
            $order_id,
        "payment_status" =>
            "CANCELLED",
        "order_status" =>
            "PAYMENT_CANCELLED"
    ]);

    exit;
}

/*
 * Fallback.
 */
http_response_code(200);

echo json_encode([
    "success" => true,
    "message" => "Webhook received."
]);

?>