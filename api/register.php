<?php

header("Content-Type: application/json");

require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);
    exit;
}

// Check required fields
if (
    !isset($data["full_name"]) ||
    !isset($data["email"]) ||
    !isset($data["password"]) ||
    !isset($data["phone"])
) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required."
    ]);
    exit;
}

$full_name = trim($data["full_name"]);
$email = strtolower(trim($data["email"]));
$password = $data["password"];
$phone = trim($data["phone"]);

// Validate empty fields
if (
    $full_name === "" ||
    $email === "" ||
    $password === "" ||
    $phone === ""
) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required."
    ]);
    exit;
}

// Validate full name
if (strlen($full_name) < 2 || strlen($full_name) > 100) {
    echo json_encode([
        "success" => false,
        "message" => "Full name must be between 2 and 100 characters."
    ]);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid email address."
    ]);
    exit;
}

// Validate password
if (strlen($password) < 6) {
    echo json_encode([
        "success" => false,
        "message" => "Password must be at least 6 characters."
    ]);
    exit;
}

// Validate phone
if (strlen($phone) < 7 || strlen($phone) > 20) {
    echo json_encode([
        "success" => false,
        "message" => "Please enter a valid phone number."
    ]);
    exit;
}

// Check if email already exists
$check_query = "
    SELECT user_id
    FROM users
    WHERE LOWER(email) = $1
    LIMIT 1
";

$check_result = pg_query_params(
    $conn,
    $check_query,
    [$email]
);

if (!$check_result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to check existing account."
    ]);
    exit;
}

if (pg_num_rows($check_result) > 0) {
    echo json_encode([
        "success" => false,
        "message" => "An account with this email already exists."
    ]);
    exit;
}

// Hash password securely
$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// -----------------------------------------
// PUBLIC REGISTRATION = CUSTOMER ONLY
// -----------------------------------------

$role = "CUSTOMER";

// Insert new user
$insert_query = "
    INSERT INTO users (
        full_name,
        email,
        password,
        phone,
        role
    )
    VALUES ($1, $2, $3, $4, $5)
    RETURNING
        user_id,
        full_name,
        email,
        phone,
        role,
        created_at
";

$result = pg_query_params(
    $conn,
    $insert_query,
    [
        $full_name,
        $email,
        $hashed_password,
        $phone,
        $role
    ]
);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to create account."
    ]);
    exit;
}

$user = pg_fetch_assoc($result);

$user["user_id"] = (int) $user["user_id"];

echo json_encode([
    "success" => true,
    "message" => "Registration successful.",
    "user" => $user
]);

?>