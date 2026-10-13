<?php

require_once "db.php";
require_once "auth.php";

header("Content-Type: application/json; charset=UTF-8");

try {

    $user = requireAuth($conn);

    requireRole($user, ["ADMIN", "STAFF"]);

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {

        http_response_code(405);

        echo json_encode([
            "success" => false,
            "message" => "Only GET requests are allowed."
        ]);

        exit;
    }

    $productId = isset($_GET["product_id"])
        ? (int) $_GET["product_id"]
        : 0;

    if ($productId <= 0) {

        http_response_code(422);

        echo json_encode([
            "success" => false,
            "message" => "A valid product_id is required."
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * PRODUCT
     * ---------------------------------------------------------
     */

    $productQuery = "
        SELECT
            product_id,
            product_name,
            category,
            price,
            is_available
        FROM products
        WHERE product_id = $1
        LIMIT 1
    ";

    $productResult = pg_query_params(
        $conn,
        $productQuery,
        [$productId]
    );

    if (!$productResult) {
        throw new Exception(
            "Unable to retrieve product."
        );
    }

    $product = pg_fetch_assoc(
        $productResult
    );

    if (!$product) {

        http_response_code(404);

        echo json_encode([
            "success" => false,
            "message" => "Product not found."
        ]);

        exit;
    }

    /*
     * ---------------------------------------------------------
     * RECIPE + INVENTORY
     * ---------------------------------------------------------
     */

    $recipeQuery = "
        SELECT
            pr.recipe_id,
            pr.ingredient_id,
            i.ingredient_name,
            pr.quantity_required,
            i.current_quantity,
            i.minimum_quantity,
            i.is_available,
            iu.unit_name,
            iu.unit_code
        FROM product_recipes pr
        INNER JOIN ingredients i
            ON i.ingredient_id = pr.ingredient_id
        INNER JOIN inventory_units iu
            ON iu.unit_id = i.unit_id
        WHERE pr.product_id = $1
        ORDER BY pr.recipe_id ASC
    ";

    $recipeResult = pg_query_params(
        $conn,
        $recipeQuery,
        [$productId]
    );

    if (!$recipeResult) {
        throw new Exception(
            "Unable to retrieve product recipe."
        );
    }

    $recipeCount = pg_num_rows(
        $recipeResult
    );

    /*
     * ---------------------------------------------------------
     * PRODUCT WITHOUT RECIPE
     * ---------------------------------------------------------
     */

    if ($recipeCount === 0) {

        echo json_encode([
            "success" => true,
            "message" =>
                "Product has no configured recipe.",
            "product" => [
                "product_id" =>
                    (int) $product["product_id"],
                "product_name" =>
                    $product["product_name"],
                "category" =>
                    $product["category"],
                "price" =>
                    (float) $product["price"],
                "is_available" =>
                    (bool) $product["is_available"]
            ],
            "has_recipe" => false,
            "can_sell" => false,
            "max_sellable_quantity" => 0,
            "limiting_ingredient" => null,
            "ingredients" => []
        ]);

        exit;
    }

    $ingredients = [];

    $maxSellableQuantity = null;
    $limitingIngredient = null;

    while ($row = pg_fetch_assoc(
        $recipeResult
    )) {

        $required =
            (float) $row["quantity_required"];

        $current =
            (float) $row["current_quantity"];

        /*
         * How many complete products can this
         * ingredient support?
         */
        if ($required > 0) {

            $possibleQuantity =
                (int) floor(
                    $current / $required
                );

        } else {

            $possibleQuantity = 0;
        }

        /*
         * Ingredient itself must be available.
         */
        if ($row["is_available"] !== "t") {
            $possibleQuantity = 0;
        }

        if (
            $maxSellableQuantity === null ||
            $possibleQuantity < $maxSellableQuantity
        ) {

            $maxSellableQuantity =
                $possibleQuantity;

            $limitingIngredient = [
                "ingredient_id" =>
                    (int) $row["ingredient_id"],

                "ingredient_name" =>
                    $row["ingredient_name"],

                "current_quantity" =>
                    $current,

                "quantity_required" =>
                    $required,

                "unit_name" =>
                    $row["unit_name"],

                "unit_code" =>
                    $row["unit_code"],

                "possible_quantity" =>
                    $possibleQuantity
            ];
        }

        $ingredients[] = [
            "recipe_id" =>
                (int) $row["recipe_id"],

            "ingredient_id" =>
                (int) $row["ingredient_id"],

            "ingredient_name" =>
                $row["ingredient_name"],

            "quantity_required" =>
                $required,

            "current_quantity" =>
                $current,

            "minimum_quantity" =>
                (float) $row["minimum_quantity"],

            "is_available" =>
                ($row["is_available"] === "t"),

            "possible_quantity" =>
                $possibleQuantity,

            "unit_name" =>
                $row["unit_name"],

            "unit_code" =>
                $row["unit_code"]
        ];
    }

    if ($maxSellableQuantity === null) {
        $maxSellableQuantity = 0;
    }

    $canSell =
        $maxSellableQuantity > 0 &&
        $product["is_available"] === "t";

    echo json_encode([
        "success" => true,

        "message" =>
            "Product availability calculated successfully.",

        "product" => [
            "product_id" =>
                (int) $product["product_id"],

            "product_name" =>
                $product["product_name"],

            "category" =>
                $product["category"],

            "price" =>
                (float) $product["price"],

            "is_available" =>
                ($product["is_available"] === "t")
        ],

        "has_recipe" => true,

        "can_sell" =>
            $canSell,

        "max_sellable_quantity" =>
            $maxSellableQuantity,

        "limiting_ingredient" =>
            $limitingIngredient,

        "ingredients" =>
            $ingredients
    ]);

}
catch (Throwable $e) {

    error_log(
        "check_product_availability.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to calculate product availability."
    ]);
}

?>