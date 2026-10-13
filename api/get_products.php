<?php

header("Content-Type: application/json");

require_once "db.php";

// =========================================================
// REQUEST METHOD
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "GET") {

    echo json_encode([
        "success" => false,
        "message" => "Only GET requests are allowed."
    ]);

    exit;
}

try {

    // =====================================================
    // GET PRODUCTS
    // =====================================================

    $query = "
        SELECT
            p.product_id,
            p.product_name,
            p.description,
            p.category,
            p.price,
            p.image_url,
            p.stock_quantity,
            p.is_available,
            p.created_at
        FROM products p
        ORDER BY p.category, p.product_name
    ";

    $result = pg_query(
        $conn,
        $query
    );

    if (!$result) {

        throw new Exception(
            "Failed to retrieve products."
        );
    }


    // =====================================================
    // FORMAT PRODUCTS
    // =====================================================

    $products = [];

    while ($row = pg_fetch_assoc($result)) {

        $productId =
            (int) $row["product_id"];

        $productIsAvailable =
            ($row["is_available"] === "t");


        // =================================================
        // GET RECIPE INGREDIENTS
        // =================================================

        $recipeQuery = "
            SELECT
                pr.ingredient_id,
                pr.quantity_required,
                i.current_quantity,
                i.is_available
            FROM product_recipes pr
            INNER JOIN ingredients i
                ON i.ingredient_id =
                   pr.ingredient_id
            WHERE pr.product_id = $1
        ";

        $recipeResult =
            pg_query_params(
                $conn,
                $recipeQuery,
                [$productId]
            );

        if (!$recipeResult) {

            throw new Exception(
                "Failed to retrieve recipe for product ID "
                . $productId
            );
        }


        $recipeCount =
            pg_num_rows(
                $recipeResult
            );


        $hasRecipe =
            $recipeCount > 0;

        $maxSellableQuantity = 0;


        // =================================================
        // CALCULATE RECIPE AVAILABILITY
        // =================================================

        if ($hasRecipe) {

            $maxSellableQuantity = null;

            while (
                $recipe =
                    pg_fetch_assoc(
                        $recipeResult
                    )
            ) {

                $required =
                    (float)
                    $recipe["quantity_required"];

                $current =
                    (float)
                    $recipe["current_quantity"];

                $ingredientIsAvailable =
                    ($recipe["is_available"] === "t");


                if (
                    $required > 0 &&
                    $ingredientIsAvailable
                ) {

                    $possibleQuantity =
                        (int) floor(
                            $current / $required
                        );

                } else {

                    $possibleQuantity = 0;
                }


                if (
                    $maxSellableQuantity === null
                    ||
                    $possibleQuantity <
                    $maxSellableQuantity
                ) {

                    $maxSellableQuantity =
                        $possibleQuantity;
                }
            }


            if ($maxSellableQuantity === null) {

                $maxSellableQuantity = 0;
            }
        }


        // =================================================
        // FINAL SELLABILITY
        // =================================================
        //
        // Admin manual availability remains authoritative.
        //
        // Recipe availability is also required.
        //

        $canSell =
            $productIsAvailable
            &&
            $hasRecipe
            &&
            $maxSellableQuantity > 0;


        // =================================================
        // PRODUCT RESPONSE
        // =================================================

        $products[] = [

            "product_id" =>
                $productId,

            "product_name" =>
                $row["product_name"],

            "description" =>
                $row["description"],

            "category" =>
                $row["category"],

            "price" =>
                (float) $row["price"],

            "image_url" =>
                $row["image_url"],

            /*
             * Keep the existing field for backward
             * compatibility.
             */
            "stock_quantity" =>
                (int) $row["stock_quantity"],

            /*
             * Admin-controlled availability.
             */
            "is_available" =>
                $productIsAvailable,

            /*
             * New recipe-aware availability fields.
             */
            "has_recipe" =>
                $hasRecipe,

            "can_sell" =>
                $canSell,

            "max_sellable_quantity" =>
                $maxSellableQuantity,

            "created_at" =>
                $row["created_at"]
        ];
    }


    // =====================================================
    // RESPONSE
    // =====================================================

    echo json_encode([

        "success" => true,

        "message" =>
            "Products retrieved successfully.",

        "products" =>
            $products
    ]);

}
catch (Throwable $e) {

    error_log(
        "get_products.php: " .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode([

        "success" => false,

        "message" =>
            "Failed to retrieve products."
    ]);
}

?>