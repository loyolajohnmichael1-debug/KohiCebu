<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

/*
 * Authenticate the customer.
 *
 * The authenticated user ID comes from the Bearer token.
 */
$user = requireAuth($conn);

$user_id = (int) $user["user_id"];

/*
 * Load Maya configuration
 *
 * IMPORTANT:
 * This file is outside the public htdocs directory.
 */
$maya_config = require_once "D:/Xampp/secure/maya.php";

$maya_public_key = $maya_config["public_key"];
$maya_base_url = rtrim($maya_config["base_url"], "/");

/*
 * Only allow POST
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
 * Read JSON body
 */
$raw_input = file_get_contents("php://input");

$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);

    exit;
}

/*
 * Validate order ID
 */
if (!isset($data["order_id"])) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Order ID is required."
    ]);

    exit;
}

$order_id = intval($data["order_id"]);

if ($order_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid order ID."
    ]);

    exit;
}

/*
 * Get order information.
 *
 * IMPORTANT:
 * The order must belong to the authenticated user.
 *
 * We intentionally do NOT accept a user_id
 * from the client.
 */
$order_query = "
    SELECT
        o.order_id,
        o.user_id,
        o.total_amount,
        o.order_status,
        o.payment_method,
        o.payment_status,
        o.order_source,

        u.full_name,
        u.email,
        u.phone

    FROM orders o

    INNER JOIN users u
        ON o.user_id = u.user_id

    WHERE o.order_id = $1
      AND o.user_id = $2
";

$order_result = pg_query_params(
    $conn,
    $order_query,
    [
        $order_id,
        $user_id
    ]
);

if (
    !$order_result ||
    pg_num_rows($order_result) === 0
) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Order not found."
    ]);

    exit;
}

$order = pg_fetch_assoc($order_result);

/*
 * Only ONLINE payment can create Maya Checkout.
 */
if ($order["payment_method"] !== "ONLINE") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "This order is not configured for online payment."
    ]);

    exit;
}

/*
 * Do not create another checkout for an already-paid order.
 */
if ($order["payment_status"] === "PAID") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "This order has already been paid."
    ]);

    exit;
}

/*
 * Get order items
 */
$items_query = "
    SELECT
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        p.product_name

    FROM order_items oi

    INNER JOIN products p
        ON oi.product_id = p.product_id

    WHERE oi.order_id = $1
";

$items_result = pg_query_params(
    $conn,
    $items_query,
    [$order_id]
);

if (!$items_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to retrieve order items."
    ]);

    exit;
}

$items = [];

while ($item = pg_fetch_assoc($items_result)) {

    $quantity = intval($item["quantity"]);
    $unit_price = (float) $item["unit_price"];

    $items[] = [
        "name" => $item["product_name"],
        "code" =>
            "PRODUCT-" .
            $item["product_id"],
        "description" =>
            $item["product_name"],
        "quantity" =>
            $quantity,
        "amount" => [
            "value" =>
                number_format(
                    $unit_price,
                    2,
                    ".",
                    ""
                ),
            "currency" => "PHP"
        ],
        "totalAmount" => [
            "value" =>
                number_format(
                    $unit_price * $quantity,
                    2,
                    ".",
                    ""
                ),
            "currency" => "PHP"
        ]
    ];
}

/*
 * Public HTTPS URLs.
 *
 * These currently use your active ngrok tunnel.
 *
 * IMPORTANT:
 * If ngrok generates a new URL later,
 * update these URLs.
 */
$public_base_url =
    "https://compile-carnation-collected.ngrok-free.dev";

$redirect_url = [

    "success" =>
        $public_base_url .
        "/KohiCebu/api/maya_success.php",

    "failure" =>
        $public_base_url .
        "/KohiCebu/api/maya_failure.php",

    "cancel" =>
        $public_base_url .
        "/KohiCebu/api/maya_cancel.php"
];

/*
 * Maya Checkout request
 */
$checkout_data = [

    "totalAmount" => [
        "value" =>
            number_format(
                (float) $order["total_amount"],
                2,
                ".",
                ""
            ),
        "currency" => "PHP"
    ],

    "buyer" => [

        "firstName" =>
            $order["full_name"],

        "contact" => [

            "email" =>
                $order["email"],

            "phone" =>
                $order["phone"]
        ]
    ],

    "items" =>
        $items,

    "redirectUrl" =>
        $redirect_url,

    "requestReferenceNumber" =>
        "KOHICEBU-ORDER-" .
        $order_id,

    "metadata" => [

        "orderId" =>
            (string) $order_id,

        "userId" =>
            (string) $order["user_id"],

        "orderSource" =>
            $order["order_source"]
    ]
];

/*
 * Convert request to JSON
 */
$json_body = json_encode(
    $checkout_data
);

if ($json_body === false) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to encode Maya request."
    ]);

    exit;
}

/*
 * Call Maya Checkout API
 *
 * Create Checkout uses the PUBLIC API KEY
 * for Basic Authentication.
 */
$ch = curl_init(
    $maya_base_url .
    "/checkout/v1/checkouts"
);

curl_setopt_array(
    $ch,
    [

        CURLOPT_POST => true,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [

            "Content-Type: application/json",

            "Accept: application/json"
        ],

        CURLOPT_USERPWD =>
            $maya_public_key . ":",

        CURLOPT_POSTFIELDS =>
            $json_body,

        CURLOPT_TIMEOUT => 30
    ]
);

$maya_response =
    curl_exec($ch);

$http_code =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

$curl_error =
    curl_error($ch);

curl_close($ch);

/*
 * Handle connection errors
 */
if (
    $maya_response === false ||
    $curl_error
) {

    http_response_code(502);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to connect to Maya.",
        "error" =>
            $curl_error
    ]);

    exit;
}

/*
 * Decode Maya response
 */
$maya_data =
    json_decode(
        $maya_response,
        true
    );

/*
 * Handle Maya API errors
 */
if (
    $http_code < 200 ||
    $http_code >= 300
) {

    http_response_code(
        $http_code
    );

    echo json_encode([
        "success" => false,
        "message" =>
            "Maya Checkout creation failed.",
        "maya_response" =>
            $maya_data
    ]);

    exit;
}

/*
 * Extract Maya checkout identifiers
 */
$maya_checkout_id =
    $maya_data["checkoutId"] ?? null;

$maya_payment_id =
    $maya_data["paymentId"] ?? null;

/*
 * Make sure Maya returned a checkout ID.
 */
if (!$maya_checkout_id) {

    http_response_code(502);

    echo json_encode([
        "success" => false,
        "message" =>
            "Maya did not return a checkout ID."
    ]);

    exit;
}

/*
 * Save Maya checkout information to the order.
 *
 * IMPORTANT:
 * Creating a checkout does NOT mean
 * the payment was successful.
 *
 * The order remains:
 *
 * payment_status = UNPAID
 * order_status   = AWAITING_PAYMENT
 *
 * until the Maya webhook confirms payment.
 */
$update_query = "
    UPDATE orders
    SET
        maya_checkout_id = $1,
        maya_payment_id = $2
    WHERE order_id = $3
      AND user_id = $4
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        $maya_checkout_id,
        $maya_payment_id,
        $order_id,
        $user_id
    ]
);

/*
 * Make sure the order was updated.
 */
if (
    !$update_result ||
    pg_affected_rows($update_result) !== 1
) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Maya Checkout was created, but the checkout information could not be saved."
    ]);

    exit;
}

/*
 * Successful checkout creation
 */
echo json_encode([

    "success" => true,

    "message" =>
        "Maya Checkout created successfully.",

    "order_id" =>
        $order_id,

    "maya_checkout_id" =>
        $maya_checkout_id,

    "maya_payment_id" =>
        $maya_payment_id,

    "checkout" =>
        $maya_data

], JSON_PRETTY_PRINT);

?>