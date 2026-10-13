<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";
require_once "audit.php";


/* =========================================================
   AUTHENTICATION
========================================================= */

$user = requireAuth($conn);

requireRole($user, ["ADMIN"]);


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only PUT requests are allowed."
    ]);

    exit;
}


/* =========================================================
   READ JSON REQUEST
========================================================= */

$raw_input = file_get_contents("php://input");

$data = json_decode($raw_input, true);

if (!is_array($data)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);

    exit;
}


/* =========================================================
   REQUIRED FIELDS
========================================================= */

if (
    !array_key_exists("user_id", $data) ||
    !array_key_exists("is_active", $data)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "User ID and active status are required."
    ]);

    exit;
}


/* =========================================================
   INPUT VALUES
========================================================= */

$staff_id = filter_var(
    $data["user_id"],
    FILTER_VALIDATE_INT
);

$is_active = filter_var(
    $data["is_active"],
    FILTER_VALIDATE_BOOLEAN,
    FILTER_NULL_ON_FAILURE
);


/* =========================================================
   VALIDATE STAFF ID
========================================================= */

if (
    $staff_id === false ||
    $staff_id === null ||
    $staff_id <= 0
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid staff user ID."
    ]);

    exit;
}


/* =========================================================
   VALIDATE ACTIVE STATUS
========================================================= */

if ($is_active === null) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid active status."
    ]);

    exit;
}


/* =========================================================
   PREVENT ADMIN FROM DEACTIVATING THEMSELVES
========================================================= */

if (
    isset($user["user_id"]) &&
    (int) $user["user_id"] === (int) $staff_id &&
    $is_active === false
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "You cannot deactivate your own account."
    ]);

    exit;
}


/* =========================================================
   VERIFY STAFF ACCOUNT
========================================================= */

$check_query = "
    SELECT
        user_id,
        full_name,
        email,
        phone,
        address,
        role,
        is_active,
        created_at
    FROM users
    WHERE user_id = $1
      AND role = 'STAFF'
    LIMIT 1
";

$check_result = pg_query_params(
    $conn,
    $check_query,
    [
        (string) $staff_id
    ]
);


if ($check_result === false) {

    error_log(
        "Failed to verify staff account ID: " .
        $staff_id .
        " | PostgreSQL error: " .
        pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify staff account."
    ]);

    exit;
}


/* =========================================================
   STAFF ACCOUNT NOT FOUND
========================================================= */

if (pg_num_rows($check_result) === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Staff account not found."
    ]);

    exit;
}


/* =========================================================
   GET CURRENT STATUS
========================================================= */

$current_staff =
    pg_fetch_assoc(
        $check_result
    );

$current_is_active =
    ($current_staff["is_active"] === "t");


/* =========================================================
   CONVERT BOOLEAN TO POSTGRESQL VALUE
========================================================= */

$is_active_value = $is_active
    ? "true"
    : "false";


/* =========================================================
   UPDATE ACTIVE STATUS
========================================================= */

$update_query = "
    UPDATE users
    SET
        is_active = $1::boolean
    WHERE user_id = $2
      AND role = 'STAFF'
    RETURNING
        user_id,
        full_name,
        email,
        phone,
        address,
        role,
        is_active,
        created_at
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        $is_active_value,
        (string) $staff_id
    ]
);


if ($update_result === false) {

    error_log(
        "Failed to update staff status for ID: " .
        $staff_id .
        " | PostgreSQL error: " .
        pg_last_error($conn)
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to update staff account status."
    ]);

    exit;
}


/* =========================================================
   GET UPDATED STAFF
========================================================= */

$updated_staff =
    pg_fetch_assoc(
        $update_result
    );


if ($updated_staff === false) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Staff status was not updated."
    ]);

    exit;
}


/* =========================================================
   FORMAT RESPONSE DATA
========================================================= */

$updated_staff["user_id"] =
    (int) $updated_staff["user_id"];

$updated_staff["is_active"] =
    ($updated_staff["is_active"] === "t");


/* =========================================================
   DETERMINE AUDIT ACTION
========================================================= */

$auditAction =
    $is_active
        ? "ACTIVATE"
        : "DEACTIVATE";


/* =========================================================
   AUDIT LOG
========================================================= */

logAudit(
    $conn,
    $user,
    $auditAction,
    "STAFF",
    "USER",
    $updated_staff["user_id"],
    $is_active
        ? "Activated staff account."
        : "Deactivated staff account.",
    [
        "staff_user_id" =>
            $updated_staff["user_id"],

        "staff_name" =>
            $updated_staff["full_name"],

        "previous_is_active" =>
            $current_is_active,

        "new_is_active" =>
            $updated_staff["is_active"]
    ]
);


/* =========================================================
   SUCCESS RESPONSE
========================================================= */

echo json_encode([
    "success" => true,
    "message" =>
        $is_active
            ? "Staff account activated successfully."
            : "Staff account deactivated successfully.",
    "staff" => $updated_staff,
    "updated_by" => (int) $user["user_id"],
    "updated_by_role" => $user["role"]
]);

exit;

?>