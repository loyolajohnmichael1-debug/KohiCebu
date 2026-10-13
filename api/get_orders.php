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
// GET ORDERS FOR AUTHENTICATED USER
// =========================================================

$query = "
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
    WHERE user_id = $1
    ORDER BY order_date DESC
";

$result = pg_query_params(
    $conn,
    $query,
    [$user_id]
);

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve orders."
    ]);

    exit;
}

// =========================================================
// BUILD RESPONSE
// =========================================================

$orders = [];

while ($row = pg_fetch_assoc($result)) {

    $orders[] = [
        "order_id" => (int) $row["order_id"],
        "user_id" => (int) $row["user_id"],
        "total_amount" => (float) $row["total_amount"],
        "order_status" => $row["order_status"],
        "payment_method" => $row["payment_method"],
        "payment_status" => $row["payment_status"],
        "order_source" => $row["order_source"],
        "order_date" => $row["order_date"]
    ];
}

// =========================================================
// RESPONSE
// =========================================================

echo json_encode([
    "success" => true,
    "message" => "Orders retrieved successfully.",
    "orders" => $orders
]);

?>