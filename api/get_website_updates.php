<?php

header("Content-Type: application/json; charset=utf-8");

require_once "db.php";


/*
 * ---------------------------------------------------------
 * GET WEBSITE UPDATES
 * ---------------------------------------------------------
 *
 * Returns currently published promotions and updates.
 *
 * Visibility rules:
 *
 * is_published = TRUE
 *
 * AND
 *
 * start_date is NULL OR start_date has already arrived
 *
 * AND
 *
 * end_date is NULL OR end_date has not passed
 *
 * This endpoint is shared by:
 *
 * - KohiCebu Website
 * - KohiCebu Android App
 *
 */


/*
 * ---------------------------------------------------------
 * Only allow GET requests
 * ---------------------------------------------------------
 */

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Query active updates
 * ---------------------------------------------------------
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
        updated_at
    FROM website_updates
    WHERE
        is_published = TRUE
        AND (
            start_date IS NULL
            OR start_date <= CURRENT_DATE
        )
        AND (
            end_date IS NULL
            OR end_date >= CURRENT_DATE
        )
    ORDER BY
        COALESCE(
            start_date,
            DATE '1900-01-01'
        ) DESC,
        update_id DESC
";


$result = pg_query(
    $conn,
    $query
);


/*
 * ---------------------------------------------------------
 * Database query failed
 * ---------------------------------------------------------
 */

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to load website updates."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * Build response
 * ---------------------------------------------------------
 */

$updates = [];

while ($row = pg_fetch_assoc($result)) {

    $updates[] = $row;

}


/*
 * ---------------------------------------------------------
 * Return JSON
 * ---------------------------------------------------------
 */

echo json_encode([
    "success" => true,
    "updates" => $updates
]);

?>