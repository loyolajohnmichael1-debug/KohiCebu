<?php

/*
 * =========================================================
 * KohiCebu API
 *
 * Get Admin Website Updates
 *
 * Returns all website updates for the Admin Portal.
 *
 * Includes:
 *
 * - Published
 * - Drafts
 * - Scheduled
 * - Active
 * - Expired
 *
 * ADMIN ONLY
 * =========================================================
 */


header(
    "Content-Type: application/json; charset=utf-8"
);


/*
 * =========================================================
 * DATABASE
 * =========================================================
 */

require_once "db.php";


/*
 * =========================================================
 * AUTHENTICATION
 * =========================================================
 */

require_once "auth.php";


/*
 * =========================================================
 * REQUIRE ADMIN
 * =========================================================
 */

$user = requireAuth($conn);

requireRole(
    $user,
    ["ADMIN"]
);


/*
 * =========================================================
 * ONLY ALLOW GET
 * =========================================================
 */

if (
    $_SERVER["REQUEST_METHOD"] !== "GET"
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" =>
            "Only GET requests are allowed."
    ]);

    exit;
}


/*
 * =========================================================
 * GET ALL UPDATES
 * =========================================================
 *
 * Status rules:
 *
 * DRAFT
 * - is_published = FALSE
 *
 * SCHEDULED
 * - published
 * - start date is in the future
 *
 * ACTIVE
 * - published
 * - start date has arrived
 * - end date has not passed
 *
 * EXPIRED
 * - published
 * - end date has already passed
 *
 * =========================================================
 */

$query = "

    SELECT

        update_id,

        title,

        description,

        update_type,

        image_url,

        start_date,

        end_date,

        is_published,

        created_at,

        updated_at,

        CASE

            WHEN is_published = FALSE
                THEN 'DRAFT'

            WHEN
                start_date IS NOT NULL
                AND start_date > CURRENT_DATE
                THEN 'SCHEDULED'

            WHEN
                end_date IS NOT NULL
                AND end_date < CURRENT_DATE
                THEN 'EXPIRED'

            ELSE 'ACTIVE'

        END AS status

    FROM website_updates

    ORDER BY

        created_at DESC,

        update_id DESC

";


$result = pg_query(
    $conn,
    $query
);


/*
 * =========================================================
 * DATABASE ERROR
 * =========================================================
 */

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to load website updates."
    ]);

    exit;
}


/*
 * =========================================================
 * BUILD UPDATE LIST
 * =========================================================
 */

$updates = [];


while (
    $row = pg_fetch_assoc($result)
) {

    /*
     * Convert PostgreSQL boolean
     * into a proper PHP boolean.
     */

    $row["is_published"] =
        ($row["is_published"] === "t");


    $updates[] = $row;
}


/*
 * =========================================================
 * SUMMARY COUNTS
 * =========================================================
 */

$total_updates =
    count($updates);

$published_updates = 0;

$draft_updates = 0;

$active_updates = 0;


foreach (
    $updates as $update
) {

    if (
        $update["is_published"]
    ) {

        $published_updates++;

    } else {

        $draft_updates++;

    }


    if (
        $update["status"] === "ACTIVE"
    ) {

        $active_updates++;

    }

}


/*
 * =========================================================
 * RETURN RESPONSE
 * =========================================================
 */

echo json_encode([

    "success" => true,

    "updates" => $updates,

    "summary" => [

        "total" =>
            $total_updates,

        "published" =>
            $published_updates,

        "drafts" =>
            $draft_updates,

        "active" =>
            $active_updates

    ]

]);

?>