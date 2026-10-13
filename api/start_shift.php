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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Check Whether This Staff Member Already Has an Open Shift
|--------------------------------------------------------------------------
*/

$existing_query = "
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

$existing_result = pg_query_params(
    $conn,
    $existing_query,
    [
        (string) $user["user_id"]
    ]
);

if ($existing_result === false) {
    error_log(
        "Failed to check existing staff shift for user ID: " .
        $user["user_id"] .
        " | PostgreSQL error: " .
        pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to check current shift."
    ]);

    exit;
}

if (pg_num_rows($existing_result) > 0) {

    $existing_shift = pg_fetch_assoc($existing_result);

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "You already have an active shift.",
        "shift" => [
            "shift_id" => (int) $existing_shift["shift_id"],
            "user_id" => (int) $existing_shift["user_id"],
            "started_at" => $existing_shift["started_at"],
            "ended_at" => $existing_shift["ended_at"],
            "status" => $existing_shift["status"],
            "created_at" => $existing_shift["created_at"]
        ]
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Check Whether Another Cashier Is Already On Duty
|--------------------------------------------------------------------------
*/

$open_shift_query = "
    SELECT
        s.shift_id,
        s.user_id,
        s.started_at,
        s.status,
        u.full_name,
        u.email
    FROM staff_shifts s
    INNER JOIN users u
        ON u.user_id = s.user_id
    WHERE s.status = 'OPEN'
    LIMIT 1
";

$open_shift_result = pg_query($conn, $open_shift_query);

if ($open_shift_result === false) {
    error_log(
        "Failed to check open cashier shift. PostgreSQL error: " .
        pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to check cashier availability."
    ]);

    exit;
}

if (pg_num_rows($open_shift_result) > 0) {

    $open_shift = pg_fetch_assoc($open_shift_result);

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "Another cashier already has an active shift.",
        "shift" => [
            "shift_id" => (int) $open_shift["shift_id"],
            "user_id" => (int) $open_shift["user_id"],
            "full_name" => $open_shift["full_name"],
            "started_at" => $open_shift["started_at"],
            "status" => $open_shift["status"]
        ]
    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Create New Shift
|--------------------------------------------------------------------------
*/

$insert_query = "
    INSERT INTO staff_shifts (
        user_id,
        status
    )
    VALUES (
        $1,
        'OPEN'
    )
    RETURNING
        shift_id,
        user_id,
        started_at,
        ended_at,
        status,
        created_at
";

$insert_result = pg_query_params(
    $conn,
    $insert_query,
    [
        (string) $user["user_id"]
    ]
);

if ($insert_result === false) {

    error_log(
        "Failed to create staff shift for user ID: " .
        $user["user_id"] .
        " | PostgreSQL error: " .
        pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to start shift."
    ]);

    exit;
}

$shift = pg_fetch_assoc($insert_result);

if ($shift === false) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Shift was not created."
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
    "message" => "Shift started successfully.",
    "shift" => [
        "shift_id" => (int) $shift["shift_id"],
        "user_id" => (int) $shift["user_id"],
        "cashier_name" => $user["full_name"],
        "started_at" => $shift["started_at"],
        "ended_at" => $shift["ended_at"],
        "status" => $shift["status"],
        "created_at" => $shift["created_at"]
    ]
]);

exit;

?>