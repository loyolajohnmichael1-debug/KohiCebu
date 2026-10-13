<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";
require_once "audit.php";

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
   READ JSON
========================================================= */

$raw_input = file_get_contents("php://input");

$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {

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
    !isset($data["user_id"]) ||
    !isset($data["full_name"]) ||
    !isset($data["email"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "User ID, full name, and email are required."
    ]);

    exit;
}


/* =========================================================
   INPUT VALUES
========================================================= */

$staff_id = (int) $data["user_id"];

$full_name = trim(
    (string) $data["full_name"]
);

$email = trim(
    (string) $data["email"]
);

$phone = isset($data["phone"])
    ? trim((string) $data["phone"])
    : "";

$address = isset($data["address"])
    ? trim((string) $data["address"])
    : "";


/* =========================================================
   VALIDATION
========================================================= */

if ($staff_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid staff user ID."
    ]);

    exit;
}


if ($full_name === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Full name is required."
    ]);

    exit;
}


if (strlen($full_name) > 150) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Full name is too long."
    ]);

    exit;
}


if (!filter_var(
    $email,
    FILTER_VALIDATE_EMAIL
)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Please provide a valid email address."
    ]);

    exit;
}


if (strlen($email) > 255) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Email address is too long."
    ]);

    exit;
}


if (strlen($address) > 500) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Address is too long."
    ]);

    exit;
}


/* =========================================================
   GET CURRENT STAFF ACCOUNT
========================================================= */

$current_staff_query = "
    SELECT
        user_id,
        full_name,
        email,
        phone,
        address,
        role,
        is_active
    FROM users
    WHERE user_id = $1
      AND role = 'STAFF'
    LIMIT 1
";

$current_staff_result = pg_query_params(
    $conn,
    $current_staff_query,
    [$staff_id]
);


if (!$current_staff_result) {

    error_log(
        "Failed to retrieve current staff account ID: " .
        $staff_id
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify staff account."
    ]);

    exit;
}


if (pg_num_rows($current_staff_result) === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Staff account not found."
    ]);

    exit;
}


$current_staff =
    pg_fetch_assoc(
        $current_staff_result
    );


/* =========================================================
   CHECK DUPLICATE EMAIL
========================================================= */

$email_check_query = "
    SELECT
        user_id
    FROM users
    WHERE LOWER(email) = LOWER($1)
      AND user_id <> $2
    LIMIT 1
";

$email_check_result = pg_query_params(
    $conn,
    $email_check_query,
    [
        $email,
        $staff_id
    ]
);


if (!$email_check_result) {

    error_log(
        "Failed to check duplicate staff email."
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify email address."
    ]);

    exit;
}


if (pg_num_rows(
    $email_check_result
) > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "This email address is already being used."
    ]);

    exit;
}


/* =========================================================
   UPDATE STAFF PROFILE
========================================================= */

$update_query = "
    UPDATE users
    SET
        full_name = $1,
        email = $2,
        phone = $3,
        address = $4
    WHERE user_id = $5
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
        $full_name,
        $email,
        $phone,
        $address,
        $staff_id
    ]
);


if (!$update_result) {

    error_log(
        "Failed to update staff account ID: " .
        $staff_id
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to update staff account."
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


/* =========================================================
   NORMALIZE RESPONSE
========================================================= */

$updated_staff["user_id"] =
    (int) $updated_staff["user_id"];

$updated_staff["is_active"] =
    ($updated_staff["is_active"] === "t");


/* =========================================================
   DETERMINE CHANGED FIELDS
========================================================= */

$changedFields = [];

$previousValues = [];

$newValues = [];


/* ---------------------------------------------------------
   FULL NAME
--------------------------------------------------------- */

if (
    (string) $current_staff["full_name"]
    !==
    (string) $updated_staff["full_name"]
) {

    $changedFields[] = "full_name";

    $previousValues["full_name"] =
        $current_staff["full_name"];

    $newValues["full_name"] =
        $updated_staff["full_name"];
}


/* ---------------------------------------------------------
   EMAIL
--------------------------------------------------------- */

if (
    (string) $current_staff["email"]
    !==
    (string) $updated_staff["email"]
) {

    $changedFields[] = "email";

    $previousValues["email"] =
        $current_staff["email"];

    $newValues["email"] =
        $updated_staff["email"];
}


/* ---------------------------------------------------------
   PHONE
--------------------------------------------------------- */

$currentPhone =
    $current_staff["phone"] ?? "";

$updatedPhone =
    $updated_staff["phone"] ?? "";

if (
    (string) $currentPhone
    !==
    (string) $updatedPhone
) {

    $changedFields[] = "phone";

    $previousValues["phone"] =
        $currentPhone;

    $newValues["phone"] =
        $updatedPhone;
}


/* ---------------------------------------------------------
   ADDRESS
--------------------------------------------------------- */

$currentAddress =
    $current_staff["address"] ?? "";

$updatedAddress =
    $updated_staff["address"] ?? "";

if (
    (string) $currentAddress
    !==
    (string) $updatedAddress
) {

    $changedFields[] = "address";

    $previousValues["address"] =
        $currentAddress;

    $newValues["address"] =
        $updatedAddress;
}


/* =========================================================
   AUDIT LOG
========================================================= */

logAudit(
    $conn,
    $user,
    "UPDATE",
    "STAFF",
    "USER",
    $updated_staff["user_id"],
    "Updated staff account profile.",
    [
        "staff_user_id" =>
            $updated_staff["user_id"],

        "changed_fields" =>
            $changedFields,

        "previous_values" =>
            $previousValues,

        "new_values" =>
            $newValues
    ]
);


/* =========================================================
   RESPONSE
========================================================= */

echo json_encode([
    "success" => true,
    "message" => "Staff account updated successfully.",
    "staff" => $updated_staff,
    "updated_by" => (int) $user["user_id"],
    "updated_by_role" => $user["role"]
]);

?>