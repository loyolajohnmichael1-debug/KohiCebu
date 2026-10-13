<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";
require_once "audit.php";


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$user = requireAuth($conn);


/*
|--------------------------------------------------------------------------
| Admin Only
|--------------------------------------------------------------------------
*/

requireRole($user, ["ADMIN"]);


/*
|--------------------------------------------------------------------------
| Request Method
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "POST method required."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Read JSON Body
|--------------------------------------------------------------------------
*/

$input = json_decode(
    file_get_contents("php://input"),
    true
);


if (!is_array($input)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request body."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/

$ingredient_name =
    trim($input["ingredient_name"] ?? "");

$description =
    trim($input["description"] ?? "");

$unit_id =
    isset($input["unit_id"])
        ? (int) $input["unit_id"]
        : 0;

$minimum_quantity =
    isset($input["minimum_quantity"])
        ? (float) $input["minimum_quantity"]
        : 0;


/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

if ($ingredient_name === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Ingredient name is required."
    ]);

    exit;
}


if (strlen($ingredient_name) > 100) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Ingredient name must not exceed 100 characters."
    ]);

    exit;
}


if (strlen($description) > 1000) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Description must not exceed 1000 characters."
    ]);

    exit;
}


if ($unit_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A valid inventory unit is required."
    ]);

    exit;
}


if ($minimum_quantity < 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Minimum quantity cannot be negative."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Verify Unit
|--------------------------------------------------------------------------
*/

$unitQuery = "
    SELECT
        unit_id,
        unit_name,
        unit_code,
        unit_type
    FROM inventory_units
    WHERE unit_id = $1
    LIMIT 1
";

$unitResult =
    pg_query_params(
        $conn,
        $unitQuery,
        [$unit_id]
    );


if (!$unitResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to verify inventory unit."
    ]);

    exit;
}


if (pg_num_rows($unitResult) === 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "The selected inventory unit does not exist."
    ]);

    exit;
}


$unit =
    pg_fetch_assoc($unitResult);


/*
|--------------------------------------------------------------------------
| Prevent Duplicate Ingredient Names
|--------------------------------------------------------------------------
*/

$duplicateQuery = "
    SELECT
        ingredient_id,
        ingredient_name
    FROM ingredients
    WHERE LOWER(TRIM(ingredient_name))
        = LOWER(TRIM($1))
    LIMIT 1
";

$duplicateResult =
    pg_query_params(
        $conn,
        $duplicateQuery,
        [$ingredient_name]
    );


if (!$duplicateResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to validate ingredient name."
    ]);

    exit;
}


if (pg_num_rows($duplicateResult) > 0) {

    $duplicate =
        pg_fetch_assoc($duplicateResult);

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "An ingredient with this name already exists.",

        "existing_ingredient_id" =>
            (int) $duplicate["ingredient_id"]
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Insert Ingredient
|--------------------------------------------------------------------------
*/

$insertQuery = "
    INSERT INTO ingredients (
        ingredient_name,
        description,
        unit_id,
        current_quantity,
        minimum_quantity,
        is_available
    )
    VALUES (
        $1,
        NULLIF($2, ''),
        $3,
        0,
        $4,
        TRUE
    )
    RETURNING
        ingredient_id,
        ingredient_name,
        description,
        unit_id,
        current_quantity,
        minimum_quantity,
        is_available,
        created_at,
        updated_at
";


$insertResult =
    pg_query_params(
        $conn,
        $insertQuery,
        [
            $ingredient_name,
            $description,
            $unit_id,
            $minimum_quantity
        ]
    );


if (!$insertResult) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to create ingredient."
    ]);

    exit;
}


$ingredient =
    pg_fetch_assoc($insertResult);


/*
|--------------------------------------------------------------------------
| AUDIT LOG
|--------------------------------------------------------------------------
*/

logAudit(
    $conn,
    $user,
    "CREATE",
    "INVENTORY",
    "INGREDIENT",
    (int) $ingredient["ingredient_id"],
    "Created a new inventory ingredient.",
    [
        "ingredient_id" =>
            (int)
            $ingredient["ingredient_id"],

        "ingredient_name" =>
            $ingredient["ingredient_name"],

        "description" =>
            $ingredient["description"],

        "unit_id" =>
            (int)
            $ingredient["unit_id"],

        "unit_name" =>
            $unit["unit_name"],

        "unit_code" =>
            $unit["unit_code"],

        "unit_type" =>
            $unit["unit_type"],

        "current_quantity" =>
            (float)
            $ingredient["current_quantity"],

        "minimum_quantity" =>
            (float)
            $ingredient["minimum_quantity"],

        "is_available" =>
            filter_var(
                $ingredient["is_available"],
                FILTER_VALIDATE_BOOLEAN
            )
    ]
);


/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

echo json_encode([
    "success" => true,

    "message" =>
        "Ingredient created successfully.",

    "ingredient" => [
        "ingredient_id" =>
            (int)
            $ingredient["ingredient_id"],

        "ingredient_name" =>
            $ingredient["ingredient_name"],

        "description" =>
            $ingredient["description"],

        "unit_id" =>
            (int)
            $ingredient["unit_id"],

        "unit_name" =>
            $unit["unit_name"],

        "unit_code" =>
            $unit["unit_code"],

        "unit_type" =>
            $unit["unit_type"],

        "current_quantity" =>
            (float)
            $ingredient["current_quantity"],

        "minimum_quantity" =>
            (float)
            $ingredient["minimum_quantity"],

        "is_available" =>
            filter_var(
                $ingredient["is_available"],
                FILTER_VALIDATE_BOOLEAN
            ),

        "created_at" =>
            $ingredient["created_at"],

        "created_by" => [
            "user_id" =>
                $user["user_id"],

            "role" =>
                $user["role"]
        ]
    ]
]);

?>