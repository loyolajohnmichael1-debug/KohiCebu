<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";


/*
 * =========================================================
 * KOHICEBU — ADMIN DASHBOARD STATISTICS
 * =========================================================
 *
 * Returns live statistics for the Admin Dashboard.
 *
 * Statistics:
 *
 * - Total Products
 * - Total Ingredients
 * - Total Staff
 * - Orders Today
 *
 * IMPORTANT:
 * Actual ADMIN authorization is enforced here.
 *
 */


/*
 * =========================================================
 * ONLY GET
 * =========================================================
 */

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}


/*
 * =========================================================
 * AUTHENTICATION
 * =========================================================
 */

$user = requireAuth($conn);


/*
 * =========================================================
 * ADMIN AUTHORIZATION
 * =========================================================
 */

if (
    !isset($user["role"]) ||
    strtoupper($user["role"]) !== "ADMIN"
) {

    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "Administrator access required."
    ]);

    exit;
}


/*
 * =========================================================
 * PRODUCTS
 * =========================================================
 */

$product_query = "
    SELECT COUNT(*) AS total
    FROM products
";

$product_result = pg_query(
    $conn,
    $product_query
);

if (!$product_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve product statistics."
    ]);

    exit;
}

$product_row =
    pg_fetch_assoc($product_result);

$product_count =
    (int) $product_row["total"];


/*
 * =========================================================
 * INGREDIENTS
 * =========================================================
 */

$ingredient_query = "
    SELECT COUNT(*) AS total
    FROM ingredients
";

$ingredient_result = pg_query(
    $conn,
    $ingredient_query
);

if (!$ingredient_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve ingredient statistics."
    ]);

    exit;
}

$ingredient_row =
    pg_fetch_assoc($ingredient_result);

$ingredient_count =
    (int) $ingredient_row["total"];


/*
 * =========================================================
 * STAFF
 * =========================================================
 *
 * Count active STAFF accounts only.
 *
 * ADMIN accounts are not included.
 *
 */

$staff_query = "
    SELECT COUNT(*) AS total
    FROM users
    WHERE
        UPPER(role) = 'STAFF'
        AND is_active = TRUE
";

$staff_result = pg_query(
    $conn,
    $staff_query
);

if (!$staff_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve staff statistics."
    ]);

    exit;
}

$staff_row =
    pg_fetch_assoc($staff_result);

$staff_count =
    (int) $staff_row["total"];


/*
 * =========================================================
 * ORDERS TODAY
 * =========================================================
 *
 * Counts all orders created today.
 *
 * CURRENT_TIMESTAMP is used so PostgreSQL
 * evaluates the date using the database server time.
 *
 */

$orders_query = "
    SELECT COUNT(*) AS total
    FROM orders
    WHERE order_date::date = CURRENT_DATE
";

$orders_result = pg_query(
    $conn,
    $orders_query
);

if (!$orders_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to retrieve order statistics."
    ]);

    exit;
}

$orders_row =
    pg_fetch_assoc($orders_result);

$orders_today =
    (int) $orders_row["total"];


/*
 * =========================================================
 * RESPONSE
 * =========================================================
 */

echo json_encode([

    "success" => true,

    "stats" => [

        "products" =>
            $product_count,

        "ingredients" =>
            $ingredient_count,

        "staff" =>
            $staff_count,

        "orders_today" =>
            $orders_today
    ]

]);

?>