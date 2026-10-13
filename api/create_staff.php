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

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
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
    !isset($data["full_name"]) ||
    !isset($data["email"]) ||
    !isset($data["password"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Full name, email, and password are required."
    ]);

    exit;
}


/* =========================================================
   INPUT VALUES
========================================================= */

$full_name = trim((string) $data["full_name"]);

$email = trim((string) $data["email"]);

$password = (string) $data["password"];

$phone = isset($data["phone"])
    ? trim((string) $data["phone"])
    : "";

$address = isset($data["address"])
    ? trim((string) $data["address"])
    : "";


/* =========================================================
   VALIDATION
========================================================= */

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


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

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


if (strlen($password) < 8) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 8 characters."
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
   CHECK DUPLICATE EMAIL
========================================================= */

$check_query = "
    SELECT user_id
    FROM users
    WHERE LOWER(email) = LOWER($1)
    LIMIT 1
";

$check_result = pg_query_params(
    $conn,
    $check_query,
    [$email]
);


if (!$check_result) {

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


if (pg_num_rows($check_result) > 0) {

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" => "This email address is already being used."
    ]);

    exit;
}


/* =========================================================
   HASH PASSWORD
========================================================= */

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


if ($password_hash === false) {

    error_log(
        "Failed to hash password for new staff account."
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to create staff account."
    ]);

    exit;
}


/* =========================================================
   CREATE STAFF ACCOUNT
========================================================= */

$insert_query = "
    INSERT INTO users (
        full_name,
        email,
        password,
        phone,
        address,
        role,
        is_active
    )
    VALUES (
        $1,
        $2,
        $3,
        $4,
        $5,
        'STAFF',
        TRUE
    )
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

$insert_result = pg_query_params(
    $conn,
    $insert_query,
    [
        $full_name,
        $email,
        $password_hash,
        $phone,
        $address
    ]
);


if (!$insert_result) {

    error_log(
        "Failed to create staff account for email: " .
        $email
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to create staff account."
    ]);

    exit;
}


/* =========================================================
   GET CREATED STAFF
========================================================= */

$new_staff = pg_fetch_assoc(
    $insert_result
);

$new_staff["user_id"] =
    (int) $new_staff["user_id"];

$new_staff["is_active"] =
    ($new_staff["is_active"] === "t");


/* =========================================================
   AUDIT LOG
========================================================= */

logAudit(
    $conn,
    $user,
    "CREATE",
    "STAFF",
    "USER",
    $new_staff["user_id"],
    "Created a new staff account.",
    [
        "staff_user_id" =>
            $new_staff["user_id"],

        "full_name" =>
            $new_staff["full_name"],

        "email" =>
            $new_staff["email"],

        "role" =>
            $new_staff["role"],

        "is_active" =>
            $new_staff["is_active"]
    ]
);


/* =========================================================
   RESPONSE
========================================================= */

echo json_encode([
    "success" => true,
    "message" => "Staff account created successfully.",
    "staff" => $new_staff,
    "created_by" => (int) $user["user_id"],
    "created_by_role" => $user["role"]
]);

?>