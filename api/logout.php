
<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// Only allow POST requests.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

// Authenticate the current session.
$user = requireAuth($conn);

// Read the Bearer token from the request.
$authorization = getAuthorizationHeader();

if (
    !$authorization ||
    !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)
) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Authentication required."
    ]);

    exit;
}

$token = trim($matches[1]);

// Hash the raw token; only its hash is stored in PostgreSQL.
$token_hash = hash("sha256", $token);

// Revoke only this user's current token.
$query = "
    UPDATE api_tokens
    SET revoked_at = CURRENT_TIMESTAMP
    WHERE token_hash = $1
      AND user_id = $2
      AND revoked_at IS NULL
";

$result = pg_query_params(
    $conn,
    $query,
    [
        $token_hash,
        (int) $user["user_id"]
    ]
);

if (!$result) {
    error_log("Failed to revoke API token during logout.");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to log out. Please try again."
    ]);

    exit;
}

if (pg_affected_rows($result) !== 1) {
    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "The session is no longer active."
    ]);

    exit;
}

echo json_encode([
    "success" => true,
    "message" => "Logged out successfully."
]);

?>
