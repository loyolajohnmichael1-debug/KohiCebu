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
| Get Form Data
|--------------------------------------------------------------------------
*/

$update_type = trim(
    $_POST["update_type"] ?? ""
);

$title = trim(
    $_POST["title"] ?? ""
);

$description = trim(
    $_POST["description"] ?? ""
);

$start_date = trim(
    $_POST["start_date"] ?? ""
);

$end_date = trim(
    $_POST["end_date"] ?? ""
);


/*
|--------------------------------------------------------------------------
| Published Status
|--------------------------------------------------------------------------
*/

$is_published = false;

if (
    isset($_POST["is_published"]) &&
    (
        $_POST["is_published"] === "1" ||
        $_POST["is_published"] === "true" ||
        $_POST["is_published"] === "on"
    )
) {
    $is_published = true;
}


/*
|--------------------------------------------------------------------------
| Validate Update Type
|--------------------------------------------------------------------------
*/

$allowed_types = [
    "PROMO",
    "ANNOUNCEMENT",
    "NEW_PRODUCT",
    "EVENT"
];

if (
    !in_array(
        $update_type,
        $allowed_types,
        true
    )
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid update type."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Title
|--------------------------------------------------------------------------
*/

if ($title === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Title is required."
    ]);

    exit;
}

if (mb_strlen($title) > 150) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Title must not exceed 150 characters."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Description
|--------------------------------------------------------------------------
*/

if ($description === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Description is required."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Validate Dates
|--------------------------------------------------------------------------
*/

$start_date_value = null;
$end_date_value = null;


/*
| Start Date
*/

if ($start_date !== "") {

    $start_date_object = DateTime::createFromFormat(
        "Y-m-d",
        $start_date
    );

    if (
        !$start_date_object ||
        $start_date_object->format("Y-m-d") !== $start_date
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid start date."
        ]);

        exit;
    }

    $start_date_value = $start_date;
}


/*
| End Date
*/

if ($end_date !== "") {

    $end_date_object = DateTime::createFromFormat(
        "Y-m-d",
        $end_date
    );

    if (
        !$end_date_object ||
        $end_date_object->format("Y-m-d") !== $end_date
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Invalid end date."
        ]);

        exit;
    }

    $end_date_value = $end_date;
}


/*
| End Date cannot be before Start Date
*/

if (
    $start_date_value !== null &&
    $end_date_value !== null &&
    $end_date_value < $start_date_value
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "End date cannot be earlier than start date."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Existing Update
|--------------------------------------------------------------------------
*/

$existing_query = "
    SELECT
        update_id,
        title,
        description,
        update_type,
        image_url,
        start_date,
        end_date,
        is_published
    FROM website_updates
    WHERE update_id = $1
    LIMIT 1
";

$existing_result = pg_query_params(
    $conn,
    $existing_query,
    [$update_id]
);

if (
    !$existing_result ||
    pg_num_rows($existing_result) === 0
) {

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" => "Website update not found."
    ]);

    exit;
}

$existing_update = pg_fetch_assoc(
    $existing_result
);

$old_image_url =
    $existing_update["image_url"];


/*
|--------------------------------------------------------------------------
| Image Upload
|--------------------------------------------------------------------------
*/

$new_image_url = $old_image_url;
$new_uploaded_file = null;

if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    $image = $_FILES["image"];


    /*
    | Upload Error
    */

    if (
        $image["error"] !== UPLOAD_ERR_OK
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Failed to upload image."
        ]);

        exit;
    }


    /*
    | File Size
    */

    $max_file_size = 5 * 1024 * 1024;

    if (
        $image["size"] > $max_file_size
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Image must not exceed 5 MB."
        ]);

        exit;
    }


    /*
    | Validate MIME Type
    */

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime_type = $finfo->file(
        $image["tmp_name"]
    );

    $allowed_mime_types = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];

    if (
        !isset(
            $allowed_mime_types[$mime_type]
        )
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Only JPG, PNG, and WebP images are allowed."
        ]);

        exit;
    }


    /*
    | Validate Actual Image
    */

    if (
        @getimagesize(
            $image["tmp_name"]
        ) === false
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" => "Uploaded file is not a valid image."
        ]);

        exit;
    }


    /*
    | Upload Directory
    */

    $upload_directory =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        "uploads" .
        DIRECTORY_SEPARATOR .
        "website_updates";


    if (
        !is_dir($upload_directory)
    ) {

        if (
            !mkdir(
                $upload_directory,
                0755,
                true
            )
        ) {

            http_response_code(500);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Failed to create image upload directory."
            ]);

            exit;
        }
    }


    /*
    | Generate Filename
    */

    $extension =
        $allowed_mime_types[$mime_type];

    $filename =
        "update_" .
        bin2hex(
            random_bytes(8)
        ) .
        "." .
        $extension;


    $destination =
        $upload_directory .
        DIRECTORY_SEPARATOR .
        $filename;


    /*
    | Move Uploaded File
    */

    if (
        !move_uploaded_file(
            $image["tmp_name"],
            $destination
        )
    ) {

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to save uploaded image."
        ]);

        exit;
    }


    /*
    | Store Relative URL
    */

    $new_image_url =
        "uploads/website_updates/" .
        $filename;

    $new_uploaded_file =
        $destination;
}


/*
|--------------------------------------------------------------------------
| Update Database
|--------------------------------------------------------------------------
*/

$query = "
    UPDATE website_updates
    SET
        title = $1,
        description = $2,
        update_type = $3,
        image_url = $4,
        start_date = $5,
        end_date = $6,
        is_published = $7,
        updated_at = CURRENT_TIMESTAMP
    WHERE update_id = $8
    RETURNING
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
";


$result = pg_query_params(
    $conn,
    $query,
    [
        $title,
        $description,
        $update_type,
        $new_image_url,
        $start_date_value,
        $end_date_value,
        $is_published ? "true" : "false",
        $update_id
    ]
);


/*
|--------------------------------------------------------------------------
| Database Failure
|--------------------------------------------------------------------------
*/

if (!$result) {

    /*
    | Remove newly uploaded image
    | if database update failed.
    */

    if (
        $new_uploaded_file !== null &&
        file_exists($new_uploaded_file)
    ) {
        @unlink(
            $new_uploaded_file
        );
    }

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to update website update."
    ]);

    exit;
}


$updated = pg_fetch_assoc(
    $result
);


/*
|--------------------------------------------------------------------------
| Delete Old Image
|--------------------------------------------------------------------------
|
| Only delete the old image when a NEW image
| was successfully uploaded and saved.
|
*/

if (
    $new_uploaded_file !== null &&
    !empty($old_image_url)
) {

    $old_image_path =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        str_replace(
            "/",
            DIRECTORY_SEPARATOR,
            $old_image_url
        );

    if (
        file_exists($old_image_path) &&
        is_file($old_image_path)
    ) {

        @unlink(
            $old_image_path
        );
    }
}


/*
|--------------------------------------------------------------------------
| Normalize Boolean
|--------------------------------------------------------------------------
*/

$updated["is_published"] =
    ($updated["is_published"] === "t");


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,
    "message" =>
        "Website update updated successfully.",
    "update" => $updated
]);

?>