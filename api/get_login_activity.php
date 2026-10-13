<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

// Get user ID from the authenticated token.
// Never trust user_id from the client.
$user_id = (int) $user["user_id"];

// =========================================================
// REQUEST METHOD
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}

// =========================================================
// GET LOGIN ACTIVITY
// =========================================================

$query = "
    SELECT
        login_activity_id,
        user_id,
        login_date
    FROM login_activity
    WHERE user_id = $1
    ORDER BY login_date DESC
";

$result = pg_query_params(
    $conn,
    $query,
    [$user_id]
);

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to retrieve login activity."
    ]);

    exit;
}

$activities = [];

while ($row = pg_fetch_assoc($result)) {

    $row["login_activity_id"] =
        (int) $row["login_activity_id"];

    $row["user_id"] =
        (int) $row["user_id"];

    $activities[] = $row;
}

// =========================================================
// RESPONSE
// =========================================================

echo json_encode([
    "success" => true,
    "message" =>
        "Login activity retrieved successfully.",
    "activities" => $activities
]);

?>