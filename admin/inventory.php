<?php

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>KohiCebu Admin - Inventory</title>

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="css/inventory.css"
    >
    <link rel="stylesheet" href="css/pagination.css">

</head>

<body>

<div class="admin-layout">

    <?php require_once "includes/sidebar.php"; ?>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main-content">

        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <header class="page-header">

            <div>

                <h1>Inventory</h1>

                <p>
                    Manage ingredients, stock levels, and inventory movements.
                </p>

            </div>

            <div class="header-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="refreshInventory"
                >
                    Refresh
                </button>

            </div>

        </header>


        <!-- =================================================
             SUMMARY CARDS
             ================================================= -->

        <section class="inventory-summary">

            <div class="summary-card">

                <div class="summary-label">
                    Total Ingredients
                </div>

                <div
                    class="summary-value"
                    id="totalIngredients"
                >
                    0
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    In Stock
                </div>

                <div
                    class="summary-value"
                    id="inStockIngredients"
                >
                    0
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Low Stock
                </div>

                <div
                    class="summary-value"
                    id="lowStockIngredients"
                >
                    0
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    Out of Stock
                </div>

                <div
                    class="summary-value"
                    id="outOfStockIngredients"
                >
                    0
                </div>

            </div>

        </section>


        <!-- =================================================
             INVENTORY TOOLBAR
             ================================================= -->

        <section class="inventory-toolbar">

            <div class="toolbar-left">

                <div class="search-box">

                    <input
                        type="text"
                        id="inventorySearch"
                        placeholder="Search ingredients..."
                        autocomplete="off"
                    >

                </div>


                <select
                    id="stockStatusFilter"
                    class="inventory-filter"
                >

                    <option value="ALL">
                        All Stock
                    </option>

                    <option value="IN_STOCK">
                        In Stock
                    </option>

                    <option value="LOW_STOCK">
                        Low Stock
                    </option>

                    <option value="OUT_OF_STOCK">
                        Out of Stock
                    </option>

                </select>

            </div>


            <div class="toolbar-right">

                <button
                    type="button"
                    class="primary-button"
                    id="addIngredientButton"
                >
                    + Add Ingredient
                </button>

            </div>

        </section>


        <!-- =================================================
             INVENTORY CARD
             ================================================= -->

        <section class="inventory-card">

            <div class="inventory-card-header">

                <div>

                    <h2>Ingredient Inventory</h2>

                    <p>
                        Current ingredient stock and availability.
                    </p>

                </div>

                <div
                    class="inventory-count"
                    id="inventoryCount"
                >
                    0 ingredients
                </div>

            </div>


            <!-- =================================================
                 INVENTORY TABLE
                 ================================================= -->

            <div
                class="inventory-table-wrapper"
                id="inventoryTableWrapper"
                hidden
            >

                <table class="inventory-table">

                    <thead>

                        <tr>

                            <th>
                                Ingredient
                            </th>

                            <th>
                                Unit
                            </th>

                            <th>
                                Current Stock
                            </th>

                            <th>
                                Minimum
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Availability
                            </th>

                            <th>
                                Actions
                            </th>

                        </tr>

                    </thead>

                    <tbody
                        id="inventoryTableBody"
                    >

                    </tbody>

                </table>
            </div>

            <nav class="admin-pagination" id="inventoryPagination" aria-label="Inventory pages" hidden></nav>


            <!-- =================================================
                 LOADING STATE
                 ================================================= -->

            <div
                class="inventory-state"
                id="inventoryLoading"
            >

                <div class="state-title">
                    Loading inventory...
                </div>

                <div class="state-message">
                    Please wait while the latest inventory is retrieved.
                </div>

            </div>


            <!-- =================================================
                 ERROR STATE
                 ================================================= -->

            <div
                class="inventory-state error-state"
                id="inventoryError"
                hidden
            >

                <div class="state-title">
                    Unable to load inventory
                </div>

                <div
                    class="state-message"
                    id="inventoryErrorMessage"
                >
                    Something went wrong while retrieving inventory.
                </div>

            </div>


            <!-- =================================================
                 EMPTY STATE
                 ================================================= -->

            <div
                class="inventory-state"
                id="inventoryEmpty"
                hidden
            >

                <div class="state-title">
                    No ingredients found
                </div>

                <div class="state-message">
                    There are no ingredients matching your current filters.
                </div>

            </div>

        </section>


        <!-- =================================================
             SYSTEM STATUS
             ================================================= -->

        <div
            class="system-status"
            id="systemStatus"
        >
            Inventory system ready.
        </div>

    </main>

</div>


<!-- =========================================================
     ADD / STOCK ACTION MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="inventoryModal"
    hidden
>

    <div class="inventory-modal">

        <div class="modal-header">

            <div>

                <h2 id="inventoryModalTitle">
                    Inventory Action
                </h2>

                <p id="inventoryModalDescription">
                    Update ingredient inventory.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                id="closeInventoryModal"
            >
                ×
            </button>

        </div>


        <div class="modal-body">

            <div
                class="selected-ingredient"
                id="selectedIngredient"
            >
                Select an ingredient.
            </div>


            <div class="form-group">

                <label for="inventoryAction">
                    Action
                </label>

                <select
                    id="inventoryAction"
                >

                    <option value="STOCK_IN">
                        Stock In
                    </option>

                    <option value="STOCK_OUT">
                        Stock Out
                    </option>

                    <option value="WASTE">
                        Record Waste
                    </option>

                    <option value="ADJUSTMENT">
                        Physical Adjustment
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="inventoryQuantity">
                    Quantity
                </label>

                <input
                    type="number"
                    id="inventoryQuantity"
                    min="0"
                    step="0.001"
                    placeholder="Enter quantity"
                >

                <div
                    class="input-help"
                    id="inventoryUnitHelp"
                >
                    Unit: -
                </div>

            </div>


            <div class="form-group">

                <label for="inventoryReason">
                    Reason
                </label>

                <textarea
                    id="inventoryReason"
                    rows="4"
                    maxlength="500"
                    placeholder="Enter the reason for this inventory action..."
                ></textarea>

            </div>


            <div
                class="modal-error"
                id="inventoryModalError"
                hidden
            ></div>

        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="secondary-button"
                id="cancelInventoryAction"
            >
                Cancel
            </button>

            <button
                type="button"
                class="primary-button"
                id="saveInventoryAction"
            >
                Save Changes
            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     ADD INGREDIENT MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="addIngredientModal"
    hidden
>

    <div class="inventory-modal">

        <div class="modal-header">

            <div>

                <h2>
                    Add Ingredient
                </h2>

                <p>
                    Create a new ingredient for future recipes.
                </p>

            </div>

            <button
                type="button"
                class="modal-close"
                id="closeAddIngredientModal"
            >
                ×
            </button>

        </div>


        <div class="modal-body">

            <div class="form-group">

                <label for="newIngredientName">
                    Ingredient Name
                </label>

                <input
                    type="text"
                    id="newIngredientName"
                    maxlength="100"
                    placeholder="e.g. Whipped Cream"
                >

            </div>


            <div class="form-group">

                <label for="newIngredientDescription">
                    Description
                </label>

                <textarea
                    id="newIngredientDescription"
                    rows="3"
                    maxlength="500"
                    placeholder="Describe how this ingredient is used..."
                ></textarea>

            </div>


            <div class="form-group">

                <label for="newIngredientUnit">
                    Unit
                </label>

                <div class="inventory-unit-control">
                    <select id="newIngredientUnit">
                        <option value="">Select unit</option>
                    </select>
                    <button type="button" class="secondary-button inventory-add-unit-button" id="addInventoryUnitButton">
                        + Add Unit
                    </button>
                </div>

            </div>


            <div class="form-group">

                <label for="newIngredientMinimum">
                    Minimum Stock
                </label>

                <input
                    type="number"
                    id="newIngredientMinimum"
                    min="0"
                    step="0.001"
                    value="0"
                    placeholder="e.g. 500"
                >

            </div>


            <div
                class="modal-error"
                id="addIngredientError"
                hidden
            ></div>

        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="secondary-button"
                id="cancelAddIngredient"
            >
                Cancel
            </button>

            <button
                type="button"
                class="primary-button"
                id="saveNewIngredient"
            >
                Create Ingredient
            </button>

        </div>

    </div>

</div>


<!-- ADD INVENTORY UNIT MODAL -->
<div class="modal-overlay" id="addInventoryUnitModal" hidden>
    <div class="inventory-modal">
        <div class="modal-header">
            <div><h2>Add Inventory Unit</h2><p>Configure a unit for ingredient stock tracking.</p></div>
            <button type="button" class="modal-close" id="closeAddInventoryUnitModal" aria-label="Close">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group"><label for="newUnitName">Unit Name</label><input type="text" id="newUnitName" maxlength="50" placeholder="e.g. Grams"></div>
            <div class="form-group"><label for="newUnitCode">Unit Code</label><input type="text" id="newUnitCode" maxlength="20" placeholder="e.g. g"></div>
            <div class="form-group">
                <label for="newUnitType">Unit Type</label>
                <select id="newUnitType">
                    <option value="">Select type</option>
                    <option value="WEIGHT">Weight</option>
                    <option value="VOLUME">Volume</option>
                    <option value="COUNT">Count</option>
                </select>
            </div>
            <div class="modal-error" id="addInventoryUnitError" hidden></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="secondary-button" id="cancelAddInventoryUnit">Cancel</button>
            <button type="button" class="primary-button" id="saveInventoryUnit">Save Unit</button>
        </div>
    </div>
</div>
<script src="js/pagination.js"></script>
<script src="js/inventory.js"></script>

</body>

</html>
