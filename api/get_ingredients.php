<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";

try {

    // =====================================================
    // AUTHENTICATION
    // =====================================================

    $user = requireAuth($conn);

    requireRole($user, ["ADMIN", "STAFF"]);


    // =====================================================
    // REQUEST METHOD
    // =====================================================

    if ($_SERVER["REQUEST_METHOD"] !== "GET") {

        http_response_code(405);

        echo json_encode([
            "success" => false,
            "message" => "Only GET requests are allowed."
        ]);

        exit;
    }


    // =====================================================
    // GET INGREDIENTS
    // =====================================================
    //
    // Availability is now calculated from actual stock.
    //
    // current_quantity <= 0
    //     -> OUT_OF_STOCK
    //     -> is_available = false
    //
    // current_quantity <= minimum_quantity
    //     -> LOW_STOCK
    //     -> is_available = true
    //
    // current_quantity > minimum_quantity
    //     -> IN_STOCK
    //     -> is_available = true
    //
    // The database remains the source of truth.
    // =====================================================

    $query = "
        SELECT
            i.ingredient_id,
            i.ingredient_name,
            i.description,
            i.unit_id,
            iu.unit_name,
            iu.unit_code,
            iu.unit_type,
            i.current_quantity,
            i.minimum_quantity,
            i.created_at,
            i.updated_at,

            CASE
                WHEN i.current_quantity <= 0
                    THEN 'OUT_OF_STOCK'

                WHEN i.current_quantity <= i.minimum_quantity
                    THEN 'LOW_STOCK'

                ELSE 'IN_STOCK'
            END AS stock_status,

            CASE
                WHEN i.current_quantity <= 0
                    THEN FALSE

                ELSE TRUE
            END AS calculated_is_available

        FROM ingredients i

        INNER JOIN inventory_units iu
            ON iu.unit_id = i.unit_id

        ORDER BY
            i.ingredient_name ASC
    ";


    $result =
        pg_query(
            $conn,
            $query
        );


    if (!$result) {

        throw new Exception(
            "Failed to retrieve ingredients."
        );
    }


    // =====================================================
    // FORMAT RESPONSE
    // =====================================================

    $ingredients = [];


    while (
        $row =
            pg_fetch_assoc($result)
    ) {

        $currentQuantity =
            (float) $row["current_quantity"];


        $minimumQuantity =
            (float) $row["minimum_quantity"];


        $stockStatus =
            $row["stock_status"];


        $isAvailable =
            ($row["calculated_is_available"] === "t");


        $ingredients[] = [

            "ingredient_id" =>
                (int) $row["ingredient_id"],

            "ingredient_name" =>
                $row["ingredient_name"],

            "description" =>
                $row["description"],

            "unit_id" =>
                (int) $row["unit_id"],

            "unit_name" =>
                $row["unit_name"],

            "unit_code" =>
                $row["unit_code"],

            "unit_type" =>
                $row["unit_type"],

            "current_quantity" =>
                $currentQuantity,

            "minimum_quantity" =>
                $minimumQuantity,

            "stock_status" =>
                $stockStatus,

            "is_available" =>
                $isAvailable,

            "created_at" =>
                $row["created_at"],

            "updated_at" =>
                $row["updated_at"]
        ];
    }


    // =====================================================
    // RESPONSE
    // =====================================================

    echo json_encode([

        "success" =>
            true,

        "message" =>
            "Ingredients retrieved successfully.",

        "count" =>
            count($ingredients),

        "ingredients" =>
            $ingredients
    ]);

}
catch (Throwable $e) {

    error_log(
        "get_ingredients.php: " .
        $e->getMessage()
    );


    http_response_code(500);


    echo json_encode([

        "success" =>
            false,

        "message" =>
            "Unable to retrieve ingredients."
    ]);
}

?>