<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";
require_once "audit.php";


/*
 * ---------------------------------------------------------
 * ONLY POST
 * ---------------------------------------------------------
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
 * ---------------------------------------------------------
 * AUTHENTICATION
 * ---------------------------------------------------------
 */

$user = requireAuth($conn);


/*
 * ---------------------------------------------------------
 * AUTHORIZATION
 * ---------------------------------------------------------
 *
 * Only ADMIN accounts can permanently delete products.
 */

requireRole($user, ["ADMIN"]);


/*
 * ---------------------------------------------------------
 * READ JSON BODY
 * ---------------------------------------------------------
 */

$raw_input = file_get_contents("php://input");

$data = json_decode(
    $raw_input,
    true
);

if (
    json_last_error() !== JSON_ERROR_NONE ||
    !is_array($data)
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * PRODUCT ID
 * ---------------------------------------------------------
 */

if (!isset($data["product_id"])) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Product ID is required."
    ]);

    exit;
}


$product_id =
    filter_var(
        $data["product_id"],
        FILTER_VALIDATE_INT
    );


if (
    $product_id === false ||
    $product_id <= 0
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Invalid product ID."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * BEGIN TRANSACTION
 * ---------------------------------------------------------
 */

if (!pg_query($conn, "BEGIN")) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to start deletion transaction."
    ]);

    exit;
}


try {

    /*
     * -----------------------------------------------------
     * LOCK PRODUCT
     * -----------------------------------------------------
     *
     * Lock the product row so another operation cannot
     * modify/delete it while we are checking it.
     */

    $product_query = "
        SELECT
            product_id,
            product_name,
            image_url
        FROM products
        WHERE product_id = $1
        FOR UPDATE
    ";

    $product_result = pg_query_params(
        $conn,
        $product_query,
        [$product_id]
    );


    if (!$product_result) {

        throw new Exception(
            "Failed to find the product."
        );

    }


    if (pg_num_rows($product_result) === 0) {

        throw new Exception(
            "Product not found."
        );

    }


    $product =
        pg_fetch_assoc(
            $product_result
        );


    /*
     * -----------------------------------------------------
     * CHECK ORDER HISTORY
     * -----------------------------------------------------
     *
     * A product that has already been used in an order
     * must not be permanently deleted.
     */

    $order_check_query = "
        SELECT
            COUNT(*) AS order_item_count
        FROM order_items
        WHERE product_id = $1
    ";

    $order_check_result = pg_query_params(
        $conn,
        $order_check_query,
        [$product_id]
    );


    if (!$order_check_result) {

        throw new Exception(
            "Failed to check product order history."
        );

    }


    $order_check =
        pg_fetch_assoc(
            $order_check_result
        );


    $order_item_count =
        (int)
        $order_check["order_item_count"];


    if ($order_item_count > 0) {

        /*
         * Roll back the transaction because no deletion
         * should occur.
         */

        pg_query(
            $conn,
            "ROLLBACK"
        );


        http_response_code(409);

        echo json_encode([
            "success" => false,

            "message" =>
                "This product cannot be permanently deleted because it has already been used in an order.",

            "product" => [
                "product_id" =>
                    (int)
                    $product["product_id"],

                "product_name" =>
                    $product["product_name"]
            ],

            "order_item_count" =>
                $order_item_count,

            "suggestion" =>
                "Deactivate the product instead to preserve historical order records."
        ]);

        exit;
    }


    /*
     * -----------------------------------------------------
     * DELETE PRODUCT
     * -----------------------------------------------------
     *
     * At this point the product has never appeared in
     * order_items.
     *
     * product_recipes uses ON DELETE CASCADE according
     * to the current database design, so its recipe rows
     * will be removed with the product.
     */

    $delete_query = "
        DELETE FROM products
        WHERE product_id = $1
        RETURNING
            product_id,
            product_name,
            image_url
    ";

    $delete_result = pg_query_params(
        $conn,
        $delete_query,
        [$product_id]
    );


    if (!$delete_result) {

        throw new Exception(
            "Failed to delete product."
        );

    }


    if (pg_num_rows($delete_result) === 0) {

        throw new Exception(
            "Product could not be deleted."
        );

    }


    $deleted_product =
        pg_fetch_assoc(
            $delete_result
        );


    /*
     * -----------------------------------------------------
     * COMMIT DATABASE CHANGE
     * -----------------------------------------------------
     */

    if (!pg_query($conn, "COMMIT")) {

        throw new Exception(
            "Failed to commit product deletion."
        );

    }


    /*
     * -----------------------------------------------------
     * DELETE IMAGE FILE
     * -----------------------------------------------------
     *
     * Only attempt this after the database deletion has
     * successfully committed.
     *
     * The database stores the image URL/path.
     */

    $image_deleted = false;
    $image_delete_error = null;

    $image_url =
        trim(
            (string)
            ($deleted_product["image_url"] ?? "")
        );


    if ($image_url !== "") {

        /*
         * Convert the stored URL/path into a local file
         * path only when it points to our product uploads.
         *
         * This avoids allowing the database value to become
         * an arbitrary filesystem path.
         */

        $uploads_marker =
            "/uploads/products/";

        $marker_position =
            strpos(
                $image_url,
                $uploads_marker
            );


        if ($marker_position !== false) {

            $relative_file =
                substr(
                    $image_url,
                    $marker_position +
                    strlen($uploads_marker)
                );


            /*
             * Prevent path traversal.
             */

            if (
                $relative_file !== "" &&
                $relative_file !== "." &&
                $relative_file !== ".." &&
                strpos(
                    $relative_file,
                    ".."
                ) === false &&
                strpos(
                    $relative_file,
                    "/"
                ) === false &&
                strpos(
                    $relative_file,
                    "\\"
                ) === false
            ) {

                $file_path =
                    __DIR__ .
                    "/../uploads/products/" .
                    $relative_file;


                if (
                    is_file($file_path) &&
                    @unlink($file_path)
                ) {

                    $image_deleted = true;

                }
                elseif (
                    !file_exists($file_path)
                ) {

                    /*
                     * The database deletion is already complete,
                     * so a missing image file is not a database
                     * failure.
                     */

                    $image_deleted = false;

                }
                else {

                    $image_delete_error =
                        "Product was deleted, but the image file could not be removed.";

                }

            }

        }

    }


    /*
     * -----------------------------------------------------
     * AUDIT LOG
     * -----------------------------------------------------
     *
     * The product has already been successfully deleted
     * from the database at this point.
     *
     * Audit logging must not affect the successful deletion.
     */

    logAudit(
        $conn,
        $user,
        "DELETE",
        "PRODUCTS",
        "PRODUCT",
        (int) $deleted_product["product_id"],
        "Deleted a product.",
        [
            "product_id" =>
                (int)
                $deleted_product["product_id"],

            "product_name" =>
                $deleted_product["product_name"],

            "image_url" =>
                $deleted_product["image_url"],

            "image_deleted" =>
                $image_deleted,

            "image_delete_warning" =>
                $image_delete_error
        ]
    );


    /*
     * -----------------------------------------------------
     * SUCCESS
     * -----------------------------------------------------
     */

    http_response_code(200);

    echo json_encode([
        "success" => true,

        "message" =>
            "Product deleted successfully.",

        "product" => [
            "product_id" =>
                (int)
                $deleted_product["product_id"],

            "product_name" =>
                $deleted_product["product_name"]
        ],

        "deleted_by" =>
            (int)
            $user["user_id"],

        "deleted_by_role" =>
            $user["role"],

        "image" => [
            "deleted" =>
                $image_deleted,

            "warning" =>
                $image_delete_error
        ]
    ]);

    exit;

}

catch (Throwable $exception) {

    /*
     * -----------------------------------------------------
     * ROLLBACK
     * -----------------------------------------------------
     */

    pg_query(
        $conn,
        "ROLLBACK"
    );


    error_log(
        "delete_product.php: " .
        $exception->getMessage()
    );


    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to delete product."
    ]);

    exit;
}

?>