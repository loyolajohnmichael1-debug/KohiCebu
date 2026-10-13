<?php

/*
 * KohiCebu API Authentication Helper
 *
 * Authenticates API requests using a server-generated
 * Bearer token stored as a SHA-256 hash in api_tokens.
 */

/**
 * Get the Authorization header.
 */
function getAuthorizationHeader()
{
    // Standard PHP / Apache
    if (!empty($_SERVER["HTTP_AUTHORIZATION"])) {
        return trim($_SERVER["HTTP_AUTHORIZATION"]);
    }

    // Some Apache configurations
    if (!empty($_SERVER["REDIRECT_HTTP_AUTHORIZATION"])) {
        return trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);
    }

    // Apache fallback
    if (function_exists("getallheaders")) {

        $headers = getallheaders();

        foreach ($headers as $key => $value) {

            if (strtolower($key) === "authorization") {
                return trim($value);
            }
        }
    }

    return null;
}

/**
 * Require a valid authenticated user.
 *
 * Returns:
 *
 * [
 *     "user_id" => int,
 *     "full_name" => string,
 *     "email" => string,
 *     "phone" => string,
 *     "role" => string,
 *     "is_active" => bool
 * ]
 */
function requireAuth($conn)
{
    $authorization = getAuthorizationHeader();

    if (!$authorization) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Authentication required."
        ]);

        exit;
    }

    /*
     * Expected:
     *
     * Authorization: Bearer TOKEN
     */
    if (
        !preg_match(
            '/^Bearer\s+(.+)$/i',
            $authorization,
            $matches
        )
    ) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid authorization format."
        ]);

        exit;
    }

    $token = trim($matches[1]);

    if ($token === "") {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid authentication token."
        ]);

        exit;
    }

    /*
     * Hash the raw token.
     *
     * Only the SHA-256 hash is stored in the database.
     */
    $token_hash = hash(
        "sha256",
        $token
    );

    /*
     * Find the authenticated user.
     *
     * Authentication requires:
     *
     * - matching token hash
     * - token not revoked
     * - token not expired
     * - user account is active
     */
    $query = "
        SELECT
            u.user_id,
            u.full_name,
            u.email,
            u.phone,
            u.role,
            u.is_active
        FROM api_tokens t
        INNER JOIN users u
            ON u.user_id = t.user_id
        WHERE t.token_hash = $1
          AND t.revoked_at IS NULL
          AND t.expires_at > CURRENT_TIMESTAMP
          AND u.is_active = TRUE
        LIMIT 1
    ";

    $result = pg_query_params(
        $conn,
        $query,
        [$token_hash]
    );

    if (
        !$result ||
        pg_num_rows($result) === 0
    ) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired authentication token."
        ]);

        exit;
    }

    $user = pg_fetch_assoc($result);

    $user["user_id"] =
        (int) $user["user_id"];

    /*
     * PostgreSQL returns BOOLEAN values as
     * "t" or "f" strings through pg_fetch_assoc().
     *
     * Convert it explicitly to a PHP boolean.
     */
    $user["is_active"] =
        ($user["is_active"] === "t");

    return $user;
}

/**
 * Require the authenticated user to have
 * one of the specified roles.
 *
 * Example:
 *
 * requireRole($user, ["ADMIN"]);
 *
 * or:
 *
 * requireRole($user, ["STAFF", "ADMIN"]);
 */
function requireRole($user, array $allowed_roles)
{
    if (
        !isset($user["role"]) ||
        !in_array(
            $user["role"],
            $allowed_roles,
            true
        )
    ) {

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" =>
                "You do not have permission to perform this action."
        ]);

        exit;
    }
}

?>