<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "db.php";
require_once "auth.php";


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
 * JSON INPUT
 * ---------------------------------------------------------
 */

$raw_input = file_get_contents("php://input");

$data = json_decode(
    $raw_input,
    true
);

if (json_last_error() !== JSON_ERROR_NONE) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON request."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * REQUIRED FIELDS
 * ---------------------------------------------------------
 */

if (
    !isset($data["items"]) ||
    !is_array($data["items"]) ||
    empty($data["items"])
) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Order items are required."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * AUTHENTICATED USER
 * ---------------------------------------------------------
 *
 * The authenticated account is authoritative.
 * We NEVER trust user_id from the request body.
 *
 * CUSTOMER:
 *     user_id belongs to the customer/order owner.
 *     shift_id belongs to the currently OPEN cashier shift.
 *
 * STAFF:
 *     user_id on orders remains NULL.
 *     shift_id belongs to the authenticated staff member's
 *     active shift.
 *
 * ADMIN:
 *     Admin accounts cannot create customer orders through
 *     this endpoint.
 * ---------------------------------------------------------
 */

$authenticated_user_id =
    (int) $user["user_id"];

$items =
    $data["items"];


/*
 * ---------------------------------------------------------
 * ORDER SOURCE
 * ---------------------------------------------------------
 */

$order_source = isset($data["order_source"])
    ? strtoupper(trim($data["order_source"]))
    : "ONLINE_APP";

$allowed_sources = [
    "ONLINE_APP",
    "WALK_IN_QR"
];

if (!in_array($order_source, $allowed_sources, true)) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Invalid order source."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * PAYMENT METHOD
 * ---------------------------------------------------------
 */

$payment_method = isset($data["payment_method"])
    ? strtoupper(trim($data["payment_method"]))
    : "CASHIER";

$allowed_payment_methods = [
    "ONLINE",
    "CASHIER"
];

if (!in_array($payment_method, $allowed_payment_methods, true)) {

    http_response_code(422);

    echo json_encode([
        "success" => false,
        "message" => "Invalid payment method."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * NEW ORDERS ALWAYS START UNPAID
 * ---------------------------------------------------------
 */

$payment_status = "UNPAID";

$order_status = "AWAITING_PAYMENT";


/*
 * ---------------------------------------------------------
 * ORDER IDENTITY
 * ---------------------------------------------------------
 *
 * order_user_id:
 *     The customer who owns the order.
 *
 * actor_user_id:
 *     The authenticated account performing the operation.
 *
 * shift_id:
 *     The cashier shift responsible for the order.
 *
 * CUSTOMER:
 *     Customer owns the order.
 *     Current OPEN cashier shift owns accountability.
 *
 * STAFF:
 *     Staff does not become the customer.
 *     Their active shift owns accountability.
 * ---------------------------------------------------------
 */

$actor_user_id =
    $authenticated_user_id;

$order_user_id = null;

$shift_id = null;


/*
 * ---------------------------------------------------------
 * START TRANSACTION
 * ---------------------------------------------------------
 */

if (!pg_query($conn, "BEGIN")) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Unable to start order transaction."
    ]);

    exit;
}


try {

    /*
     * -----------------------------------------------------
     * VERIFY AUTHENTICATED USER
     * -----------------------------------------------------
     */

    $user_query = "
        SELECT
            user_id,
            role,
            is_active,
            full_name
        FROM users
        WHERE user_id = $1
    ";

    $user_result = pg_query_params(
        $conn,
        $user_query,
        [$authenticated_user_id]
    );

    if (
        !$user_result ||
        pg_num_rows($user_result) === 0
    ) {

        throw new Exception(
            "User account not found."
        );
    }

    $authenticated_user =
        pg_fetch_assoc($user_result);


    /*
     * -----------------------------------------------------
     * VERIFY ACCOUNT STATUS
     * -----------------------------------------------------
     */

    if ($authenticated_user["is_active"] !== "t") {

        throw new Exception(
            "This account is currently inactive."
        );
    }


    /*
     * -----------------------------------------------------
     * DETERMINE ORDER OWNER / SHIFT
     * -----------------------------------------------------
     *
     * CUSTOMER:
     *
     *     The authenticated customer becomes the order owner.
     *
     *     The currently OPEN cashier shift becomes the
     *     accountability shift for the order.
     *
     * STAFF:
     *
     *     The authenticated staff member does NOT become
     *     the customer.
     *
     *     The staff member's own active shift becomes the
     *     accountability shift.
     *
     * ADMIN:
     *
     *     Admin accounts do not create customer orders
     *     through this endpoint.
     * -----------------------------------------------------
     */

    if ($authenticated_user["role"] === "CUSTOMER") {

        /*
         * -------------------------------------------------
         * CUSTOMER ORDER OWNER
         * -------------------------------------------------
         */

        $order_user_id =
            $authenticated_user_id;


        /*
         * -------------------------------------------------
         * FIND CURRENT OPEN CASHIER SHIFT
         * -------------------------------------------------
         *
         * There should be only one OPEN cashier shift
         * because start_shift.php prevents another cashier
         * from starting while one is already on duty.
         *
         * The shift is therefore the accountability owner
         * for customer orders created during that period.
         *
         * FOR UPDATE locks the selected shift row during
         * this transaction.
         * -------------------------------------------------
         */

        $customer_shift_query = "
            SELECT
                s.shift_id,
                s.user_id,
                s.started_at,
                s.status,
                u.full_name AS cashier_name
            FROM staff_shifts s
            INNER JOIN users u
                ON u.user_id = s.user_id
            WHERE s.status = 'OPEN'
              AND u.role = 'STAFF'
              AND u.is_active = TRUE
            ORDER BY s.started_at DESC
            LIMIT 1
            FOR UPDATE OF s
        ";

        $customer_shift_result = pg_query(
            $conn,
            $customer_shift_query
        );

        if (!$customer_shift_result) {

            throw new Exception(
                "Unable to retrieve the current cashier shift."
            );
        }


        /*
         * -------------------------------------------------
         * NO CASHIER ON DUTY
         * -------------------------------------------------
         */

        if (
            pg_num_rows(
                $customer_shift_result
            ) === 0
        ) {

            throw new Exception(
                "No cashier shift is currently active. "
                . "Please try again when a cashier is on duty."
            );
        }


        $current_cashier_shift =
            pg_fetch_assoc(
                $customer_shift_result
            );


        /*
         * -------------------------------------------------
         * ASSIGN CURRENT SHIFT
         * -------------------------------------------------
         */

        $shift_id =
            (int) $current_cashier_shift["shift_id"];

    } elseif ($authenticated_user["role"] === "STAFF") {

        /*
         * -------------------------------------------------
         * STAFF SHIFT
         * -------------------------------------------------
         *
         * Staff-created orders must belong to the
         * authenticated staff member's active shift.
         *
         * The client is NOT allowed to supply shift_id.
         * -------------------------------------------------
         */

        $shift_query = "
            SELECT
                shift_id,
                user_id,
                started_at,
                status
            FROM staff_shifts
            WHERE user_id = $1
              AND status = 'OPEN'
            ORDER BY started_at DESC
            LIMIT 1
            FOR UPDATE
        ";

        $shift_result = pg_query_params(
            $conn,
            $shift_query,
            [$authenticated_user_id]
        );

        if (!$shift_result) {

            throw new Exception(
                "Unable to retrieve the current staff shift."
            );
        }


        /*
         * STAFF MUST HAVE AN ACTIVE SHIFT.
         */

        if (pg_num_rows($shift_result) === 0) {

            throw new Exception(
                "You do not have an active shift. "
                . "Please start your shift before processing an order."
            );
        }


        $current_shift =
            pg_fetch_assoc($shift_result);

        $shift_id =
            (int) $current_shift["shift_id"];


        /*
         * A staff-created order represents a cashier/
         * walk-in transaction.
         */

        if ($order_source !== "WALK_IN_QR") {

            throw new Exception(
                "Staff-created orders must use WALK_IN_QR as the order source."
            );
        }

    } else {

        /*
         * ADMIN or any unsupported role.
         */

        throw new Exception(
            "This account is not authorized to create customer orders."
        );
    }


    /*
     * -----------------------------------------------------
     * COMBINE DUPLICATE PRODUCTS
     * -----------------------------------------------------
     */

    $combined_items = [];

    foreach ($items as $item) {

        if (
            !isset($item["product_id"]) ||
            !isset($item["quantity"])
        ) {

            throw new Exception(
                "Each item must have product_id and quantity."
            );
        }

        $product_id =
            (int) $item["product_id"];

        $quantity =
            (int) $item["quantity"];

        if (
            $product_id <= 0 ||
            $quantity <= 0
        ) {

            throw new Exception(
                "Invalid product ID or quantity."
            );
        }

        if (!isset($combined_items[$product_id])) {
            $combined_items[$product_id] = 0;
        }

        $combined_items[$product_id] += $quantity;
    }


    /*
     * -----------------------------------------------------
     * VALIDATE PRODUCTS
     * -----------------------------------------------------
     */

    $total_amount = 0;

    $validated_items = [];

    foreach (
        $combined_items as $product_id => $quantity
    ) {

        /*
         * Lock product row.
         */

        $product_query = "
            SELECT
                product_id,
                product_name,
                price,
                stock_quantity,
                is_available
            FROM products
            WHERE product_id = $1
            FOR UPDATE
        ";

        $product_result = pg_query_params(
            $conn,
            $product_query,
            [$product_id]
        );

        if (
            !$product_result ||
            pg_num_rows($product_result) === 0
        ) {

            throw new Exception(
                "Product with ID "
                . $product_id
                . " was not found."
            );
        }

        $product =
            pg_fetch_assoc($product_result);


        /*
         * -------------------------------------------------
         * PRODUCT MUST BE ENABLED
         * -------------------------------------------------
         */

        if ($product["is_available"] !== "t") {

            throw new Exception(
                $product["product_name"]
                . " is currently unavailable."
            );
        }


        /*
         * -------------------------------------------------
         * CHECK WHETHER PRODUCT HAS A RECIPE
         * -------------------------------------------------
         */

        $recipe_query = "
            SELECT COUNT(*) AS recipe_count
            FROM product_recipes
            WHERE product_id = $1
        ";

        $recipe_result = pg_query_params(
            $conn,
            $recipe_query,
            [$product_id]
        );

        if (!$recipe_result) {

            throw new Exception(
                "Failed to check product recipe."
            );
        }

        $recipe_row =
            pg_fetch_assoc($recipe_result);

        $has_recipe =
            ((int) $recipe_row["recipe_count"]) > 0;


        /*
         * -------------------------------------------------
         * INVENTORY AVAILABILITY CHECK
         * -------------------------------------------------
         *
         * Products WITH recipes:
         *     Recipe ingredient inventory is authoritative.
         *
         * Products WITHOUT recipes:
         *     Legacy product stock is used temporarily.
         */

        if ($has_recipe) {

            /*
             * -------------------------------------------------
             * CHECK RECIPE INGREDIENT AVAILABILITY
             * -------------------------------------------------
             */

            $availability_query = "
                SELECT
                    i.ingredient_id,
                    i.ingredient_name,
                    i.current_quantity,
                    i.is_available,
                    pr.quantity_required
                FROM product_recipes pr
                INNER JOIN ingredients i
                    ON i.ingredient_id =
                       pr.ingredient_id
                WHERE pr.product_id = $1
                ORDER BY pr.recipe_id ASC
            ";

            $availability_result =
                pg_query_params(
                    $conn,
                    $availability_query,
                    [$product_id]
                );

            if (!$availability_result) {

                throw new Exception(
                    "Failed to verify recipe inventory for "
                    . $product["product_name"]
                    . "."
                );
            }

            while (
                $ingredient =
                    pg_fetch_assoc(
                        $availability_result
                    )
            ) {

                $required_per_product =
                    (float)
                    $ingredient["quantity_required"];

                $required_total =
                    $required_per_product *
                    $quantity;

                $current_quantity =
                    (float)
                    $ingredient["current_quantity"];


                /*
                 * Ingredient manually disabled.
                 */

                if (
                    $ingredient["is_available"] !== "t"
                ) {

                    throw new Exception(
                        $product["product_name"]
                        . " is currently unavailable because "
                        . $ingredient["ingredient_name"]
                        . " is unavailable."
                    );
                }


                /*
                 * Not enough ingredient.
                 */

                if (
                    $current_quantity <
                    $required_total
                ) {

                    throw new Exception(
                        "Not enough "
                        . $ingredient["ingredient_name"]
                        . " for "
                        . $product["product_name"]
                        . ". Required: "
                        . $required_total
                        . ", Available: "
                        . $current_quantity
                        . "."
                    );
                }
            }

        } else {

            /*
             * -------------------------------------------------
             * LEGACY PRODUCT STOCK
             * -------------------------------------------------
             */

            $stock_quantity =
                (int) $product["stock_quantity"];

            if ($quantity > $stock_quantity) {

                throw new Exception(
                    "Not enough stock for "
                    . $product["product_name"]
                    . ". Available stock: "
                    . $stock_quantity
                );
            }
        }


        /*
         * -------------------------------------------------
         * DATABASE PRICE
         * -------------------------------------------------
         */

        $unit_price =
            $product["price"];


        /*
         * -------------------------------------------------
         * SUBTOTAL
         * -------------------------------------------------
         */

        $subtotal_query = "
            SELECT
                ($1::numeric * $2::numeric)
                AS subtotal
        ";

        $subtotal_result = pg_query_params(
            $conn,
            $subtotal_query,
            [
                $quantity,
                $unit_price
            ]
        );

        if (!$subtotal_result) {

            throw new Exception(
                "Failed to calculate order subtotal."
            );
        }

        $subtotal_row =
            pg_fetch_assoc($subtotal_result);

        $subtotal =
            $subtotal_row["subtotal"];


        /*
         * -------------------------------------------------
         * TOTAL
         * -------------------------------------------------
         */

        $total_query = "
            SELECT
                ($1::numeric + $2::numeric)
                AS total
        ";

        $total_result = pg_query_params(
            $conn,
            $total_query,
            [
                $total_amount,
                $subtotal
            ]
        );

        if (!$total_result) {

            throw new Exception(
                "Failed to calculate order total."
            );
        }

        $total_row =
            pg_fetch_assoc($total_result);

        $total_amount =
            $total_row["total"];


        /*
         * Keep this information so we know later
         * whether to use recipe inventory or legacy
         * product inventory.
         */

        $validated_items[] = [

            "product_id" =>
                (int) $product_id,

            "product_name" =>
                $product["product_name"],

            "quantity" =>
                (int) $quantity,

            "unit_price" =>
                (float) $unit_price,

            "subtotal" =>
                (float) $subtotal,

            "has_recipe" =>
                $has_recipe
        ];
    }


    /*
     * -----------------------------------------------------
     * CREATE ORDER
     * -----------------------------------------------------
     *
     * user_id:
     *     CUSTOMER account only.
     *
     * shift_id:
     *     Current cashier shift.
     *
     * CUSTOMER ORDER:
     *     user_id = customer
     *     shift_id = current OPEN cashier shift
     *
     * STAFF ORDER:
     *     user_id = NULL
     *     shift_id = authenticated staff shift
     *
     * This provides both customer identity and cashier/
     * shift accountability.
     * -----------------------------------------------------
     */

    $order_query = "
        INSERT INTO orders
        (
            user_id,
            shift_id,
            total_amount,
            order_status,
            payment_method,
            payment_status,
            order_source,
            order_date
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
            order_id,
            order_date,
            shift_id
    ";

    $order_result = pg_query_params(
        $conn,
        $order_query,
        [
            $order_user_id,
            $shift_id,
            $total_amount,
            $order_status,
            $payment_method,
            $payment_status,
            $order_source
        ]
    );

    if (!$order_result) {

        throw new Exception(
            "Failed to create order."
        );
    }

    $order =
        pg_fetch_assoc($order_result);

    $order_id =
        (int) $order["order_id"];


    /*
     * -----------------------------------------------------
     * INSERT ORDER ITEMS
     * -----------------------------------------------------
     */

    foreach ($validated_items as $item) {

        $item_query = "
            INSERT INTO order_items
            (
                order_id,
                product_id,
                quantity,
                unit_price
            )
            VALUES
            (
                $1,
                $2,
                $3,
                $4
            )
        ";

        $item_result = pg_query_params(
            $conn,
            $item_query,
            [
                $order_id,
                $item["product_id"],
                $item["quantity"],
                $item["unit_price"]
            ]
        );

        if (!$item_result) {

            throw new Exception(
                "Failed to add order item."
            );
        }
    }


    /*
     * -----------------------------------------------------
     * INVENTORY DEDUCTION
     * -----------------------------------------------------
     *
     * The ACTOR is passed to the inventory function.
     *
     * This is intentionally NOT $order_user_id because
     * a staff-created order has no customer user_id.
     *
     * Therefore:
     *
     *     Customer order:
     *         actor = customer
     *
     *     Staff order:
     *         actor = staff
     *
     * The shift remains responsible for cashier
     * accountability.
     * -----------------------------------------------------
     */

    foreach ($validated_items as $item) {

        if ($item["has_recipe"]) {

            /*
             * NEW INVENTORY SYSTEM
             *
             * Atomic recipe deduction.
             */

            $recipe_deduction_query = "
                SELECT *
                FROM deduct_product_recipe_inventory(
                    $1,
                    $2,
                    $3,
                    $4
                )
            ";

            $recipe_deduction_result =
                pg_query_params(
                    $conn,
                    $recipe_deduction_query,
                    [
                        $item["product_id"],
                        $item["quantity"],
                        $actor_user_id,
                        $order_id
                    ]
                );

            if (!$recipe_deduction_result) {

                throw new Exception(
                    "Failed to deduct recipe inventory for "
                    . $item["product_name"]
                    . "."
                );
            }

        } else {

            /*
             * -------------------------------------------------
             * LEGACY INVENTORY SYSTEM
             * -------------------------------------------------
             */

            $stock_query = "
                UPDATE products
                SET
                    stock_quantity =
                        stock_quantity - $1,

                    is_available =
                        CASE
                            WHEN stock_quantity - $1 <= 0
                            THEN FALSE
                            ELSE TRUE
                        END
                WHERE product_id = $2
            ";

            $stock_result = pg_query_params(
                $conn,
                $stock_query,
                [
                    $item["quantity"],
                    $item["product_id"]
                ]
            );

            if (
                !$stock_result ||
                pg_affected_rows($stock_result) !== 1
            ) {

                throw new Exception(
                    "Failed to update product stock."
                );
            }


            /*
             * Update legacy inventory table.
             */

            $inventory_query = "
                UPDATE inventory
                SET
                    quantity =
                        quantity - $1,

                    updated_at =
                        CURRENT_TIMESTAMP
                WHERE product_id = $2
            ";

            $inventory_result =
                pg_query_params(
                    $conn,
                    $inventory_query,
                    [
                        $item["quantity"],
                        $item["product_id"]
                    ]
                );

            if (
                !$inventory_result ||
                pg_affected_rows(
                    $inventory_result
                ) !== 1
            ) {

                throw new Exception(
                    "Inventory record not found for product ID "
                    . $item["product_id"]
                    . "."
                );
            }
        }
    }


    /*
     * -----------------------------------------------------
     * MARK INVENTORY AS DEDUCTED
     * -----------------------------------------------------
     *
     * No additional deduction happens here.
     * This only records successful inventory processing.
     * -----------------------------------------------------
     */

    $inventory_flag_query = "
        UPDATE orders
        SET
            inventory_deducted = TRUE
        WHERE order_id = $1
    ";

    $inventory_flag_result = pg_query_params(
        $conn,
        $inventory_flag_query,
        [$order_id]
    );

    if (
        !$inventory_flag_result ||
        pg_affected_rows($inventory_flag_result) !== 1
    ) {

        throw new Exception(
            "Failed to update inventory deduction status."
        );
    }


    /*
     * -----------------------------------------------------
     * COMMIT EVERYTHING
     * -----------------------------------------------------
     */

    if (!pg_query($conn, "COMMIT")) {

        throw new Exception(
            "Failed to complete order transaction."
        );
    }


    /*
     * -----------------------------------------------------
     * SUCCESS RESPONSE
     * -----------------------------------------------------
     */

    echo json_encode([

        "success" => true,

        "message" =>
            "Order created successfully.",

        "order" => [

            "order_id" =>
                $order_id,

            "user_id" =>
                $order_user_id,

            "shift_id" =>
                $shift_id,

            "total_amount" =>
                (float) $total_amount,

            "order_status" =>
                $order_status,

            "payment_method" =>
                $payment_method,

            "payment_status" =>
                $payment_status,

            "order_source" =>
                $order_source,

            "inventory_deducted" =>
                true,

            "order_date" =>
                $order["order_date"]
        ],

        "items" =>
            $validated_items
    ]);

}
catch (Throwable $e) {

    /*
     * -----------------------------------------------------
     * ROLLBACK EVERYTHING
     * -----------------------------------------------------
     */

    pg_query(
        $conn,
        "ROLLBACK"
    );

    error_log(
        "add_order.php: "
        . $e->getMessage()
    );

    http_response_code(400);

    echo json_encode([

        "success" => false,

        "message" =>
            $e->getMessage()
    ]);
}

?>