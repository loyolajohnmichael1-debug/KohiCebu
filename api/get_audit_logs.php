<?php

/*
 * =========================================================
 * KohiCebu API
 *
 * Get Audit Logs
 *
 * Provides:
 *
 * - Protected audit log retrieval
 * - Administrator-only access
 * - Date filtering
 * - Newest-first ordering
 *
 * =========================================================
 */

header(
    "Content-Type: application/json; charset=UTF-8"
);


/*
 * =========================================================
 * DEPENDENCIES
 * =========================================================
 */

require_once "db.php";
require_once "auth.php";


/*
 * =========================================================
 * REQUEST METHOD
 * =========================================================
 */

if (
    $_SERVER["REQUEST_METHOD"] !== "GET"
) {

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

$user =
    requireAuth($conn);


/*
 * =========================================================
 * ADMIN ACCESS
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

$dateFrom =
    isset($_GET["date_from"])
        ? trim($_GET["date_from"])
        : "";

$dateTo =
    isset($_GET["date_to"])
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


if (
    !validateDate($dateFrom)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid date_from. Use YYYY-MM-DD."
    ]);

    exit;
}


if (
    !validateDate($dateTo)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid date_to. Use YYYY-MM-DD."
    ]);

    exit;
}


if (
    $dateFrom !== "" &&
    $dateTo !== "" &&
    $dateFrom > $dateTo
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "date_from cannot be later than date_to."
    ]);

    exit;
}


/*
 * =========================================================
 * BUILD CONDITIONS
 * =========================================================
 */

$conditions = [];

$params = [];


/*
 * DATE FROM
 */

if (
    $dateFrom !== ""
) {

    $conditions[] =
        "a.created_at >= $1::date";

    $params[] =
        $dateFrom;

}


/*
 * DATE TO
 */

if (
    $dateTo !== ""
) {

    $parameterNumber =
        count($params) + 1;


    $conditions[] =
        "a.created_at < ($"
        . $parameterNumber
        . "::date + INTERVAL '1 day')";


    $params[] =
        $dateTo;

}


/*
 * WHERE CLAUSE
 */

$where = "";


if (
    count($conditions) > 0
) {

    $where =
        "WHERE "
        . implode(
            " AND ",
            $conditions
        );

}


/*
 * =========================================================
 * QUERY
 * =========================================================
 */

$query = "

    SELECT

        a.audit_id,

        a.user_id,

        COALESCE(
            u.full_name,
            'Unknown User'
        ) AS user_name,

        COALESCE(
            u.role,
            'UNKNOWN'
        ) AS user_role,

        a.action_type,

        a.module,

        a.target_type,

        a.target_id,

        a.description,

        a.metadata,

        a.ip_address,

        a.user_agent,

        a.created_at

    FROM audit_logs a

    LEFT JOIN users u
        ON u.user_id = a.user_id

    $where

    ORDER BY
        a.created_at DESC,
        a.audit_id DESC

    LIMIT 100

";


/*
 * =========================================================
 * EXECUTE QUERY
 * =========================================================
 */

$result =
    pg_query_params(
        $conn,
        $query,
        $params
    );


if (
    !$result
) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to load audit logs."
    ]);

    exit;
}


/*
 * =========================================================
 * BUILD RESPONSE
 * =========================================================
 */

$logs = [];


while (
    $row =
        pg_fetch_assoc($result)
) {

    $metadata = null;


    /*
     * PostgreSQL JSONB is returned
     * as a JSON string.
     *
     * Decode it so the API returns
     * an actual JSON object.
     */

    if (
        !empty($row["metadata"])
    ) {

        $decodedMetadata =
            json_decode(
                $row["metadata"],
                true
            );


        if (
            json_last_error() === JSON_ERROR_NONE
        ) {

            $metadata =
                $decodedMetadata;

        } else {

            $metadata =
                $row["metadata"];

        }

    }


    $logs[] = [

        "audit_id" =>
            (int) $row["audit_id"],

        "user_id" =>
            $row["user_id"] !== null
                ? (int) $row["user_id"]
                : null,

        "user_name" =>
            $row["user_name"],

        "user_role" =>
            $row["user_role"],

        "action_type" =>
            $row["action_type"],

        "module" =>
            $row["module"],

        "target_type" =>
            $row["target_type"],

        "target_id" =>
            $row["target_id"] !== null
                ? (int) $row["target_id"]
                : null,

        "description" =>
            $row["description"],

        "metadata" =>
            $metadata,

        "ip_address" =>
            $row["ip_address"],

        "user_agent" =>
            $row["user_agent"],

        "created_at" =>
            $row["created_at"]

    ];

}


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

    "count" =>
        count($logs),

    "logs" =>
        $logs

]);


?>