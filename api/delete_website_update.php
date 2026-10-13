<?php

header("Content-Type: application/json; charset=utf-8");

require_once "db.php";
require_once "auth.php";


/*
|--------------------------------------------------------------------------
| Request Method
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$user = requireAuth($conn);

requireRole(
    $user,
    ["ADMIN"]
);


/*
|--------------------------------------------------------------------------
| Get Update ID
|--------------------------------------------------------------------------
*/

$update_id = isset($_POST["update_id"])
    ? (int) $_POST["update_id"]
    : 0;

if ($update_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid update ID."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Find Existing Update
|--------------------------------------------------------------------------
*/

$query = "
    SELECT
        update_id,
        title,
        image_url
    FROM website_updates
    WHERE update_id = $1
    LIMIT 1
";

$result = pg_query_params(
    $conn,
    $query,
    [$update_id]
);

if (!$result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to find website update."
    ]);

    exit;
}


if (pg_num_rows($result) === 0) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Website update not found."
    ]);

    exit;
}


$update = pg_fetch_assoc($result);

$old_image_url =
    $update["image_url"];

$update_title =
    $update["title"];


/*
|--------------------------------------------------------------------------
| Delete Database Record
|--------------------------------------------------------------------------
*/

$delete_query = "
    DELETE FROM website_updates
    WHERE update_id = $1
";

$delete_result = pg_query_params(
    $conn,
    $delete_query,
    [$update_id]
);


/*
|--------------------------------------------------------------------------
| Database Delete Failed
|--------------------------------------------------------------------------
*/

if (!$delete_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to delete website update."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Associated Image
|--------------------------------------------------------------------------
|
| The database record has already been deleted
| successfully at this point.
|
| Only remove an image if this update actually
| had one.
|
*/

$image_deleted = false;

if (!empty($old_image_url)) {

    $image_path =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        str_replace(
            "/",
            DIRECTORY_SEPARATOR,
            $old_image_url
        );


    if (
        file_exists($image_path) &&
        is_file($image_path)
    ) {

        if (
            @unlink($image_path)
        ) {

            $image_deleted = true;

        }

    }

}


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" =>
        "Website update deleted successfully.",
    "update_id" =>
        (int) $update_id,
    "title" =>
        $update_title,
    "image_deleted" =>
        $image_deleted
]);

?>