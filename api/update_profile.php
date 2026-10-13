<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

// User ID comes from the authenticated token.
// Do NOT trust user_id from the client.
$user_id = (int) $user["user_id"];

// =========================================================
// READ REQUEST
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

$data = json_decode(
    file_get_contents("php://input"),
    true
);

if (
    !isset($data["full_name"]) ||
    !isset($data["email"]) ||
    !isset($data["phone"])
) {

    echo json_encode([
        "success" => false,
        "message" => "Missing required fields."
    ]);

    exit;
}

$full_name = trim($data["full_name"]);
$email = trim($data["email"]);
$phone = trim($data["phone"]);

// =========================================================
// VALIDATION
// =========================================================

if (
    $full_name === "" ||
    $email === ""
) {

    echo json_encode([
        "success" => false,
        "message" => "Full name and email are required."
    ]);

    exit;
}

// =========================================================
// UPDATE PROFILE
// =========================================================

try {

    // Check if another account already uses this email.
    $checkQuery = "
        SELECT user_id
        FROM users
        WHERE LOWER(email) = LOWER($1)
          AND user_id != $2
        LIMIT 1
    ";

    $checkResult = pg_query_params(
        $conn,
        $checkQuery,
        [
            $email,
            $user_id
        ]
    );

    if (!$checkResult) {

        throw new Exception(
            "Unable to check email."
        );
    }

    if (pg_num_rows($checkResult) > 0) {

        echo json_encode([
            "success" => false,
            "message" =>
                "Email is already being used by another account."
        ]);

        exit;
    }

    // Update ONLY the authenticated user's profile.
    $updateQuery = "
        UPDATE users
        SET
            full_name = $1,
            email = $2,
            phone = $3
        WHERE user_id = $4
    ";

    $updateResult = pg_query_params(
        $conn,
        $updateQuery,
        [
            $full_name,
            $email,
            $phone,
            $user_id
        ]
    );

    if (!$updateResult) {

        throw new Exception(
            "Unable to update profile."
        );
    }

    if (pg_affected_rows($updateResult) !== 1) {

        throw new Exception(
            "Profile was not updated."
        );
    }

    echo json_encode([
        "success" => true,
        "message" =>
            "Profile updated successfully."
    ]);

} catch (Exception $e) {

    echo json_encode([
        "success" => false,
        "message" =>
            $e->getMessage()
    ]);
}

?>