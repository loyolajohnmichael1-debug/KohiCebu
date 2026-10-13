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
// VALIDATE NOTIFICATION ID
// =========================================================

if (!isset($data["notification_id"])) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Notification ID is required."
    ]);

    exit;
}

$notification_id =
    intval($data["notification_id"]);

if ($notification_id <= 0) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid notification ID."
    ]);

    exit;
}

// =========================================================
// MARK NOTIFICATION AS READ
// =========================================================
//
// IMPORTANT:
// The notification must belong to the authenticated
// customer.
//
// We intentionally do NOT accept user_id from the
// Android application.
//

$query = "
    UPDATE notifications
    SET is_read = TRUE
    WHERE notification_id = $1
      AND user_id = $2
    RETURNING
        notification_id,
        user_id,
        order_id,
        title,
        message,
        notification_type,
        is_read,
        is_resolved,
        created_at
";

$result = pg_query_params(
    $conn,
    $query,
    [
        $notification_id,
        $user_id
    ]
);

// =========================================================
// DATABASE ERROR
// =========================================================

if (!$result) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to mark notification as read."
    ]);

    exit;
}

// =========================================================
// NOTIFICATION NOT FOUND / NOT OWNED
// =========================================================
//
// If no row was updated, either:
// - the notification does not exist, or
// - it belongs to another user.
//
// We intentionally use the same response for both
// cases so we do not reveal another customer's data.
//

if (pg_num_rows($result) === 0) {

    echo json_encode([
        "success" => false,
        "message" =>
            "Notification not found."
    ]);

    exit;
}

// =========================================================
// FORMAT RESULT
// =========================================================

$notification =
    pg_fetch_assoc($result);

$notification["notification_id"] =
    (int) $notification["notification_id"];

$notification["user_id"] =
    (int) $notification["user_id"];

if ($notification["order_id"] !== null) {

    $notification["order_id"] =
        (int) $notification["order_id"];
}

$notification["is_read"] =
    filter_var(
        $notification["is_read"],
        FILTER_VALIDATE_BOOLEAN
    );

$notification["is_resolved"] =
    filter_var(
        $notification["is_resolved"],
        FILTER_VALIDATE_BOOLEAN
    );

// =========================================================
// RESPONSE
// =========================================================

echo json_encode([
    "success" => true,

    "message" =>
        "Notification marked as read.",

    "notification" =>
        $notification
]);

?>