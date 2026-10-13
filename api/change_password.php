<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

// Get user ID from the authenticated Bearer token.
// Never trust user_id sent by the client.
$user_id = (int) $user["user_id"];

// =========================================================
// REQUEST METHOD
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

// =========================================================
// READ JSON REQUEST
// =========================================================

$raw_input = file_get_contents("php://input");

$data = json_decode(
    $raw_input,
    true
);

if (json_last_error() !== JSON_ERROR_NONE) {

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);

    exit;
}

// =========================================================
// VALIDATE REQUIRED FIELDS
// =========================================================

if (
    !isset($data["current_password"]) ||
    !isset($data["new_password"])
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Current password and new password are required."
    ]);

    exit;
}

$current_password =
    $data["current_password"];

$new_password =
    $data["new_password"];

// =========================================================
// BASIC VALIDATION
// =========================================================

if (
    $current_password === "" ||
    $new_password === ""
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Passwords cannot be empty."
    ]);

    exit;
}

if (strlen($new_password) < 6) {

    echo json_encode([
        "success" => false,
        "message" =>
            "New password must be at least 6 characters."
    ]);

    exit;
}

if ($current_password === $new_password) {

    echo json_encode([
        "success" => false,
        "message" =>
            "New password must be different from the current password."
    ]);

    exit;
}

// =========================================================
// GET AUTHENTICATED USER'S PASSWORD
// =========================================================

$query = "
    SELECT
        user_id,
        password
    FROM users
    WHERE user_id = $1
    LIMIT 1
";

$result = pg_query_params(
    $conn,
    $query,
    [$user_id]
);

if (
    !$result ||
    pg_num_rows($result) === 0
) {

    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);

    exit;
}

$authenticated_user =
    pg_fetch_assoc($result);

// =========================================================
// VERIFY CURRENT PASSWORD
// =========================================================

if (
    !password_verify(
        $current_password,
        $authenticated_user["password"]
    )
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Current password is incorrect."
    ]);

    exit;
}

// =========================================================
// CREATE NEW PASSWORD HASH
// =========================================================

$new_password_hash =
    password_hash(
        $new_password,
        PASSWORD_DEFAULT
    );

if ($new_password_hash === false) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to create password hash."
    ]);

    exit;
}

// =========================================================
// UPDATE PASSWORD
// =========================================================

$update_query = "
    UPDATE users
    SET password = $1
    WHERE user_id = $2
";

$update_result = pg_query_params(
    $conn,
    $update_query,
    [
        $new_password_hash,
        $user_id
    ]
);

if (
    !$update_result ||
    pg_affected_rows($update_result) !== 1
) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to update password."
    ]);

    exit;
}

// =========================================================
// SUCCESS
// =========================================================

echo json_encode([
    "success" => true,
    "message" =>
        "Password changed successfully."
]);

?>