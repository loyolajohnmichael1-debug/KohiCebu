<?php
header("Content-Type: application/json");
require_once "db.php";
require_once dirname(__DIR__) . "/vendor/autoload.php";

const GOOGLE_WEB_CLIENT_ID = "831845580299-ib9cd05ujm1tvh918a9fd3hm1doof99r.apps.googleusercontent.com";

function googleLoginFail($message, $status = 400) {
    http_response_code($status);
    echo json_encode(["success" => false, "message" => $message]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    googleLoginFail("Only POST requests are allowed.", 405);
}

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data) || empty($data["id_token"]) || !is_string($data["id_token"])) {
    googleLoginFail("A Google ID token is required.");
}

try {
    $verifier = new Google\Auth\AccessToken();
    $claims = $verifier->verify($data["id_token"], [
        "audience" => GOOGLE_WEB_CLIENT_ID,
        "issuer" => "https://accounts.google.com",
        "throwException" => true
    ]);
} catch (Throwable $e) {
    error_log("Google sign-in token verification failed: " . $e->getMessage());
    googleLoginFail("Google sign-in could not be verified. Please try again.", 401);
}

$email = strtolower(trim($claims["email"] ?? ""));
$name = trim($claims["name"] ?? "");
$emailVerified = ($claims["email_verified"] ?? false) === true
    || ($claims["email_verified"] ?? null) === "true";
if (!$emailVerified || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    googleLoginFail("Google did not provide a verified email address.", 401);
}

$query = "SELECT user_id, full_name, email, phone, role, is_active, created_at
          FROM users WHERE LOWER(email) = $1 LIMIT 1";
$result = pg_query_params($conn, $query, [$email]);
if (!$result) {
    error_log("Google login account lookup failed.");
    googleLoginFail("Unable to look up your KohiCebu account.", 500);
}
$isNewUser = false;
if (pg_num_rows($result) === 0) {
    $phone = trim((string)($data["phone"] ?? ""));
    if (strlen($phone) < 7 || strlen($phone) > 20) {
        googleLoginFail("Enter a valid phone number to finish creating your customer account.", 422);
    }
    $fullName = trim($name);
    if (strlen($fullName) < 2) $fullName = strstr($email, "@", true) ?: "KohiCebu Customer";
    $fullName = substr($fullName, 0, 100);
    $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $created = pg_query_params($conn,
        "INSERT INTO users (full_name, email, password, phone, role) VALUES ($1, $2, $3, $4, 'CUSTOMER') RETURNING user_id, full_name, email, phone, role, is_active, created_at",
        [$fullName, $email, $passwordHash, $phone]
    );
    if (!$created) {
        // A concurrent registration may have claimed the address; use that customer account.
        $result = pg_query_params($conn, $query, [$email]);
        if (!$result || pg_num_rows($result) === 0) {
            error_log("Google customer registration failed.");
            googleLoginFail("Unable to create your account. Please try again.", 500);
        }
        $user = pg_fetch_assoc($result);
    } else {
        $user = pg_fetch_assoc($created);
        $isNewUser = true;
    }
} else {
    $user = pg_fetch_assoc($result);
}
if (strtoupper($user["role"] ?? "") !== "CUSTOMER") {
    googleLoginFail("Google sign-in is available for customer accounts only.", 403);
}
if (($user["is_active"] ?? "f") !== "t") {
    googleLoginFail("This account is currently inactive.", 403);
}

$token = bin2hex(random_bytes(32));
$tokenHash = hash("sha256", $token);
$expiresAt = date("Y-m-d H:i:s", time() + (7 * 24 * 60 * 60));
$tokenResult = pg_query_params(
    $conn,
    "INSERT INTO api_tokens (user_id, token_hash, expires_at) VALUES ($1, $2, $3)",
    [(int)$user["user_id"], $tokenHash, $expiresAt]
);
if (!$tokenResult) {
    error_log("Failed to create Google login token for user ID: " . $user["user_id"]);
    googleLoginFail("Unable to create an authentication session.", 500);
}

$activityResult = pg_query_params(
    $conn,
    "INSERT INTO login_activity (user_id) VALUES ($1)",
    [(int)$user["user_id"]]
);
if (!$activityResult) {
    error_log("Failed to record Google login activity for user ID: " . $user["user_id"]);
}

$user["user_id"] = (int)$user["user_id"];
$user["is_active"] = true;
echo json_encode([
    "success" => true,
    "message" => $isNewUser ? "Customer account created." : "Google sign-in successful.",
    "is_new_user" => $isNewUser,
    "token" => $token,
    "expires_at" => $expiresAt,
    "user" => $user
]);
?>
