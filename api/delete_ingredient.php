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

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "DELETE method required."
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

$ingredient_id =
    isset($input["ingredient_id"])
        ? (int) $input["ingredient_id"]
        : 0;


/*
|--------------------------------------------------------------------------
| Validate Ingredient ID
|--------------------------------------------------------------------------
*/

if ($ingredient_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "A valid ingredient ID is required."
    ]);

    exit;
}


/*
|--------------------------------------------------------------------------
| Begin Transaction
|--------------------------------------------------------------------------
*/

if (!pg_query($conn, "BEGIN")) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to start deletion transaction."
    ]);

    exit;
}


try {

    /*
    |--------------------------------------------------------------------------
    | Lock and Retrieve Ingredient
    |--------------------------------------------------------------------------
    */

    $ingredientQuery = "
        SELECT
            ingredient_id,
            ingredient_name,
            current_quantity,
            unit_id
        FROM ingredients
        WHERE ingredient_id = $1
        FOR UPDATE
    ";

    $ingredientResult =
        pg_query_params(
            $conn,
            $ingredientQuery,
            [$ingredient_id]
        );


    if (!$ingredientResult) {

        throw new Exception(
            "Unable to retrieve ingredient."
        );
    }


    if (pg_num_rows($ingredientResult) === 0) {

        pg_query($conn, "ROLLBACK");

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Ingredient not found."
        ]);

        exit;
    }


    $ingredient =
        pg_fetch_assoc(
            $ingredientResult
        );


    /*
    |--------------------------------------------------------------------------
    | Check Current Stock
    |--------------------------------------------------------------------------
    */

    $currentQuantity =
        (float) $ingredient["current_quantity"];


    if ($currentQuantity != 0) {

        pg_query($conn, "ROLLBACK");

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "This ingredient cannot be deleted because it still has inventory stock.",

            "ingredient" => [
                "ingredient_id" =>
                    (int) $ingredient["ingredient_id"],

                "ingredient_name" =>
                    $ingredient["ingredient_name"],

                "current_quantity" =>
                    $currentQuantity
            ]
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Product Recipes
    |--------------------------------------------------------------------------
    */

    $recipeQuery = "
        SELECT
            pr.recipe_id,
            pr.product_id,
            p.product_name
        FROM product_recipes pr
        INNER JOIN products p
            ON p.product_id = pr.product_id
        WHERE pr.ingredient_id = $1
        LIMIT 1
    ";

    $recipeResult =
        pg_query_params(
            $conn,
            $recipeQuery,
            [$ingredient_id]
        );


    if (!$recipeResult) {

        throw new Exception(
            "Unable to check product recipes."
        );
    }


    if (pg_num_rows($recipeResult) > 0) {

        $recipe =
            pg_fetch_assoc($recipeResult);


        pg_query($conn, "ROLLBACK");

        http_response_code(409);

        echo json_encode([
            "success" => false,

            "message" =>
                "This ingredient cannot be deleted because it is currently used in a product recipe.",

            "ingredient" => [
                "ingredient_id" =>
                    (int) $ingredient["ingredient_id"],

                "ingredient_name" =>
                    $ingredient["ingredient_name"]
            ],

            "recipe_usage" => [
                "recipe_id" =>
                    (int) $recipe["recipe_id"],

                "product_id" =>
                    (int) $recipe["product_id"],

                "product_name" =>
                    $recipe["product_name"]
            ]
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Check Inventory Transaction History
    |--------------------------------------------------------------------------
    */

    $transactionQuery = "
        SELECT
            transaction_id,
            transaction_type,
            quantity_change,
            created_at
        FROM inventory_transactions
        WHERE ingredient_id = $1
        ORDER BY transaction_id DESC
        LIMIT 1
    ";

    $transactionResult =
        pg_query_params(
            $conn,
            $transactionQuery,
            [$ingredient_id]
        );


    if (!$transactionResult) {

        throw new Exception(
            "Unable to check inventory transaction history."
        );
    }


    if (pg_num_rows($transactionResult) > 0) {

        $transaction =
            pg_fetch_assoc(
                $transactionResult
            );


        pg_query($conn, "ROLLBACK");

        http_response_code(409);

        echo json_encode([
            "success" => false,

            "message" =>
                "This ingredient cannot be deleted because it has inventory transaction history.",

            "ingredient" => [
                "ingredient_id" =>
                    (int) $ingredient["ingredient_id"],

                "ingredient_name" =>
                    $ingredient["ingredient_name"]
            ],

            "transaction_history" => [
                "transaction_id" =>
                    (int) $transaction["transaction_id"],

                "transaction_type" =>
                    $transaction["transaction_type"],

                "quantity_change" =>
                    (float) $transaction["quantity_change"],

                "created_at" =>
                    $transaction["created_at"]
            ]
        ]);

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Delete Ingredient
    |--------------------------------------------------------------------------
    */

    $deleteQuery = "
        DELETE FROM ingredients
        WHERE ingredient_id = $1
        RETURNING
            ingredient_id,
            ingredient_name,
            unit_id
    ";


    $deleteResult =
        pg_query_params(
            $conn,
            $deleteQuery,
            [$ingredient_id]
        );


    if (!$deleteResult) {

        throw new Exception(
            "Failed to delete ingredient."
        );
    }


    if (pg_num_rows($deleteResult) === 0) {

        throw new Exception(
            "Ingredient could not be deleted."
        );
    }


    $deletedIngredient =
        pg_fetch_assoc(
            $deleteResult
        );


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    if (!pg_query($conn, "COMMIT")) {

        throw new Exception(
            "Failed to commit ingredient deletion."
        );
    }


    /*
    |--------------------------------------------------------------------------
    | AUDIT LOG
    |--------------------------------------------------------------------------
    *
    * The ingredient has now been successfully deleted.
    *
    * We record the important information captured before
    * deletion so the audit trail remains useful even though
    * the ingredient no longer exists.
    */

    logAudit(
        $conn,
        $user,
        "DELETE",
        "INVENTORY",
        "INGREDIENT",
        (int) $deletedIngredient["ingredient_id"],
        "Deleted an inventory ingredient.",
        [
            "ingredient_id" =>
                (int)
                $deletedIngredient["ingredient_id"],

            "ingredient_name" =>
                $deletedIngredient["ingredient_name"],

            "unit_id" =>
                (int)
                $deletedIngredient["unit_id"],

            "current_quantity" =>
                $currentQuantity,

            "deletion_reason" =>
                "Ingredient had zero stock, no product recipe usage, and no inventory transaction history."
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | Success Response
    |--------------------------------------------------------------------------
    */

    echo json_encode([
        "success" => true,

        "message" =>
            "Ingredient deleted successfully.",

        "deleted_ingredient" => [
            "ingredient_id" =>
                (int) $deletedIngredient["ingredient_id"],

            "ingredient_name" =>
                $deletedIngredient["ingredient_name"],

            "unit_id" =>
                (int) $deletedIngredient["unit_id"]
        ],

        "deleted_by" => [
            "user_id" =>
                $user["user_id"],

            "role" =>
                $user["role"]
        ]
    ]);

}
catch (Throwable $e) {

    pg_query($conn, "ROLLBACK");

    error_log(
        "delete_ingredient.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to delete ingredient."
    ]);

    exit;
}

?>