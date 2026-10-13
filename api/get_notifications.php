<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

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
// GET ACTIVE NOTIFICATIONS
// =========================================================
//
// IMPORTANT:
// We use the authenticated user's ID.
// We do NOT accept user_id from the client.
//
// Only unresolved notifications are returned.
//
// This means notifications belonging to completed
// orders will no longer appear in the customer's
// notification list.
//

$query = "
    SELECT
        notification_id,
        user_id,
        order_id,
        title,
        message,
        notification_type,
        is_read,
        created_at
    FROM notifications
    WHERE user_id = $1
      AND is_resolved = FALSE
    ORDER BY created_at DESC
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
            "Failed to retrieve notifications."
    ]);

    exit;
}

// =========================================================
// BUILD NOTIFICATION LIST
// =========================================================

$notifications = [];

while ($row = pg_fetch_assoc($result)) {

    $row["notification_id"] =
        (int) $row["notification_id"];

    $row["user_id"] =
        (int) $row["user_id"];

    if ($row["order_id"] !== null) {

        $row["order_id"] =
            (int) $row["order_id"];
    }

    $row["is_read"] =
        filter_var(
            $row["is_read"],
            FILTER_VALIDATE_BOOLEAN
        );

    $notifications[] =
        $row;
}

// =========================================================
// COUNT UNREAD ACTIVE NOTIFICATIONS
// =========================================================
//
// Resolved notifications are excluded.
//
// This keeps the Home notification badge accurate.
//

$unread_query = "
    SELECT COUNT(*) AS unread_count
    FROM notifications
    WHERE user_id = $1
      AND is_read = FALSE
      AND is_resolved = FALSE
";

$unread_result = pg_query_params(
    $conn,
    $unread_query,
    [$user_id]
);

if (!$unread_result) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to retrieve unread notification count."
    ]);

    exit;
}

$unread_row =
    pg_fetch_assoc(
        $unread_result
    );

$unread_count =
    (int) $unread_row["unread_count"];

// =========================================================
// RESPONSE
// =========================================================

echo json_encode([
    "success" => true,

    "message" =>
        "Notifications retrieved successfully.",

    "count" =>
        count($notifications),

    "unread_count" =>
        $unread_count,

    "notifications" =>
        $notifications
]);

?>