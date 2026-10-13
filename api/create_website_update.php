<?php

/*
 * =========================================================
 * KohiCebu API
 *
 * Create Website Update
 *
 * Creates:
 * - Promotions
 * - Announcements
 * - New Product updates
 * - Events
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
 * ONLY ALLOW POST
 * =========================================================
 */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" =>
            "Only POST requests are allowed."
    ]);

    exit;
}


/*
 * =========================================================
 * GET FORM DATA
 * =========================================================
 */

$update_type =
    isset($_POST["update_type"])
        ? trim($_POST["update_type"])
        : "";

$title =
    isset($_POST["title"])
        ? trim($_POST["title"])
        : "";

$description =
    isset($_POST["description"])
        ? trim($_POST["description"])
        : "";

$start_date =
    isset($_POST["start_date"]) &&
    trim($_POST["start_date"]) !== ""
        ? trim($_POST["start_date"])
        : null;

$end_date =
    isset($_POST["end_date"]) &&
    trim($_POST["end_date"]) !== ""
        ? trim($_POST["end_date"])
        : null;


/*
 * =========================================================
 * PUBLISHED STATUS
 * =========================================================
 *
 * Checkbox behavior:
 *
 * checked   = 1
 * unchecked = 0
 *
 */

$is_published =
    isset($_POST["is_published"]) &&
    (
        $_POST["is_published"] === "1" ||
        $_POST["is_published"] === "true" ||
        $_POST["is_published"] === "on"
    );


/*
 * =========================================================
 * VALIDATE UPDATE TYPE
 * =========================================================
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
        "message" =>
            "Invalid update type."
    ]);

    exit;
}


/*
 * =========================================================
 * VALIDATE TITLE
 * =========================================================
 */

if ($title === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Title is required."
    ]);

    exit;
}

if (mb_strlen($title) > 150) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Title must not exceed 150 characters."
    ]);

    exit;
}


/*
 * =========================================================
 * VALIDATE DESCRIPTION
 * =========================================================
 */

if ($description === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Description is required."
    ]);

    exit;
}


/*
 * =========================================================
 * VALIDATE DATES
 * =========================================================
 */

function validateDateValue($date)
{
    if ($date === null) {
        return true;
    }

    $date_object =
        DateTime::createFromFormat(
            "Y-m-d",
            $date
        );

    return (
        $date_object !== false &&
        $date_object->format("Y-m-d") === $date
    );
}


if (!validateDateValue($start_date)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid start date."
    ]);

    exit;
}


if (!validateDateValue($end_date)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Invalid end date."
    ]);

    exit;
}


/*
 * =========================================================
 * VALIDATE DATE RANGE
 * =========================================================
 */

if (
    $start_date !== null &&
    $end_date !== null &&
    $end_date < $start_date
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "End date cannot be earlier than start date."
    ]);

    exit;
}


/*
 * =========================================================
 * IMAGE UPLOAD
 * =========================================================
 *
 * Image is optional.
 *
 * Allowed:
 * - JPG
 * - JPEG
 * - PNG
 * - WEBP
 *
 */

$image_url = null;

if (
    isset($_FILES["image"]) &&
    $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    $image = $_FILES["image"];


    /*
     * Check upload error.
     */

    if (
        $image["error"] !== UPLOAD_ERR_OK
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Image upload failed."
        ]);

        exit;
    }


    /*
     * Maximum file size:
     *
     * 5 MB
     */

    $max_file_size =
        5 * 1024 * 1024;

    if (
        $image["size"] > $max_file_size
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Image must not exceed 5 MB."
        ]);

        exit;
    }


    /*
     * Detect actual MIME type.
     */

    $finfo =
        finfo_open(FILEINFO_MIME_TYPE);

    $mime_type =
        finfo_file(
            $finfo,
            $image["tmp_name"]
        );

    finfo_close($finfo);


    /*
     * Allowed MIME types.
     */

    $allowed_mime_types = [

        "image/jpeg" => "jpg",

        "image/png" => "png",

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
                "Invalid image format. Use JPG, PNG, or WebP."
        ]);

        exit;
    }


    /*
     * Verify that the uploaded file
     * is actually an image.
     */

    if (
        @getimagesize(
            $image["tmp_name"]
        ) === false
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Uploaded file is not a valid image."
        ]);

        exit;
    }


    /*
     * Website update upload directory.
     *
     * Project:
     *
     * KOHICEBU/
     * ├── api/
     * ├── admin/
     * ├── staff/
     * └── uploads/
     *
     */

    $upload_directory =
        dirname(__DIR__) .
        DIRECTORY_SEPARATOR .
        "uploads" .
        DIRECTORY_SEPARATOR .
        "website_updates";


    /*
     * Create directory if necessary.
     */

    if (
        !is_dir(
            $upload_directory
        )
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
                    "Unable to create image upload directory."
            ]);

            exit;
        }
    }


    /*
     * Generate a random filename.
     *
     * Never trust the original filename.
     */

    $extension =
        $allowed_mime_types[$mime_type];

    $filename =
        "update_" .
        bin2hex(
            random_bytes(16)
        ) .
        "." .
        $extension;


    $destination =
        $upload_directory .
        DIRECTORY_SEPARATOR .
        $filename;


    /*
     * Move uploaded file.
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
                "Unable to save uploaded image."
        ]);

        exit;
    }


    /*
     * Public URL stored in database.
     *
     * Example:
     *
     * uploads/website_updates/update_xxxxx.jpg
     */

    $image_url =
        "uploads/website_updates/" .
        $filename;
}


/*
 * =========================================================
 * INSERT UPDATE
 * =========================================================
 */

$query = "
    INSERT INTO website_updates (
        title,
        description,
        update_type,
        image_url,
        start_date,
        end_date,
        is_published
    )
    VALUES (
        $1,
        $2,
        $3,
        $4,
        $5,
        $6,
        $7
    )
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
        $image_url,
        $start_date,
        $end_date,
        $is_published
    ]
);


/*
 * =========================================================
 * DATABASE ERROR
 * =========================================================
 */

if (!$result) {

    /*
     * If database insertion failed after
     * uploading an image, remove the image
     * so we don't leave an unused file.
     */

    if (
        $image_url !== null
    ) {

        $uploaded_file =
            dirname(__DIR__) .
            DIRECTORY_SEPARATOR .
            str_replace(
                "/",
                DIRECTORY_SEPARATOR,
                $image_url
            );

        if (
            file_exists(
                $uploaded_file
            )
        ) {

            @unlink(
                $uploaded_file
            );
        }
    }


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to create website update."
    ]);

    exit;
}


/*
 * =========================================================
 * GET CREATED UPDATE
 * =========================================================
 */

$update =
    pg_fetch_assoc($result);


/*
 * =========================================================
 * RETURN SUCCESS
 * =========================================================
 */

echo json_encode([
    "success" => true,
    "message" =>
        "Website update created successfully.",
    "update" => $update
]);

?>