<?php

header("Content-Type: application/json");

require_once "db.php";

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);
    exit;
}

// Read JSON request
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

// Validate required fields
if (
    !isset($data["email"]) ||
    !isset($data["password"])
) {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Email and password are required."
    ]);
    exit;
}

$email = strtolower(trim($data["email"]));
$password = $data["password"];

if ($email === "" || $password === "") {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Email and password cannot be empty."
    ]);
    exit;
}

// Find user by email
$query = "
    SELECT
        user_id,
        full_name,
        email,
        password,
        phone,
        role,
        is_active,
        created_at
    FROM users
    WHERE LOWER(email) = $1
    LIMIT 1
";

$result = pg_query_params(
    $conn,
    $query,
    [$email]
);

if (!$result || pg_num_rows($result) === 0) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);
    exit;
}

$user = pg_fetch_assoc($result);

// Verify hashed password
if (!password_verify($password, $user["password"])) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Invalid email or password."
    ]);
    exit;
}

// ---------------------------------------------------------
// CHECK ACCOUNT STATUS
// ---------------------------------------------------------

/*
 * PostgreSQL BOOLEAN values are returned as
 * "t" or "f" strings by pg_fetch_assoc().
 */
if ($user["is_active"] !== "t") {

    http_response_code(403);

    echo json_encode([
        "success" => false,
        "message" => "This account is currently inactive."
    ]);

    exit;
}

// ---------------------------------------------------------
// CREATE AUTHENTICATION TOKEN
// ---------------------------------------------------------

/*
 * Generate a cryptographically secure random token.
 *
 * random_bytes(32) = 256 bits of randomness.
 */
$token = bin2hex(random_bytes(32));

/*
 * Store only the SHA-256 hash in PostgreSQL.
 */
$token_hash = hash(
    "sha256",
    $token
);

/*
 * Token lifetime:
 * 7 days from successful login.
 */
$expires_at = date(
    "Y-m-d H:i:s",
    time() + (7 * 24 * 60 * 60)
);

/*
 * Store the token hash.
 */
$token_query = "
    INSERT INTO api_tokens
    (
        user_id,
        token_hash,
        expires_at
    )
    VALUES
    (
        $1,
        $2,
        $3
    )
";

$token_result = pg_query_params(
    $conn,
    $token_query,
    [
        (int) $user["user_id"],
        $token_hash,
        $expires_at
    ]
);

if (!$token_result) {

    error_log(
        "Failed to create authentication token for user ID: " .
        $user["user_id"]
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to create authentication session."
    ]);

    exit;
}

// ---------------------------------------------------------
// RECORD SUCCESSFUL LOGIN
// ---------------------------------------------------------

$login_query = "
    INSERT INTO login_activity (user_id)
    VALUES ($1)
";

$login_result = pg_query_params(
    $conn,
    $login_query,
    [(int) $user["user_id"]]
);

// Login activity failure should not prevent login
if (!$login_result) {
    error_log(
        "Failed to record login activity for user ID: " .
        $user["user_id"]
    );
}

// Remove password before sending response
unset($user["password"]);

// Convert database values to proper types
$user["user_id"] =
    (int) $user["user_id"];

$user["is_active"] =
    ($user["is_active"] === "t");

// ---------------------------------------------------------
// SUCCESS RESPONSE
// ---------------------------------------------------------

echo json_encode([
    "success" => true,
    "message" => "Login successful.",
    "token" => $token,
    "expires_at" => $expires_at,
    "user" => $user
]);

?>