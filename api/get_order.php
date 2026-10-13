<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

$user_id = (int) $user["user_id"];

// =========================================================
// CHECK ORDER ID
// =========================================================

if (!isset($_GET["order_id"]) || empty($_GET["order_id"])) {

    echo json_encode([
        "success" => false,
        "message" => "Order ID is required."
    ]);

    exit;
}

$order_id = intval($_GET["order_id"]);

if ($order_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid order ID."
    ]);

    exit;
}

// =========================================================
// GET ORDER INFORMATION
// =========================================================
// IMPORTANT:
// The order must belong to the authenticated user.
// We do NOT trust a user_id supplied by the client.

$order_query = "
    SELECT
        o.order_id,
        o.user_id,
        u.full_name,
        u.email,
        u.phone,
        o.total_amount,
        o.order_status,
        o.payment_method,
        o.payment_status,
        o.order_source,
        o.order_date
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

    echo json_encode([
        "success" => false,
        "message" => "Order not found."
    ]);

    exit;
}

$order_row = pg_fetch_assoc($order_result);

// =========================================================
// GET ORDER ITEMS
// =========================================================

$items_query = "
    SELECT
        oi.order_item_id,
        oi.product_id,
        p.product_name,
        p.image_url,
        oi.quantity,
        oi.unit_price,
        (oi.quantity * oi.unit_price) AS subtotal
    FROM order_items oi
    INNER JOIN products p
        ON oi.product_id = p.product_id
    WHERE oi.order_id = $1
    ORDER BY oi.order_item_id
";

$items_result = pg_query_params(
    $conn,
    $items_query,
    [$order_id]
);

if (!$items_result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve order items."
    ]);

    exit;
}

$items = [];

while ($row = pg_fetch_assoc($items_result)) {

    $items[] = [
        "order_item_id" => (int) $row["order_item_id"],
        "product_id" => (int) $row["product_id"],
        "product_name" => $row["product_name"],
        "image_url" => $row["image_url"],
        "quantity" => (int) $row["quantity"],
        "unit_price" => (float) $row["unit_price"],
        "subtotal" => (float) $row["subtotal"]
    ];
}

// =========================================================
// BUILD COMPLETE ORDER RESPONSE
// =========================================================

$order = [
    "order_id" => (int) $order_row["order_id"],
    "user_id" => (int) $order_row["user_id"],
    "customer" => [
        "full_name" => $order_row["full_name"],
        "email" => $order_row["email"],
        "phone" => $order_row["phone"]
    ],
    "total_amount" => (float) $order_row["total_amount"],
    "order_status" => $order_row["order_status"],
    "payment_method" => $order_row["payment_method"],
    "payment_status" => $order_row["payment_status"],
    "order_source" => $order_row["order_source"],
    "order_date" => $order_row["order_date"],
    "items" => $items
];

// =========================================================
// RESPONSE
// =========================================================

echo json_encode([
    "success" => true,
    "message" => "Order retrieved successfully.",
    "order" => $order
]);

?>