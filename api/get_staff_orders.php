<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";


/*
|--------------------------------------------------------------------------
| STAFF ORDER LIST
|--------------------------------------------------------------------------
|
| STAFF and ADMIN only.
|
| This endpoint returns orders from across the system so staff
| can manage the order queue.
|
| Customer identity and staff accountability are kept separate:
|
| orders.user_id
|     -> Customer account
|
| orders.shift_id
|     -> Staff shift
|         -> Staff user
|
*/


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "GET method required."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

$user = requireAuth($conn);


/*
|--------------------------------------------------------------------------
| ROLE AUTHORIZATION
|--------------------------------------------------------------------------
*/

requireRole(
    $user,
    ["STAFF", "ADMIN"]
);


/*
|--------------------------------------------------------------------------
| OPTIONAL STATUS FILTER
|--------------------------------------------------------------------------
|
| Example:
|
| get_staff_orders.php?status=Preparing
|
| If no status is supplied, all orders are returned.
|
*/

$status = isset($_GET["status"])
    ? trim($_GET["status"])
    : "";


/*
|--------------------------------------------------------------------------
| BASE QUERY
|--------------------------------------------------------------------------
|
| Customer:
|     orders.user_id -> users
|
| Staff accountability:
|     orders.shift_id -> staff_shifts
|     staff_shifts.user_id -> users
|
*/

$query = "
    SELECT
        o.order_id,

        /*
         * CUSTOMER
         */
        o.user_id,
        customer_user.full_name AS customer_name,
        customer_user.email AS customer_email,
        customer_user.phone AS customer_phone,

        /*
         * STAFF / SHIFT ACCOUNTABILITY
         */
        o.shift_id,
        ss.user_id AS staff_user_id,
        staff_user.full_name AS cashier_name,
        staff_user.email AS cashier_email,
        staff_user.phone AS cashier_phone,
        ss.status AS shift_status,
        ss.started_at AS shift_started_at,
        ss.ended_at AS shift_ended_at,

        /*
         * ORDER
         */
        o.total_amount,
        o.order_status,
        o.order_date,

        /*
         * PAYMENT
         */
        o.payment_method,
        o.payment_status,
        o.order_source,

        /*
         * MAYA
         */
        o.maya_checkout_id,
        o.maya_payment_id,
        o.paid_at,

        /*
         * ORDER ITEMS
         */
        COALESCE(
            json_agg(
                json_build_object(
                    'order_item_id', oi.order_item_id,
                    'product_id', oi.product_id,
                    'product_name', p.product_name,
                    'quantity', oi.quantity,
                    'unit_price', oi.unit_price
                )
                ORDER BY oi.order_item_id
            )
            FILTER (
                WHERE oi.order_item_id IS NOT NULL
            ),
            '[]'::json
        ) AS items

    FROM orders o

    /*
     * CUSTOMER
     */
    LEFT JOIN users customer_user
        ON customer_user.user_id = o.user_id

    /*
     * STAFF SHIFT
     */
    LEFT JOIN staff_shifts ss
        ON ss.shift_id = o.shift_id

    /*
     * STAFF ACCOUNT
     */
    LEFT JOIN users staff_user
        ON staff_user.user_id = ss.user_id

    /*
     * ORDER ITEMS
     */
    LEFT JOIN order_items oi
        ON oi.order_id = o.order_id

    LEFT JOIN products p
        ON p.product_id = oi.product_id
";


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

$params = [];

if ($status !== "") {

    $query .= "
        WHERE LOWER(o.order_status) = LOWER($1)
    ";

    $params[] = $status;
}


/*
|--------------------------------------------------------------------------
| GROUPING
|--------------------------------------------------------------------------
*/

$query .= "
    GROUP BY
        o.order_id,

        /*
         * CUSTOMER
         */
        o.user_id,
        customer_user.full_name,
        customer_user.email,
        customer_user.phone,

        /*
         * STAFF / SHIFT
         */
        o.shift_id,
        ss.user_id,
        staff_user.full_name,
        staff_user.email,
        staff_user.phone,
        ss.status,
        ss.started_at,
        ss.ended_at,

        /*
         * ORDER
         */
        o.total_amount,
        o.order_status,
        o.order_date,

        /*
         * PAYMENT
         */
        o.payment_method,
        o.payment_status,
        o.order_source,

        /*
         * MAYA
         */
        o.maya_checkout_id,
        o.maya_payment_id,
        o.paid_at

    ORDER BY
        o.order_date DESC,
        o.order_id DESC

    LIMIT 100
";


/*
|--------------------------------------------------------------------------
| EXECUTE QUERY
|--------------------------------------------------------------------------
*/

$result = pg_query_params(
    $conn,
    $query,
    $params
);


if (!$result) {

    error_log(
        "get_staff_orders.php: "
        . pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve staff orders."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| BUILD RESPONSE
|--------------------------------------------------------------------------
*/

$orders = [];

while ($row = pg_fetch_assoc($result)) {

    $orders[] = [

        /*
         * ------------------------------------------------------
         * ORDER
         * ------------------------------------------------------
         */

        "order_id" =>
            (int) $row["order_id"],


        /*
         * ------------------------------------------------------
         * CUSTOMER
         * ------------------------------------------------------
         *
         * NULL means this is a guest/walk-in order.
         * ------------------------------------------------------
         */

        "user_id" =>
            $row["user_id"] !== null
                ? (int) $row["user_id"]
                : null,

        "customer" => [

            "full_name" =>
                $row["customer_name"],

            "email" =>
                $row["customer_email"],

            "phone" =>
                $row["customer_phone"]
        ],


        /*
         * ------------------------------------------------------
         * STAFF / CASHIER ACCOUNTABILITY
         * ------------------------------------------------------
         *
         * NULL means the order was not associated with a
         * staff shift, such as a customer-created online order.
         * ------------------------------------------------------
         */

        "shift_id" =>
            $row["shift_id"] !== null
                ? (int) $row["shift_id"]
                : null,

        "cashier" => [

            "user_id" =>
                $row["staff_user_id"] !== null
                    ? (int) $row["staff_user_id"]
                    : null,

            "full_name" =>
                $row["cashier_name"],

            "email" =>
                $row["cashier_email"],

            "phone" =>
                $row["cashier_phone"]
        ],

        "shift" => [

            "shift_id" =>
                $row["shift_id"] !== null
                    ? (int) $row["shift_id"]
                    : null,

            "status" =>
                $row["shift_status"],

            "started_at" =>
                $row["shift_started_at"],

            "ended_at" =>
                $row["shift_ended_at"]
        ],


        /*
         * ------------------------------------------------------
         * ORDER DETAILS
         * ------------------------------------------------------
         */

        "total_amount" =>
            $row["total_amount"],

        "order_status" =>
            $row["order_status"],

        "order_date" =>
            $row["order_date"],


        /*
         * ------------------------------------------------------
         * PAYMENT
         * ------------------------------------------------------
         */

        "payment_method" =>
            $row["payment_method"],

        "payment_status" =>
            $row["payment_status"],

        "order_source" =>
            $row["order_source"],


        /*
         * ------------------------------------------------------
         * MAYA
         * ------------------------------------------------------
         */

        "maya_checkout_id" =>
            $row["maya_checkout_id"],

        "maya_payment_id" =>
            $row["maya_payment_id"],

        "paid_at" =>
            $row["paid_at"],


        /*
         * ------------------------------------------------------
         * ITEMS
         * ------------------------------------------------------
         */

        "items" =>
            json_decode(
                $row["items"],
                true
            )
    ];
}


/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/

echo json_encode([

    "success" => true,

    "message" =>
        "Staff orders retrieved successfully.",

    "count" =>
        count($orders),

    "orders" =>
        $orders
]);

?>