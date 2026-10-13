<?php

header("Content-Type: application/json");

require_once "db.php";
require_once "auth.php";

// =========================================================
// AUTHENTICATION
// =========================================================

$user = requireAuth($conn);

requireRole($user, ["STAFF", "ADMIN"]);

$staff_user_id = (int) $user["user_id"];
$staff_role = $user["role"];

// =========================================================
// REQUEST METHOD
// =========================================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}

// =========================================================
// READ JSON REQUEST
// =========================================================

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

// =========================================================
// VALIDATE REQUEST
// =========================================================

if (
    !isset($data["order_id"]) ||
    !isset($data["items"]) ||
    !isset($data["reason"])
) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order ID, items, and edit reason are required."
    ]);

    exit;
}

$order_id =
    intval($data["order_id"]);

$items =
    $data["items"];

$reason =
    trim((string) $data["reason"]);

// =========================================================
// VALIDATE ORDER ID
// =========================================================

if ($order_id <= 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Invalid order ID."
    ]);

    exit;
}

// =========================================================
// VALIDATE REASON
// =========================================================

if ($reason === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "An edit reason is required."
    ]);

    exit;
}

if (mb_strlen($reason) > 1000) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Edit reason must not exceed 1000 characters."
    ]);

    exit;
}

// =========================================================
// VALIDATE ITEMS ARRAY
// =========================================================

if (!is_array($items)) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "Items must be provided as an array."
    ]);

    exit;
}

// An order must contain at least one item.
if (count($items) === 0) {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" =>
            "An edited order must contain at least one item."
    ]);

    exit;
}

// =========================================================
// NORMALIZE ITEMS
// =========================================================
//
// Duplicate product IDs are combined.
//
// Example:
//
// [
//     {"product_id": 13, "quantity": 1},
//     {"product_id": 13, "quantity": 2}
// ]
//
// becomes:
//
// product 13 => quantity 3
//

$normalized_items = [];

foreach ($items as $index => $item) {

    if (!is_array($item)) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Invalid item at position " .
                ($index + 1) .
                "."
        ]);

        exit;
    }

    if (
        !isset($item["product_id"]) ||
        !isset($item["quantity"])
    ) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Each item must contain product_id and quantity."
        ]);

        exit;
    }

    $product_id =
        intval($item["product_id"]);

    $quantity =
        intval($item["quantity"]);

    if ($product_id <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Invalid product ID at item " .
                ($index + 1) .
                "."
        ]);

        exit;
    }

    if ($quantity <= 0) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Quantity must be greater than zero."
        ]);

        exit;
    }

    if ($quantity > 1000) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Quantity is too large for product ID " .
                $product_id .
                "."
        ]);

        exit;
    }

    if (!isset($normalized_items[$product_id])) {

        $normalized_items[$product_id] = 0;

    }

    $normalized_items[$product_id] += $quantity;

    if ($normalized_items[$product_id] > 1000) {

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Total quantity for product ID " .
                $product_id .
                " cannot exceed 1000."
        ]);

        exit;
    }
}

ksort($normalized_items);

$new_product_ids =
    array_keys($normalized_items);

// =========================================================
// START TRANSACTION
// =========================================================

if (!pg_query($conn, "BEGIN")) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to start database transaction."
    ]);

    exit;
}

// =========================================================
// VERIFY ACTIVE SHIFT
// =========================================================
//
// STAFF users must have an active shift.
//
// ADMIN users can perform administrative edits without
// requiring a cashier shift.
//

if ($staff_role === "STAFF") {

    $active_shift_query = "
        SELECT
            shift_id
        FROM staff_shifts
        WHERE user_id = $1
          AND status = 'OPEN'
        ORDER BY started_at DESC
        LIMIT 1
        FOR UPDATE
    ";

    $active_shift_result =
        pg_query_params(
            $conn,
            $active_shift_query,
            [$staff_user_id]
        );

    if (!$active_shift_result) {

        pg_query($conn, "ROLLBACK");

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to verify your active shift."
        ]);

        exit;
    }

    if (pg_num_rows($active_shift_result) === 0) {

        pg_query($conn, "ROLLBACK");

        http_response_code(403);

        echo json_encode([
            "success" => false,
            "message" =>
                "You must have an active shift before editing an order."
        ]);

        exit;
    }
}

// =========================================================
// GET AND LOCK ORDER
// =========================================================

$order_query = "
    SELECT
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        order_source,
        order_date,
        shift_id
    FROM orders
    WHERE order_id = $1
    FOR UPDATE
";

$order_result =
    pg_query_params(
        $conn,
        $order_query,
        [$order_id]
    );

if (!$order_result) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to verify order."
    ]);

    exit;
}

if (pg_num_rows($order_result) === 0) {

    pg_query($conn, "ROLLBACK");

    http_response_code(404);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order not found."
    ]);

    exit;
}

$current_order =
    pg_fetch_assoc($order_result);

$current_status =
    $current_order["order_status"];

$current_payment_status =
    $current_order["payment_status"];

// =========================================================
// VERIFY ORDER IS EDITABLE
// =========================================================

$editable_statuses = [
    "AWAITING_PAYMENT",
    "Pending",
    "Confirmed"
];

if (
    !in_array(
        $current_status,
        $editable_statuses,
        true
    )
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order cannot be edited from its current status '" .
            $current_status .
            "'."
    ]);

    exit;
}

// =========================================================
// VERIFY PAYMENT STATE
// =========================================================

if (
    strtoupper(
        trim($current_payment_status)
    ) !== "UNPAID"
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(409);

    echo json_encode([
        "success" => false,
        "message" =>
            "Only unpaid orders can be edited using this process."
    ]);

    exit;
}

// =========================================================
// GET CURRENT ORDER ITEMS
// =========================================================

$old_items_query = "
    SELECT
        oi.order_item_id,
        oi.product_id,
        oi.quantity,
        oi.unit_price,
        p.product_name
    FROM order_items AS oi
    INNER JOIN products AS p
        ON p.product_id = oi.product_id
    WHERE oi.order_id = $1
    ORDER BY oi.order_item_id
";

$old_items_result =
    pg_query_params(
        $conn,
        $old_items_query,
        [$order_id]
    );

if (!$old_items_result) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to load the current order items."
    ]);

    exit;
}

$old_items = [];

while (
    $row =
        pg_fetch_assoc(
            $old_items_result
        )
) {

    $product_id =
        (int) $row["product_id"];

    if (!isset($old_items[$product_id])) {

        $old_items[$product_id] = [
            "product_id" =>
                $product_id,

            "product_name" =>
                $row["product_name"],

            "quantity" =>
                0,

            "unit_price" =>
                (float) $row["unit_price"]
        ];
    }

    $old_items[$product_id]["quantity"] +=
        (int) $row["quantity"];
}

// =========================================================
// LOCK NEW PRODUCTS
// =========================================================
//
// Products are locked in product_id order to reduce the
// chance of concurrent edit/order deadlocks.
//

$product_placeholders = [];

$product_params = [];

$param_index = 1;

foreach ($new_product_ids as $product_id) {

    $product_placeholders[] =
        "$" . $param_index;

    $product_params[] =
        $product_id;

    $param_index++;
}

$product_query = "
    SELECT
        product_id,
        product_name,
        price,
        is_available
    FROM products
    WHERE product_id IN (" .
        implode(
            ",",
            $product_placeholders
        ) .
    ")
    ORDER BY product_id
    FOR UPDATE
";

$product_result =
    pg_query_params(
        $conn,
        $product_query,
        $product_params
    );

if (!$product_result) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to verify edited products."
    ]);

    exit;
}

$products = [];

while (
    $row =
        pg_fetch_assoc(
            $product_result
        )
) {

    $product_id =
        (int) $row["product_id"];

    $products[$product_id] = [
        "product_id" =>
            $product_id,

        "product_name" =>
            $row["product_name"],

        "price" =>
            (float) $row["price"],

        "is_available" =>
            $row["is_available"] === "t"
    ];
}

// =========================================================
// VERIFY ALL PRODUCTS EXIST
// =========================================================

foreach ($new_product_ids as $product_id) {

    if (!isset($products[$product_id])) {

        pg_query($conn, "ROLLBACK");

        http_response_code(400);

        echo json_encode([
            "success" => false,
            "message" =>
                "Product ID " .
                $product_id .
                " does not exist."
        ]);

        exit;
    }

    /*
     * The product must still be manually available.
     *
     * Dynamic ingredient availability is checked below.
     */

    if (!$products[$product_id]["is_available"]) {

        pg_query($conn, "ROLLBACK");

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Product '" .
                $products[$product_id]["product_name"] .
                "' is currently unavailable."
        ]);

        exit;
    }
}

// =========================================================
// GET ALL RECIPES FOR NEW PRODUCTS
// =========================================================

$recipe_placeholders = [];

$recipe_params = [];

$param_index = 1;

foreach ($new_product_ids as $product_id) {

    $recipe_placeholders[] =
        "$" . $param_index;

    $recipe_params[] =
        $product_id;

    $param_index++;
}

$recipe_query = "
    SELECT
        pr.product_id,
        pr.ingredient_id,
        pr.quantity_required,
        i.ingredient_name
    FROM product_recipes AS pr
    INNER JOIN ingredients AS i
        ON i.ingredient_id = pr.ingredient_id
    WHERE pr.product_id IN (" .
        implode(
            ",",
            $recipe_placeholders
        ) .
    ")
    ORDER BY
        pr.product_id,
        pr.ingredient_id
";

$recipe_result =
    pg_query_params(
        $conn,
        $recipe_query,
        $recipe_params
    );

if (!$recipe_result) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to load product recipes."
    ]);

    exit;
}

$new_recipes = [];

while (
    $row =
        pg_fetch_assoc(
            $recipe_result
        )
) {

    $product_id =
        (int) $row["product_id"];

    $ingredient_id =
        (int) $row["ingredient_id"];

    if (!isset($new_recipes[$product_id])) {

        $new_recipes[$product_id] = [];

    }

    $new_recipes[$product_id][$ingredient_id] = [
        "ingredient_id" =>
            $ingredient_id,

        "ingredient_name" =>
            $row["ingredient_name"],

        "quantity_required" =>
            (float) $row["quantity_required"]
    ];
}

// =========================================================
// VERIFY EVERY NEW PRODUCT HAS A RECIPE
// =========================================================

foreach ($new_product_ids as $product_id) {

    if (
        !isset($new_recipes[$product_id]) ||
        count($new_recipes[$product_id]) === 0
    ) {

        pg_query($conn, "ROLLBACK");

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Product '" .
                $products[$product_id]["product_name"] .
                "' does not have a configured recipe."
        ]);

        exit;
    }
}

// =========================================================
// BUILD OLD RECIPE REQUIREMENTS
// =========================================================
//
// We derive the existing reservation from the CURRENT
// order items and their recipes.
//
// This is independent from historical inventory transactions.
// The transaction ledger remains the audit record.
//

$old_requirements = [];

/*
 * Load recipes for old products that may no longer be in
 * the edited order.
 */

$old_product_ids =
    array_keys($old_items);

if (count($old_product_ids) > 0) {

    $old_recipe_placeholders = [];

    $old_recipe_params = [];

    $param_index = 1;

    foreach ($old_product_ids as $product_id) {

        $old_recipe_placeholders[] =
            "$" . $param_index;

        $old_recipe_params[] =
            $product_id;

        $param_index++;
    }

    $old_recipe_query = "
        SELECT
            pr.product_id,
            pr.ingredient_id,
            pr.quantity_required,
            i.ingredient_name
        FROM product_recipes AS pr
        INNER JOIN ingredients AS i
            ON i.ingredient_id = pr.ingredient_id
        WHERE pr.product_id IN (" .
            implode(
                ",",
                $old_recipe_placeholders
            ) .
        ")
        ORDER BY
            pr.product_id,
            pr.ingredient_id
    ";

    $old_recipe_result =
        pg_query_params(
            $conn,
            $old_recipe_query,
            $old_recipe_params
        );

    if (!$old_recipe_result) {

        pg_query($conn, "ROLLBACK");

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to load existing order recipes."
        ]);

        exit;
    }

    $old_recipes = [];

    while (
        $row =
            pg_fetch_assoc(
                $old_recipe_result
            )
    ) {

        $product_id =
            (int) $row["product_id"];

        $ingredient_id =
            (int) $row["ingredient_id"];

        if (!isset($old_recipes[$product_id])) {

            $old_recipes[$product_id] = [];

        }

        $old_recipes[$product_id][$ingredient_id] = [
            "ingredient_id" =>
                $ingredient_id,

            "ingredient_name" =>
                $row["ingredient_name"],

            "quantity_required" =>
                (float) $row["quantity_required"]
        ];
    }

    /*
     * Every existing product should have a recipe because
     * the current ordering system requires recipes.
     */

    foreach ($old_items as $product_id => $old_item) {

        if (
            !isset($old_recipes[$product_id]) ||
            count($old_recipes[$product_id]) === 0
        ) {

            pg_query($conn, "ROLLBACK");

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Existing product '" .
                    $old_item["product_name"] .
                    "' does not have a configured recipe. The order cannot be safely edited."
            ]);

            exit;
        }
    }


    /*
     * Calculate old ingredient requirements.
     */

    foreach (
        $old_items
        as $product_id => $old_item
    ) {

        $old_quantity =
            (int) $old_item["quantity"];

        foreach (
            $old_recipes[$product_id]
            as $ingredient_id => $recipe
        ) {

            $required =
                $recipe["quantity_required"]
                * $old_quantity;

            if (
                !isset(
                    $old_requirements[$ingredient_id]
                )
            ) {

                $old_requirements[$ingredient_id] = [
                    "ingredient_id" =>
                        $ingredient_id,

                    "ingredient_name" =>
                        $recipe["ingredient_name"],

                    "quantity" =>
                        0
                ];
            }

            $old_requirements[$ingredient_id]["quantity"] +=
                $required;
        }
    }
}

// =========================================================
// BUILD NEW RECIPE REQUIREMENTS
// =========================================================

$new_requirements = [];

foreach (
    $normalized_items
    as $product_id => $new_quantity
) {

    foreach (
        $new_recipes[$product_id]
        as $ingredient_id => $recipe
    ) {

        $required =
            $recipe["quantity_required"]
            * $new_quantity;

        if (
            !isset(
                $new_requirements[$ingredient_id]
            )
        ) {

            $new_requirements[$ingredient_id] = [
                "ingredient_id" =>
                    $ingredient_id,

                "ingredient_name" =>
                    $recipe["ingredient_name"],

                "quantity" =>
                    0
            ];
        }

        $new_requirements[$ingredient_id]["quantity"] +=
            $required;
    }
}

// =========================================================
// BUILD INVENTORY DIFFERENCE
// =========================================================
//
// positive delta = additional inventory deduction
// negative delta = inventory release
//

$inventory_deltas = [];

$all_ingredient_ids =
    array_unique(
        array_merge(
            array_keys($old_requirements),
            array_keys($new_requirements)
        )
    );

sort($all_ingredient_ids);

foreach ($all_ingredient_ids as $ingredient_id) {

    $old_quantity =
        isset(
            $old_requirements[$ingredient_id]
        )
            ? $old_requirements[$ingredient_id]["quantity"]
            : 0;

    $new_quantity =
        isset(
            $new_requirements[$ingredient_id]
        )
            ? $new_requirements[$ingredient_id]["quantity"]
            : 0;

    $delta =
        $new_quantity -
        $old_quantity;

    if (abs($delta) < 0.0005) {
        continue;
    }

    $ingredient_name =
        isset(
            $new_requirements[$ingredient_id]
        )
            ? $new_requirements[$ingredient_id]["ingredient_name"]
            : $old_requirements[$ingredient_id]["ingredient_name"];

    $inventory_deltas[$ingredient_id] = [
        "ingredient_id" =>
            $ingredient_id,

        "ingredient_name" =>
            $ingredient_name,

        "old_quantity" =>
            $old_quantity,

        "new_quantity" =>
            $new_quantity,

        "delta" =>
            $delta
    ];
}

// =========================================================
// LOCK AFFECTED INGREDIENTS
// =========================================================
//
// All affected ingredients are locked in ascending order.
//
// This is important when multiple staff actions occur
// concurrently.
//

if (count($inventory_deltas) > 0) {

    $ingredient_ids =
        array_keys(
            $inventory_deltas
        );

    sort($ingredient_ids);

    $ingredient_placeholders = [];

    $ingredient_params = [];

    $param_index = 1;

    foreach ($ingredient_ids as $ingredient_id) {

        $ingredient_placeholders[] =
            "$" . $param_index;

        $ingredient_params[] =
            $ingredient_id;

        $param_index++;
    }

    $ingredient_query = "
        SELECT
            ingredient_id,
            ingredient_name,
            current_quantity
        FROM ingredients
        WHERE ingredient_id IN (" .
            implode(
                ",",
                $ingredient_placeholders
            ) .
        ")
        ORDER BY ingredient_id
        FOR UPDATE
    ";

    $ingredient_result =
        pg_query_params(
            $conn,
            $ingredient_query,
            $ingredient_params
        );

    if (!$ingredient_result) {

        pg_query($conn, "ROLLBACK");

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to lock affected inventory."
        ]);

        exit;
    }

    $locked_ingredients = [];

    while (
        $row =
            pg_fetch_assoc(
                $ingredient_result
            )
    ) {

        $ingredient_id =
            (int) $row["ingredient_id"];

        $locked_ingredients[$ingredient_id] = [
            "ingredient_id" =>
                $ingredient_id,

            "ingredient_name" =>
                $row["ingredient_name"],

            "current_quantity" =>
                (float) $row["current_quantity"]
        ];
    }

    // ---------------------------------------------------------
    // Verify all affected ingredients exist.
    // ---------------------------------------------------------

    foreach ($ingredient_ids as $ingredient_id) {

        if (
            !isset(
                $locked_ingredients[$ingredient_id]
            )
        ) {

            pg_query($conn, "ROLLBACK");

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Ingredient ID " .
                    $ingredient_id .
                    " could not be found."
            ]);

            exit;
        }
    }


    // ---------------------------------------------------------
    // Verify additional inventory is available.
    // ---------------------------------------------------------

    foreach (
        $inventory_deltas
        as $ingredient_id => $delta_data
    ) {

        $delta =
            (float) $delta_data["delta"];

        if ($delta <= 0) {
            continue;
        }

        $available =
            $locked_ingredients[$ingredient_id]["current_quantity"];

        if ($available < $delta) {

            pg_query($conn, "ROLLBACK");

            http_response_code(409);

            echo json_encode([
                "success" => false,
                "message" =>
                    "Insufficient inventory for ingredient '" .
                    $locked_ingredients[$ingredient_id]["ingredient_name"] .
                    "'. Additional required: " .
                    number_format(
                        $delta,
                        3,
                        ".",
                        ""
                    ) .
                    ", available: " .
                    number_format(
                        $available,
                        3,
                        ".",
                        ""
                    ) .
                    "."
            ]);

            exit;
        }
    }
}

// =========================================================
// APPLY INVENTORY DIFFERENCE
// =========================================================

$inventory_changes = [];

foreach (
    $inventory_deltas
    as $ingredient_id => $delta_data
) {

    $delta =
        (float) $delta_data["delta"];

    if (abs($delta) < 0.0005) {
        continue;
    }

    /*
     * Positive delta:
     *
     * New recipe requires more inventory.
     * Deduct the additional amount.
     */

    if ($delta > 0) {

        $transaction_type =
            "ORDER_DEDUCTION";

        $quantity_change =
            -$delta;

        $transaction_reason =
            "Additional inventory deduction for edited order. " .
            "Ingredient: " .
            $delta_data["ingredient_name"] .
            ".";
    }

    /*
     * Negative delta:
     *
     * New recipe requires less inventory.
     * Release the difference.
     */

    else {

        $transaction_type =
            "REVERSAL";

        $quantity_change =
            abs($delta);

        $transaction_reason =
            "Inventory release from edited order. " .
            "Ingredient: " .
            $delta_data["ingredient_name"] .
            ".";
    }

    $transaction_query = "
        SELECT
            t.transaction_id,
            t.quantity_before,
            t.quantity_after
        FROM apply_inventory_transaction(
            $1,
            $2,
            $3,
            $4,
            $5,
            $6
        ) AS t
    ";

    $transaction_result =
        pg_query_params(
            $conn,
            $transaction_query,
            [
                $ingredient_id,
                $staff_user_id,
                $transaction_type,
                $quantity_change,
                $order_id,
                $transaction_reason
            ]
        );

    if (
        !$transaction_result ||
        pg_num_rows($transaction_result) === 0
    ) {

        pg_query($conn, "ROLLBACK");

        http_response_code(409);

        echo json_encode([
            "success" => false,
            "message" =>
                "Order could not be edited because the inventory update failed."
        ]);

        exit;
    }

    $transaction_row =
        pg_fetch_assoc(
            $transaction_result
        );

    $inventory_changes[] = [
        "transaction_id" =>
            (int) $transaction_row["transaction_id"],

        "ingredient_id" =>
            $ingredient_id,

        "ingredient_name" =>
            $delta_data["ingredient_name"],

        "transaction_type" =>
            $transaction_type,

        "quantity_change" =>
            (float) $quantity_change,

        "quantity_before" =>
            (float) $transaction_row["quantity_before"],

        "quantity_after" =>
            (float) $transaction_row["quantity_after"]
    ];
}

// =========================================================
// DELETE OLD ORDER ITEMS
// =========================================================

$delete_items_query = "
    DELETE FROM order_items
    WHERE order_id = $1
";

$delete_items_result =
    pg_query_params(
        $conn,
        $delete_items_query,
        [$order_id]
    );

if (!$delete_items_result) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to replace the existing order items."
    ]);

    exit;
}

// =========================================================
// INSERT NEW ORDER ITEMS
// =========================================================

$total_amount = 0;

$inserted_items = [];

foreach (
    $normalized_items
    as $product_id => $quantity
) {

    $unit_price =
        $products[$product_id]["price"];

    $subtotal =
        $unit_price *
        $quantity;

    $total_amount +=
        $subtotal;

    $insert_item_query = "
        INSERT INTO order_items (
            order_id,
            product_id,
            quantity,
            unit_price
        )
        VALUES (
            $1,
            $2,
            $3,
            $4
        )
        RETURNING
            order_item_id
    ";

    $insert_item_result =
        pg_query_params(
            $conn,
            $insert_item_query,
            [
                $order_id,
                $product_id,
                $quantity,
                $unit_price
            ]
        );

    if (
        !$insert_item_result ||
        pg_num_rows($insert_item_result) === 0
    ) {

        pg_query($conn, "ROLLBACK");

        http_response_code(500);

        echo json_encode([
            "success" => false,
            "message" =>
                "Failed to save the edited order items."
        ]);

        exit;
    }

    $insert_item_row =
        pg_fetch_assoc(
            $insert_item_result
        );

    $inserted_items[] = [
        "order_item_id" =>
            (int) $insert_item_row["order_item_id"],

        "product_id" =>
            $product_id,

        "product_name" =>
            $products[$product_id]["product_name"],

        "quantity" =>
            $quantity,

        "unit_price" =>
            $unit_price,

        "subtotal" =>
            $subtotal
    ];
}

// =========================================================
// UPDATE ORDER TOTAL
// =========================================================

$update_order_query = "
    UPDATE orders
    SET total_amount = $1
    WHERE order_id = $2
    RETURNING
        order_id,
        user_id,
        total_amount,
        order_status,
        payment_method,
        payment_status,
        order_source,
        order_date,
        shift_id
";

$update_order_result =
    pg_query_params(
        $conn,
        $update_order_query,
        [
            $total_amount,
            $order_id
        ]
    );

if (
    !$update_order_result ||
    pg_num_rows($update_order_result) === 0
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to update the order total."
    ]);

    exit;
}

$updated_order =
    pg_fetch_assoc(
        $update_order_result
    );

// =========================================================
// RECORD EDIT AUDIT LOG
// =========================================================

$action_log_query = "
    INSERT INTO order_action_logs (
        order_id,
        user_id,
        action_type,
        reason,
        previous_order_status,
        new_order_status,
        previous_payment_status,
        new_payment_status
    )
    VALUES (
        $1,
        $2,
        'EDIT',
        $3,
        $4,
        $5,
        $6,
        $7
    )
    RETURNING
        action_id,
        created_at
";

$action_log_result =
    pg_query_params(
        $conn,
        $action_log_query,
        [
            $order_id,
            $staff_user_id,
            $reason,
            $current_status,
            $current_status,
            $current_payment_status,
            $current_payment_status
        ]
    );

if (
    !$action_log_result ||
    pg_num_rows($action_log_result) === 0
) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Order could not be edited because the audit record could not be created."
    ]);

    exit;
}

$action_log =
    pg_fetch_assoc(
        $action_log_result
    );

// =========================================================
// COMMIT
// =========================================================

if (!pg_query($conn, "COMMIT")) {

    pg_query($conn, "ROLLBACK");

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" =>
            "Failed to complete order edit."
    ]);

    exit;
}

// =========================================================
// FORMAT RESPONSE
// =========================================================

$updated_order["order_id"] =
    (int) $updated_order["order_id"];

$updated_order["user_id"] =
    $updated_order["user_id"] !== null
        ? (int) $updated_order["user_id"]
        : null;

$updated_order["total_amount"] =
    (float) $updated_order["total_amount"];

$updated_order["shift_id"] =
    $updated_order["shift_id"] !== null
        ? (int) $updated_order["shift_id"]
        : null;

$action_log["action_id"] =
    (int) $action_log["action_id"];

// =========================================================
// SUCCESS RESPONSE
// =========================================================

echo json_encode([
    "success" => true,

    "message" =>
        "Order edited successfully.",

    "edited_by" => [
        "user_id" =>
            $staff_user_id,

        "role" =>
            $staff_role
    ],

    "order" =>
        $updated_order,

    "edit" => [
        "reason" =>
            $reason,

        "action_id" =>
            $action_log["action_id"],

        "created_at" =>
            $action_log["created_at"]
    ],

    "items" =>
        $inserted_items,

    "inventory" => [
        "changes" =>
            $inventory_changes
    ]
]);

?>