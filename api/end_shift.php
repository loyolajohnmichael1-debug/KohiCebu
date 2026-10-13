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

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only PUT requests are allowed."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Find Current Open Shift
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        shift_id,
        user_id,
        started_at,
        ended_at,
        status,
        created_at
    FROM staff_shifts
    WHERE user_id = $1
      AND status = 'OPEN'
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
        "Failed to find current shift for user ID: " .
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

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "No active shift found."
    ]);

    exit;
}

$shift = pg_fetch_assoc($result);

$shift_id = (int) $shift["shift_id"];

/*
|--------------------------------------------------------------------------
| Close Current Shift
|--------------------------------------------------------------------------
*/

$update_query = "
    UPDATE staff_shifts
    SET
        ended_at = CURRENT_TIMESTAMP,
        status = 'CLOSED'
    WHERE shift_id = $1
      AND user_id = $2
      AND status = 'OPEN'
    RETURNING
        shift_id,
        user_id,
        started_at,
        ended_at,
        status,
        created_at
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        (string) $shift_id,
        (string) $user["user_id"]
    ]
);

if ($update_result === false) {

    error_log(
        "Failed to close shift ID: " .
        $shift_id .
        " for user ID: " .
        $user["user_id"] .
        " | PostgreSQL error: " .
        pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to end shift."
    ]);

    exit;
}

$closed_shift = pg_fetch_assoc($update_result);

if ($closed_shift === false) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "The shift could not be closed. It may have already been closed."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Success Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" => "Shift ended successfully.",
    "shift" => [
        "shift_id" => (int) $closed_shift["shift_id"],
        "user_id" => (int) $closed_shift["user_id"],
        "cashier_name" => $user["full_name"],
        "started_at" => $closed_shift["started_at"],
        "ended_at" => $closed_shift["ended_at"],
        "status" => $closed_shift["status"],
        "created_at" => $closed_shift["created_at"]
    ]
]);

exit;

?>