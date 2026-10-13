<?php
/*
 * KohiCebu Admin Portal
 * Recipe Management
 *
 * Recipe configuration is managed primarily
 * through Products → Recipe.
 *
 * This page provides a system-wide recipe overview.
 */

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

    <title>
        KohiCebu — Recipe Management
    </title>


    <!-- =====================================================
         SHARED ADMIN STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >


    <!-- =====================================================
         RECIPE STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/recipes.css?v=5"
    >
    <link rel="stylesheet" href="css/pagination.css">

</head>


<body>

<div class="app">


    <!-- =====================================================
         SHARED ADMIN SIDEBAR
         ===================================================== -->

    <?php include "includes/sidebar.php"; ?>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main-content">


        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <header class="page-header">

            <div class="page-header-content">

                <div class="page-eyebrow">
                    INVENTORY CONFIGURATION
                </div>

                <h1>
                    Recipe Management
                </h1>

                <p>
                    View and monitor the ingredient recipes
                    configured for KohiCebu products.
                </p>

            </div>


            <!-- CURRENT DATE -->

            <div
                class="header-date"
                id="currentDate"
            >

                <span
                    class="header-date-icon"
                    aria-hidden="true"
                >
                    ▣
                </span>

                <span class="header-date-text">
                    Loading date...
                </span>

            </div>

        </header>


        <!-- =================================================
             SUMMARY
             ================================================= -->

        <section class="recipe-summary-grid">


            <!-- TOTAL PRODUCTS -->

            <div class="recipe-summary-card">

                <div class="recipe-summary-label">
                    PRODUCTS
                </div>

                <div
                    class="recipe-summary-value"
                    id="totalProducts"
                >
                    —
                </div>

                <div class="recipe-summary-description">
                    Menu products
                </div>

            </div>


            <!-- CONFIGURED RECIPES -->

            <div class="recipe-summary-card">

                <div class="recipe-summary-label">
                    CONFIGURED RECIPES
                </div>

                <div
                    class="recipe-summary-value"
                    id="configuredRecipes"
                >
                    —
                </div>

                <div class="recipe-summary-description">
                    Products with recipes
                </div>

            </div>


            <!-- SELLABLE -->

            <div class="recipe-summary-card">

                <div class="recipe-summary-label">
                    SELLABLE
                </div>

                <div
                    class="recipe-summary-value"
                    id="sellableProducts"
                >
                    —
                </div>

                <div class="recipe-summary-description">
                    Currently available
                </div>

            </div>


            <!-- WITHOUT RECIPE -->

            <div class="recipe-summary-card">

                <div class="recipe-summary-label">
                    NO RECIPE
                </div>

                <div
                    class="recipe-summary-value"
                    id="productsWithoutRecipe"
                >
                    —
                </div>

                <div class="recipe-summary-description">
                    Need configuration
                </div>

            </div>

        </section>


        <!-- =================================================
             FILTERS
             ================================================= -->

        <section class="recipe-filter-card">


            <!-- SEARCH -->

            <div class="recipe-filter-group">

                <label for="recipeSearch">
                    Search Product
                </label>

                <input
                    type="search"
                    id="recipeSearch"
                    placeholder="Search by product name..."
                    autocomplete="off"
                >

            </div>


            <!-- CATEGORY -->

            <div class="recipe-filter-group category-filter">

                <label for="recipeCategoryFilter">
                    Category
                </label>

                <select id="recipeCategoryFilter">

                    <option value="">
                        All Categories
                    </option>

                </select>

            </div>


            <!-- ACTION -->

            <div class="recipe-filter-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="refreshRecipesButton"
                >
                    REFRESH
                </button>

            </div>

        </section>


        <!-- =================================================
             RECIPE LIST
             ================================================= -->

        <section
            class="recipe-list-card"
            id="recipeListCard"
        >


            <!-- LIST HEADER -->

            <div class="recipe-list-header">

                <div class="recipe-list-header-content">

                    <span class="recipe-list-label">
                        RECIPE OVERVIEW
                    </span>

                    <h2>
                        Product Recipes
                    </h2>

                    <p>
                        Review the ingredients assigned
                        to each product.
                    </p>

                </div>


                <!-- RESULT COUNT -->

                <div
                    class="recipe-result-count"
                    id="recipeResultCount"
                >

                    <span
                        class="recipe-result-icon"
                        aria-hidden="true"
                    >
                        ▣
                    </span>

                    <span class="recipe-result-text">
                        Loading...
                    </span>

                </div>

            </div>


            <!-- TABLE -->

            <div class="recipe-table-wrapper">

                <table class="recipe-table">

                    <thead>

                        <tr>

                            <th>
                                Product
                            </th>

                            <th>
                                Ingredients
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Max Sellable
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody id="recipeTableBody"></tbody>

                </table>

            </div>

            <nav class="admin-pagination" id="recipesPagination" aria-label="Recipe pages" hidden></nav>

        </section>


        <!-- =================================================
             LOADING STATE
             ================================================= -->

        <section
            class="recipe-state"
            id="recipeLoading"
            hidden
        >

            <div class="state-spinner"></div>

            <p>
                Loading recipe information...
            </p>

        </section>


        <!-- =================================================
             EMPTY STATE
             ================================================= -->

        <section
            class="recipe-state"
            id="recipeEmpty"
            hidden
        >

            <div class="state-icon">
                +
            </div>

            <h3>
                No products found
            </h3>

            <p>
                No products match the current search
                or category filter.
            </p>

        </section>


        <!-- =================================================
             ERROR STATE
             ================================================= -->

        <section
            class="recipe-state error-state"
            id="recipeError"
            hidden
        >

            <div class="state-icon">
                !
            </div>

            <p id="recipeErrorMessage">
                Unable to load recipe information.
            </p>

            <button
                type="button"
                class="secondary-button"
                id="retryRecipeButton"
            >
                TRY AGAIN
            </button>

        </section>


        <!-- =================================================
             ADMIN NOTICE
             ================================================= -->

        <section class="admin-notice">

            <div class="notice-icon">
                !
            </div>

            <div>

                <strong>
                    Recipe Configuration
                </strong>

                <p>
                    Recipe quantities determine how much
                    inventory is consumed when an order is
                    processed. To configure or change a
                    product recipe, use Products → Recipe.
                </p>

            </div>

        </section>


        <!-- =================================================
             RECIPE DETAILS MODAL
             ================================================= -->

        <div
            class="modal-overlay"
            id="recipeDetailsModal"
            hidden
            aria-hidden="true"
        >

            <div
                class="recipe-modal-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="recipeDetailsProductName"
            >


                <!-- MODAL HEADER -->

                <div class="modal-header">

                    <div>

                        <div class="modal-eyebrow">
                            PRODUCT RECIPE
                        </div>

                        <h2
                            id="recipeDetailsProductName"
                        >
                            Product
                        </h2>

                        <p
                            id="recipeDetailsCategory"
                        >
                            Category
                        </p>

                    </div>


                    <button
                        type="button"
                        class="modal-close"
                        id="closeRecipeDetailsModal"
                        aria-label="Close recipe details"
                    >
                        ×
                    </button>

                </div>


                <!-- MODAL BODY -->

                <div class="modal-body">


                    <!-- DESCRIPTION -->

                    <div class="recipe-modal-summary">

                        <div>

                            <strong>
                                Recipe Ingredients
                            </strong>

                            <div>

                                <span
                                    id="recipeDetailsDescription"
                                >
                                    Current ingredient
                                    configuration.
                                </span>

                            </div>

                        </div>


                        <span
                            id="recipeDetailsCount"
                        >
                            0 ingredients
                        </span>

                    </div>


                    <!-- LOADING -->

                    <div
                        class="recipe-state"
                        id="recipeDetailsLoading"
                        hidden
                    >

                        <div class="state-spinner"></div>

                        <p>
                            Loading recipe...
                        </p>

                    </div>


                    <!-- ERROR -->

                    <div
                        class="recipe-state error-state"
                        id="recipeDetailsError"
                        hidden
                    >

                        <div class="state-icon">
                            !
                        </div>

                        <p
                            id="recipeDetailsErrorMessage"
                        >
                            Unable to load recipe.
                        </p>

                    </div>


                    <!-- CONTENT -->

                    <div
                        id="recipeDetailsContent"
                    >


                        <!-- RECIPE TABLE -->

                        <div class="recipe-detail-wrapper">

                            <table
                                class="recipe-detail-table"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Ingredient
                                        </th>

                                        <th>
                                            Quantity
                                        </th>

                                        <th>
                                            Unit
                                        </th>

                                    </tr>

                                </thead>


                                <tbody
                                    id="recipeDetailsTableBody"
                                ></tbody>

                            </table>

                        </div>


                        <!-- INVENTORY CHECK -->

                        <div class="inventory-check-section">

                            <div class="inventory-check-title">
                                Inventory Status
                            </div>

                            <div
                                class="inventory-check-list"
                                id="inventoryCheckList"
                            ></div>

                        </div>


                        <!-- MAX SELLABLE -->

                        <div
                            class="recipe-modal-summary recipe-max-sellable"
                        >

                            <span>
                                Maximum Sellable
                            </span>

                            <strong
                                id="recipeDetailsMaxSellable"
                            >
                                —
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- MODAL FOOTER -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="secondary-button"
                        id="closeRecipeDetailsButton"
                    >
                        CLOSE
                    </button>

                </div>

            </div>

        </div>


        <!-- =================================================
             SYSTEM STATUS
             ================================================= -->

        <section class="system-banner">

            <div class="system-indicator"></div>

            <div>

                <strong>
                    System Status
                </strong>

                <p id="systemStatus">
                    Admin Portal is connecting...
                </p>

            </div>

        </section>

    </main>

</div>


<!-- =========================================================
     RECIPE JAVASCRIPT
     ========================================================= -->

<script src="js/pagination.js"></script>
<script src="js/recipes.js?v=6"></script>

</body>

</html>
