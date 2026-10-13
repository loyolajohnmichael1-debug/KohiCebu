<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$user = requireAuth($conn);
requireRole($user, ["STAFF"]);

/*
|--------------------------------------------------------------------------
| Request Method
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Get Current Open Shift
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        s.shift_id,
        s.user_id,
        u.full_name,
        u.email,
        s.started_at,
        s.ended_at,
        s.status,
        s.created_at
    FROM staff_shifts s
    INNER JOIN users u
        ON u.user_id = s.user_id
    WHERE s.user_id = $1
      AND s.status = 'OPEN'
    LIMIT 1
";

$result = pg_query_params(
    $conn,
    $query,
    [
        (string) $user["user_id"]
    ]
);

if ($result === false) {

    error_log(
        "Failed to retrieve current shift for user ID: " .
        $user["user_id"] .
        " | PostgreSQL error: " .
        pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve current shift."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| No Active Shift
|--------------------------------------------------------------------------
*/

if (pg_num_rows($result) === 0) {

    echo json_encode([
        "success" => true,
        "has_active_shift" => false,
        "message" => "No active shift found.",
        "shift" => null
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Active Shift Found
|--------------------------------------------------------------------------
*/

$shift = pg_fetch_assoc($result);

echo json_encode([
    "success" => true,
    "has_active_shift" => true,
    "message" => "Active shift retrieved successfully.",
    "shift" => [
        "shift_id" => (int) $shift["shift_id"],
        "user_id" => (int) $shift["user_id"],
        "cashier_name" => $shift["full_name"],
        "email" => $shift["email"],
        "started_at" => $shift["started_at"],
        "ended_at" => $shift["ended_at"],
        "status" => $shift["status"],
        "created_at" => $shift["created_at"]
    ]
]);

exit;

?>