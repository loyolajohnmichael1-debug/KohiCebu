<?php

/*
 * =========================================================
 * KohiCebu
 *
 * Admin Reports API
 *
 * Supports:
 *
 * - All-time reports
 * - Date range reports
 * - Sales trend
 *
 * Query parameters:
 *
 * ?date_from=YYYY-MM-DD
 * ?date_to=YYYY-MM-DD
 * =========================================================
 */

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";


/*
 * =========================================================
 * METHOD CHECK
 * =========================================================
 */

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
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
 * ADMIN CHECK
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
 * DATE FILTERS
 * =========================================================
 */

$dateFrom = isset($_GET["date_from"])
    ? trim($_GET["date_from"])
    : "";

$dateTo = isset($_GET["date_to"])
    ? trim($_GET["date_to"])
    : "";


/*
 * =========================================================
 * DATE VALIDATION
 * =========================================================
 */

function validateDate($date)
{
    if ($date === "") {
        return true;
    }

    $parsed =
        DateTime::createFromFormat(
            "Y-m-d",
            $date
        );

    return (
        $parsed !== false &&
        $parsed->format("Y-m-d") === $date
    );
}


if (!validateDate($dateFrom)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid date_from. Use YYYY-MM-DD."
    ]);

    exit;
}


if (!validateDate($dateTo)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid date_to. Use YYYY-MM-DD."
    ]);

    exit;
}


/*
 * =========================================================
 * DATE RANGE VALIDATION
 * =========================================================
 */

if (
    $dateFrom !== "" &&
    $dateTo !== "" &&
    $dateFrom > $dateTo
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "date_from cannot be later than date_to."
    ]);

    exit;
}


/*
 * =========================================================
 * BUILD DATE CONDITION
 * =========================================================
 */

$dateConditions = [];
$dateParams = [];


if ($dateFrom !== "") {

    $dateConditions[] =
        "o.order_date >= $1::date";

    $dateParams[] =
        $dateFrom;

}


if ($dateTo !== "") {

    $parameterNumber =
        count($dateParams) + 1;

    $dateConditions[] =
        "o.order_date < ($"
        . $parameterNumber
        . "::date + INTERVAL '1 day')";

    $dateParams[] =
        $dateTo;

}


$dateWhere = "";

if (count($dateConditions) > 0) {

    $dateWhere =
        "WHERE "
        . implode(
            " AND ",
            $dateConditions
        );

}


/*
 * =========================================================
 * SUMMARY
 * =========================================================
 */

$summaryQuery = "
    SELECT

        COUNT(*) AS total_orders,

        COUNT(*) FILTER (
            WHERE UPPER(o.payment_status) = 'PAID'
        ) AS paid_orders,

        COUNT(*) FILTER (
            WHERE UPPER(o.payment_status) = 'UNPAID'
        ) AS unpaid_orders,

        COUNT(*) FILTER (
            WHERE UPPER(o.order_status) = 'COMPLETED'
        ) AS completed_orders,

        COALESCE(
            SUM(o.total_amount),
            0
        ) AS gross_order_value,

        COALESCE(
            SUM(
                CASE
                    WHEN UPPER(o.payment_status) = 'PAID'
                    THEN o.total_amount
                    ELSE 0
                END
            ),
            0
        ) AS paid_revenue,

        COALESCE(
            SUM(
                CASE
                    WHEN UPPER(o.payment_status) = 'UNPAID'
                    THEN o.total_amount
                    ELSE 0
                END
            ),
            0
        ) AS unpaid_order_value

    FROM orders o

    $dateWhere
";


$summaryResult =
    pg_query_params(
        $conn,
        $summaryQuery,
        $dateParams
    );


if (!$summaryResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load report summary."
    ]);

    exit;
}


$summary =
    pg_fetch_assoc(
        $summaryResult
    );


/*
 * =========================================================
 * PAYMENT BREAKDOWN
 * =========================================================
 */

$paymentParams = [];
$paymentConditions = [];


if ($dateFrom !== "") {

    $paymentConditions[] =
        "o.order_date >= $1::date";

    $paymentParams[] =
        $dateFrom;

}


if ($dateTo !== "") {

    $parameterNumber =
        count($paymentParams) + 1;

    $paymentConditions[] =
        "o.order_date < ($"
        . $parameterNumber
        . "::date + INTERVAL '1 day')";

    $paymentParams[] =
        $dateTo;

}


$paymentConditions[] =
    "UPPER(o.payment_status) = 'PAID'";


$paymentWhere =
    "WHERE "
    . implode(
        " AND ",
        $paymentConditions
    );


$paymentQuery = "
    SELECT

        COALESCE(
            NULLIF(
                TRIM(o.payment_method),
                ''
            ),
            'UNKNOWN'
        ) AS payment_method,

        COUNT(*) AS order_count,

        COALESCE(
            SUM(o.total_amount),
            0
        ) AS total_amount

    FROM orders o

    $paymentWhere

    GROUP BY
        COALESCE(
            NULLIF(
                TRIM(o.payment_method),
                ''
            ),
            'UNKNOWN'
        )

    ORDER BY
        total_amount DESC
";


$paymentResult =
    pg_query_params(
        $conn,
        $paymentQuery,
        $paymentParams
    );


if (!$paymentResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load payment breakdown."
    ]);

    exit;
}


$paymentBreakdown = [];


while (
    $row =
        pg_fetch_assoc(
            $paymentResult
        )
) {

    $paymentBreakdown[] = [

        "payment_method" =>
            $row["payment_method"],

        "order_count" =>
            (int) $row["order_count"],

        "total_amount" =>
            (float) $row["total_amount"]

    ];

}


/*
 * =========================================================
 * TOP PRODUCTS
 * =========================================================
 */

$productParams = [];
$productConditions = [];


if ($dateFrom !== "") {

    $productConditions[] =
        "o.order_date >= $1::date";

    $productParams[] =
        $dateFrom;

}


if ($dateTo !== "") {

    $parameterNumber =
        count($productParams) + 1;

    $productConditions[] =
        "o.order_date < ($"
        . $parameterNumber
        . "::date + INTERVAL '1 day')";

    $productParams[] =
        $dateTo;

}


$productConditions[] =
    "UPPER(o.payment_status) = 'PAID'";


$productWhere =
    "WHERE "
    . implode(
        " AND ",
        $productConditions
    );


$productQuery = "
    SELECT

        p.product_id,

        p.product_name,

        p.category,

        COALESCE(
            SUM(oi.quantity),
            0
        ) AS units_sold,

        COALESCE(
            SUM(
                oi.quantity * oi.unit_price
            ),
            0
        ) AS sales_amount

    FROM order_items oi

    INNER JOIN orders o
        ON o.order_id = oi.order_id

    INNER JOIN products p
        ON p.product_id = oi.product_id

    $productWhere

    GROUP BY
        p.product_id,
        p.product_name,
        p.category

    ORDER BY
        sales_amount DESC,
        units_sold DESC

    LIMIT 10
";


$productResult =
    pg_query_params(
        $conn,
        $productQuery,
        $productParams
    );


if (!$productResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to load product performance."
    ]);

    exit;
}


$topProducts = [];


while (
    $row =
        pg_fetch_assoc(
            $productResult
        )
) {

    $topProducts[] = [

        "product_id" =>
            (int) $row["product_id"],

        "product_name" =>
            $row["product_name"],

        "category" =>
            $row["category"],

        "units_sold" =>
            (int) $row["units_sold"],

        "sales_amount" =>
            (float) $row["sales_amount"]

    ];

}


/*
 * =========================================================
 * SALES TREND
 * =========================================================
 *
 * PAID revenue is calculated per calendar day.
 *
 * Important:
 *
 * - Only PAID orders contribute to revenue.
 * - Dates without orders are included as zero.
 * - The selected date range controls the period.
 *
 * =========================================================
 */


/*
 * Determine the trend range.
 *
 * If a date filter exists, use it.
 *
 * If no date filter exists, use the earliest
 * and latest order dates in the database.
 */

$trendFrom =
    $dateFrom;

$trendTo =
    $dateTo;


if (
    $trendFrom === "" ||
    $trendTo === ""
) {

    $trendRangeQuery = "
        SELECT

            MIN(order_date::date) AS first_date,

            MAX(order_date::date) AS last_date

        FROM orders
    ";


    $trendRangeResult =
        pg_query(
            $conn,
            $trendRangeQuery
        );


    if (!$trendRangeResult) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to determine sales trend range."
        ]);

        exit;
    }


    $trendRange =
        pg_fetch_assoc(
            $trendRangeResult
        );


    if (
        $trendFrom === "" &&
        !empty($trendRange["first_date"])
    ) {

        $trendFrom =
            $trendRange["first_date"];

    }


    if (
        $trendTo === "" &&
        !empty($trendRange["last_date"])
    ) {

        $trendTo =
            $trendRange["last_date"];

    }

}


/*
 * If there are no orders at all,
 * return an empty trend.
 */

$salesTrend = [];


if (
    $trendFrom !== "" &&
    $trendTo !== ""
) {

    /*
     * ---------------------------------------------
     * Build daily revenue query
     * ---------------------------------------------
     */

    $trendQuery = "
        SELECT

            o.order_date::date AS report_date,

            COUNT(*) AS order_count,

            COALESCE(
                SUM(
                    CASE
                        WHEN UPPER(o.payment_status) = 'PAID'
                        THEN o.total_amount
                        ELSE 0
                    END
                ),
                0
            ) AS paid_revenue

        FROM orders o

        WHERE
            o.order_date >= $1::date

            AND

            o.order_date < (
                $2::date
                + INTERVAL '1 day'
            )

        GROUP BY
            o.order_date::date

        ORDER BY
            report_date ASC
    ";


    $trendResult =
        pg_query_params(
            $conn,
            $trendQuery,
            [
                $trendFrom,
                $trendTo
            ]
        );


    if (!$trendResult) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" => "Unable to load sales trend."
        ]);

        exit;
    }


    /*
     * ---------------------------------------------
     * Store database results by date
     * ---------------------------------------------
     */

    $trendByDate = [];


    while (
        $row =
            pg_fetch_assoc(
                $trendResult
            )
    ) {

        $trendByDate[
            $row["report_date"]
        ] = [

            "order_count" =>
                (int) $row["order_count"],

            "paid_revenue" =>
                (float) $row["paid_revenue"]

        ];

    }


    /*
     * ---------------------------------------------
     * Generate every date in range
     * ---------------------------------------------
     */

    $start =
        new DateTime(
            $trendFrom
        );

    $end =
        new DateTime(
            $trendTo
        );


    while (
        $start <= $end
    ) {

        $date =
            $start->format(
                "Y-m-d"
            );


        $salesTrend[] = [

            "date" =>
                $date,

            "order_count" =>
                isset(
                    $trendByDate[$date]
                )
                    ? $trendByDate[$date]["order_count"]
                    : 0,

            "paid_revenue" =>
                isset(
                    $trendByDate[$date]
                )
                    ? $trendByDate[$date]["paid_revenue"]
                    : 0

        ];


        $start->modify(
            "+1 day"
        );

    }

}


/*
 * =========================================================
 * NORMALIZE SUMMARY
 * =========================================================
 */

$summaryResponse = [

    "total_orders" =>
        (int) $summary["total_orders"],

    "paid_orders" =>
        (int) $summary["paid_orders"],

    "unpaid_orders" =>
        (int) $summary["unpaid_orders"],

    "completed_orders" =>
        (int) $summary["completed_orders"],

    "gross_order_value" =>
        (float) $summary["gross_order_value"],

    "paid_revenue" =>
        (float) $summary["paid_revenue"],

    "unpaid_order_value" =>
        (float) $summary["unpaid_order_value"]

];


/*
 * =========================================================
 * RESPONSE
 * =========================================================
 */

echo json_encode([

    "success" => true,

    "filters" => [

        "date_from" =>
            $dateFrom !== ""
                ? $dateFrom
                : null,

        "date_to" =>
            $dateTo !== ""
                ? $dateTo
                : null

    ],

    "summary" =>
        $summaryResponse,

    "payment_breakdown" =>
        $paymentBreakdown,

    "top_products" =>
        $topProducts,

    "sales_trend" =>
        $salesTrend

]);

?>