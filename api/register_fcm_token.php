<?php

require_once "db.php";
require_once "auth.php";

header('Content-Type: application/json');

try {

    // =========================================================
    // AUTHENTICATION
    // =========================================================

    $user = requireAuth($conn);

    // =========================================================
    // ONLY CUSTOMERS CAN REGISTER CUSTOMER FCM TOKENS
    // =========================================================

    if (
        !isset($user['role'])
        || strtoupper($user['role']) !== 'CUSTOMER'
    ) {

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Only customer accounts can register FCM tokens.'
        ]);

        exit;
    }

    // =========================================================
    // REQUEST METHOD
    // =========================================================

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

        http_response_code(405);

        echo json_encode([
            'success' => false,
            'message' => 'POST method required.'
        ]);

        exit;
    }

    // =========================================================
    // READ JSON BODY
    // =========================================================

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON request.'
        ]);

        exit;
    }

    // =========================================================
    // FCM TOKEN
    // =========================================================

    $fcmToken =
        isset($input['fcm_token'])
            ? trim($input['fcm_token'])
            : '';

    $deviceName =
        isset($input['device_name'])
            ? trim($input['device_name'])
            : 'Android';

    if ($fcmToken === '') {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'FCM token is required.'
        ]);

        exit;
    }

    // =========================================================
    // BASIC TOKEN LENGTH PROTECTION
    // =========================================================

    if (strlen($fcmToken) > 1000) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid FCM token.'
        ]);

        exit;
    }

    // =========================================================
    // AUTHENTICATED USER ID
    // =========================================================

    $userId = (int) $user['user_id'];

    // =========================================================
    // UPSERT FCM TOKEN
    // =========================================================
    //
    // If this token already exists, update its owner/device.
    // If it does not exist, create it.
    //
    // This also allows a customer to reinstall the app or
    // refresh their Firebase token without creating duplicates.
    // =========================================================

    $sql = "
        INSERT INTO user_fcm_tokens (
            user_id,
            fcm_token,
            device_name,
            created_at,
            updated_at
        )
        VALUES (
            $1,
            $2,
            $3,
            CURRENT_TIMESTAMP,
            CURRENT_TIMESTAMP
        )
        ON CONFLICT (fcm_token)
        DO UPDATE SET
            user_id = EXCLUDED.user_id,
            device_name = EXCLUDED.device_name,
            updated_at = CURRENT_TIMESTAMP
        RETURNING
            fcm_token_id,
            user_id,
            device_name,
            created_at,
            updated_at
    ";

    $result = pg_query_params(
        $conn,
        $sql,
        [
            $userId,
            $fcmToken,
            $deviceName
        ]
    );

    if (!$result) {

        throw new Exception(
            'Failed to register FCM token.'
        );
    }

    $tokenRecord = pg_fetch_assoc($result);

    // =========================================================
    // SUCCESS
    // =========================================================

    echo json_encode([
        'success' => true,
        'message' => 'FCM token registered successfully.',
        'token' => [
            'fcm_token_id' =>
                (int) $tokenRecord['fcm_token_id'],

            'user_id' =>
                (int) $tokenRecord['user_id'],

            'device_name' =>
                $tokenRecord['device_name'],

            'created_at' =>
                $tokenRecord['created_at'],

            'updated_at' =>
                $tokenRecord['updated_at']
        ]
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Server error.'
    ]);
}