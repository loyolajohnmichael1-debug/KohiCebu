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
 * Only ADMIN accounts can create products.
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
 * PRODUCT NAME
 * ---------------------------------------------------------
 */

$product_name = isset($data["product_name"])
    ? trim($data["product_name"])
    : "";

if ($product_name === "") {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Product name is required."
    ]);

    exit;
}

if (mb_strlen($product_name) > 100) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Product name must not exceed 100 characters."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * DESCRIPTION
 * ---------------------------------------------------------
 */

$description = isset($data["description"])
    ? trim($data["description"])
    : null;

if (
    $description !== null &&
    mb_strlen($description) > 5000
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Description is too long."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * CATEGORY
 * ---------------------------------------------------------
 */

$category = isset($data["category"])
    ? trim($data["category"])
    : "";

if ($category === "") {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Product category is required."
    ]);

    exit;
}

if (mb_strlen($category) > 50) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Category must not exceed 50 characters."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * PRICE
 * ---------------------------------------------------------
 */

if (!isset($data["price"])) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Product price is required."
    ]);

    exit;
}

if (
    !is_numeric($data["price"]) ||
    (float) $data["price"] < 0
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Product price must be a valid non-negative number."
    ]);

    exit;
}

$price = round(
    (float) $data["price"],
    2
);


/*
 * ---------------------------------------------------------
 * IMAGE URL
 * ---------------------------------------------------------
 *
 * Actual file uploading will be added later.
 *
 * For now, Admin can provide an image URL.
 */

$image_url = isset($data["image_url"])
    ? trim($data["image_url"])
    : null;

if (
    $image_url !== null &&
    $image_url !== "" &&
    mb_strlen($image_url) > 500
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" =>
            "Image URL must not exceed 500 characters."
    ]);

    exit;
}

if ($image_url === "") {
    $image_url = null;
}


/*
 * ---------------------------------------------------------
 * AVAILABILITY
 * ---------------------------------------------------------
 */

$is_available = true;

if (array_key_exists("is_available", $data)) {

    /*
     * Accept actual boolean values.
     */

    if (
        !is_bool(
            $data["is_available"]
        )
    ) {

        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" =>
                "is_available must be true or false."
        ]);

        exit;
    }

    $is_available =
        $data["is_available"];
}


/*
 * ---------------------------------------------------------
 * INITIAL LEGACY STOCK
 * ---------------------------------------------------------
 *
 * Keep this field for backward compatibility.
 *
 * New recipe-based products should eventually rely
 * on ingredient inventory instead.
 */

$stock_quantity = 0;

if (array_key_exists("stock_quantity", $data)) {

    if (
        filter_var(
            $data["stock_quantity"],
            FILTER_VALIDATE_INT
        ) === false ||
        (int) $data["stock_quantity"] < 0
    ) {

        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" =>
                "stock_quantity must be a non-negative integer."
        ]);

        exit;
    }

    $stock_quantity =
        (int) $data["stock_quantity"];
}


/*
 * ---------------------------------------------------------
 * CHECK DUPLICATE PRODUCT NAME
 * ---------------------------------------------------------
 *
 * Prevent accidental duplicate catalog entries.
 */

$duplicate_query = "
    SELECT
        product_id,
        product_name
    FROM products
    WHERE LOWER(TRIM(product_name)) =
          LOWER(TRIM($1))
    LIMIT 1
";

$duplicate_result = pg_query_params(
    $conn,
    $duplicate_query,
    [$product_name]
);

if (!$duplicate_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to check existing products."
    ]);

    exit;
}

if (pg_num_rows($duplicate_result) > 0) {

    $existing_product =
        pg_fetch_assoc(
            $duplicate_result
        );

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "A product with this name already exists.",
        "existing_product" => [
            "product_id" =>
                (int)
                $existing_product["product_id"],

            "product_name" =>
                $existing_product["product_name"]
        ]
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * CREATE PRODUCT
 * ---------------------------------------------------------
 */

$insert_query = "
    INSERT INTO products
    (
        product_name,
        description,
        category,
        price,
        image_url,
        stock_quantity,
        is_available,
        created_at
    )
    VALUES
    (
        $1,
        $2,
        $3,
        $4,
        $5,
        $6,
        $7,
        CURRENT_TIMESTAMP
    )
    RETURNING
        product_id,
        product_name,
        description,
        category,
        price,
        image_url,
        stock_quantity,
        is_available,
        created_at
";

$insert_result = pg_query_params(
    $conn,
    $insert_query,
    [
        $product_name,
        $description,
        $category,
        $price,
        $image_url,
        $stock_quantity,
        $is_available
    ]
);

if (!$insert_result) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to create product."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * GET CREATED PRODUCT
 * ---------------------------------------------------------
 */

$product =
    pg_fetch_assoc(
        $insert_result
    );


/*
 * ---------------------------------------------------------
 * AUDIT LOG
 * ---------------------------------------------------------
 */

logAudit(
    $conn,
    $user,
    "CREATE",
    "PRODUCTS",
    "PRODUCT",
    (int) $product["product_id"],
    "Created a new product.",
    [
        "product_id" =>
            (int) $product["product_id"],

        "product_name" =>
            $product["product_name"],

        "category" =>
            $product["category"],

        "price" =>
            (float) $product["price"],

        "stock_quantity" =>
            (int) $product["stock_quantity"],

        "is_available" =>
            ($product["is_available"] === "t")
    ]
);


/*
 * ---------------------------------------------------------
 * SUCCESS
 * ---------------------------------------------------------
 */

http_response_code(201);

echo json_encode([
    "success" => true,

    "message" =>
        "Product created successfully.",

    "product" => [
        "product_id" =>
            (int)
            $product["product_id"],

        "product_name" =>
            $product["product_name"],

        "description" =>
            $product["description"],

        "category" =>
            $product["category"],

        "price" =>
            (float)
            $product["price"],

        "image_url" =>
            $product["image_url"],

        "stock_quantity" =>
            (int)
            $product["stock_quantity"],

        "is_available" =>
            ($product["is_available"] === "t"),

        "has_recipe" =>
            false,

        "created_at" =>
            $product["created_at"]
    ],

    "created_by" =>
        (int)
        $user["user_id"],

    "created_by_role" =>
        $user["role"]
]);

?>