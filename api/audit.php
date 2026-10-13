<?php

/*
 * =========================================================
 * KohiCebu API
 *
 * Audit Logger
 *
 * Reusable helper for recording system activity.
 *
 * =========================================================
 */


/**
 * Record an audit log.
 *
 * @param resource $conn
 * @param array    $user
 * @param string   $actionType
 * @param string   $module
 * @param string|null $targetType
 * @param int|null $targetId
 * @param string $description
 * @param array|null $metadata
 *
 * @return bool
 */
function logAudit(
    $conn,
    $user,
    $actionType,
    $module,
    $targetType = null,
    $targetId = null,
    $description = "",
    $metadata = null
) {

    /*
     * -----------------------------------------------------
     * Get the authenticated user's ID.
     * -----------------------------------------------------
     */

    $userId = null;

    if (
        is_array($user) &&
        isset($user["user_id"]) &&
        $user["user_id"] !== ""
    ) {

        $userId =
            (int) $user["user_id"];

    }


    /*
     * -----------------------------------------------------
     * Get request information.
     * -----------------------------------------------------
     */

    $ipAddress = null;

    if (
        isset($_SERVER["REMOTE_ADDR"]) &&
        $_SERVER["REMOTE_ADDR"] !== ""
    ) {

        $ipAddress =
            $_SERVER["REMOTE_ADDR"];

    }


    $userAgent = null;

    if (
        isset($_SERVER["HTTP_USER_AGENT"]) &&
        $_SERVER["HTTP_USER_AGENT"] !== ""
    ) {

        $userAgent =
            $_SERVER["HTTP_USER_AGENT"];

    }


    /*
     * -----------------------------------------------------
     * Normalize values.
     * -----------------------------------------------------
     */

    $actionType =
        strtoupper(
            trim($actionType)
        );

    $module =
        strtoupper(
            trim($module)
        );

    $targetType =
        $targetType !== null
            ? trim($targetType)
            : null;

    $description =
        trim($description);


    /*
     * -----------------------------------------------------
     * Convert metadata to JSON.
     * -----------------------------------------------------
     */

    $metadataJson = null;

    if (
        $metadata !== null
    ) {

        $metadataJson =
            json_encode(
                $metadata,
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            );

        if (
            $metadataJson === false
        ) {

            $metadataJson = null;

        }

    }


    /*
     * -----------------------------------------------------
     * Insert audit record.
     * -----------------------------------------------------
     */

    $query = "

        INSERT INTO audit_logs (

            user_id,
            action_type,
            module,
            target_type,
            target_id,
            description,
            metadata,
            ip_address,
            user_agent

        )

        VALUES (

            $1,
            $2,
            $3,
            $4,
            $5,
            $6,
            $7::jsonb,
            $8::inet,
            $9

        )

    ";


    $params = [

        $userId,

        $actionType,

        $module,

        $targetType,

        $targetId,

        $description,

        $metadataJson,

        $ipAddress,

        $userAgent

    ];


    $result =
        pg_query_params(
            $conn,
            $query,
            $params
        );


    /*
     * -----------------------------------------------------
     * Return success/failure.
     *
     * Audit logging should not break the main action.
     * -----------------------------------------------------
     */

    if (
        !$result
    ) {

        error_log(
            "KohiCebu audit log failed: "
            . pg_last_error($conn)
        );

        return false;

    }


    return true;

}

?>