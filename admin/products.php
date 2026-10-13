<?php
/*
 * KohiCebu Admin Portal
 * Product Management
 *
 * Product creation and configuration is ADMIN-only.
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

    <title>KohiCebu — Product Management</title>


    <!-- =====================================================
         SHARED ADMIN PORTAL STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >


    <!-- =====================================================
         PRODUCT PAGE STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/products.css"
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

            <div>

                <div class="page-eyebrow">
                    PRODUCT MANAGEMENT
                </div>

                <h1>
                    Products
                </h1>

                <p>
                    Manage KohiCebu products, pricing,
                    availability, and product images.
                </p>

            </div>


            <div class="header-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="refreshProductsButton"
                >
                    REFRESH
                </button>

                <button
                    type="button"
                    class="primary-button"
                    id="addProductButton"
                >
                    + ADD PRODUCT
                </button>

            </div>

        </header>


        <!-- =================================================
             PRODUCT SUMMARY
             ================================================= -->

        <section class="summary-grid">

            <div class="summary-card">

                <div class="summary-label">
                    TOTAL PRODUCTS
                </div>

                <div
                    class="summary-value"
                    id="totalProducts"
                >
                    0
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    ACTIVE
                </div>

                <div
                    class="summary-value"
                    id="activeProducts"
                >
                    0
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    INACTIVE
                </div>

                <div
                    class="summary-value"
                    id="inactiveProducts"
                >
                    0
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    WITH RECIPE
                </div>

                <div
                    class="summary-value"
                    id="recipeProducts"
                >
                    0
                </div>

            </div>

        </section>


        <!-- =================================================
             FILTERS
             ================================================= -->

        <section class="filter-card">

            <div class="filter-group search-group">

                <label for="productSearch">
                    SEARCH
                </label>

                <input
                    type="search"
                    id="productSearch"
                    placeholder="Search product name or category..."
                    autocomplete="off"
                >

            </div>


            <div class="filter-group">

                <label for="productStatusFilter">
                    STATUS
                </label>

                <select id="productStatusFilter">

                    <option value="ALL">
                        ALL
                    </option>

                    <option value="ACTIVE">
                        ACTIVE
                    </option>

                    <option value="INACTIVE">
                        INACTIVE
                    </option>

                    <option value="AVAILABLE">
                        AVAILABLE
                    </option>

                    <option value="SOLD_OUT">
                        SOLD OUT
                    </option>

                </select>

            </div>


            <div class="filter-group">

                <label for="productCategoryFilter">
                    CATEGORY
                </label>

                <select id="productCategoryFilter">

                    <option value="ALL">
                        ALL CATEGORIES
                    </option>

                </select>

            </div>

        </section>


        <!-- =================================================
             PRODUCT TABLE
             ================================================= -->

        <section class="products-card">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        PRODUCT CATALOG
                    </div>

                    <h2>
                        Products
                    </h2>

                    <p>
                        Products available in the
                        KohiCebu ordering system.
                    </p>

                </div>


                <div
                    class="product-count"
                    id="productCount"
                >
                    0 products
                </div>

            </div>


            <!-- =============================================
                 LOADING
                 ============================================= -->

            <div
                class="product-state"
                id="productsLoading"
                hidden
            >

                <div class="state-spinner"></div>

                <p>
                    Loading products...
                </p>

            </div>


            <!-- =============================================
                 ERROR
                 ============================================= -->

            <div
                class="product-state error-state"
                id="productsError"
                hidden
            >

                <div class="state-icon">
                    !
                </div>

                <p id="productsErrorMessage">
                    Unable to load products.
                </p>

                <button
                    type="button"
                    class="secondary-button"
                    id="retryProductsButton"
                >
                    TRY AGAIN
                </button>

            </div>


            <!-- =============================================
                 EMPTY
                 ============================================= -->

            <div
                class="product-state"
                id="productsEmpty"
                hidden
            >

                <div class="state-icon">
                    +
                </div>

                <h3>
                    No products found
                </h3>

                <p>
                    Add a product or change your filters.
                </p>

            </div>


            <!-- =============================================
                 TABLE
                 ============================================= -->

            <div
                class="products-table-wrapper"
                id="productsTableWrapper"
                hidden
            >

                <table class="products-table">

                    <thead>

                        <tr>

                            <th>
                                PRODUCT
                            </th>

                            <th>
                                CATEGORY
                            </th>

                            <th>
                                PRICE
                            </th>

                            <th>
                                AVAILABILITY
                            </th>

                            <th>
                                RECIPE
                            </th>

                            <th>
                                SELLABLE
                            </th>

                            <th>
                                ACTION
                            </th>

                        </tr>

                    </thead>


                    <tbody id="productsTableBody"></tbody>

                </table>
            </div>

            <nav class="admin-pagination" id="productsPagination" aria-label="Product pages" hidden></nav>

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
                    Product Configuration
                </strong>

                <p>
                    Product pricing, availability, and recipe
                    configuration affect the customer ordering
                    system. Review changes carefully before saving.
                </p>

            </div>

        </section>


        <!-- =================================================
             ADD PRODUCT MODAL
             ================================================= -->

        <div
            class="modal-overlay"
            id="addProductModal"
            hidden
        >

            <div
                class="modal-card product-modal-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="addProductTitle"
            >

                <div class="modal-header">

                    <div>

                        <div class="modal-eyebrow">
                            PRODUCT MANAGEMENT
                        </div>

                        <h2 id="addProductTitle">
                            Add Product
                        </h2>

                        <p>
                            Create a new product for the
                            KohiCebu catalog.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="modal-close"
                        id="closeAddProductModal"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>


                <div class="modal-body">


                    <!-- =====================================
                         PRODUCT NAME
                         ===================================== -->

                    <div class="form-group">

                        <label for="productName">
                            Product Name
                        </label>

                        <input
                            type="text"
                            id="productName"
                            maxlength="150"
                            placeholder="e.g. Caramel Cloud"
                            autocomplete="off"
                        >

                    </div>


                    <!-- =====================================
                         DESCRIPTION
                         ===================================== -->

                    <div class="form-group">

                        <label for="productDescription">
                            Description
                        </label>

                        <textarea
                            id="productDescription"
                            rows="3"
                            maxlength="500"
                            placeholder="Describe the product..."
                        ></textarea>

                    </div>


                    <!-- =====================================
                         CATEGORY + PRICE
                         ===================================== -->

                    <div class="form-row">

                        <div class="form-group">

                            <label for="productCategory">
                                Category
                            </label>

                            <input
                                type="text"
                                id="productCategory"
                                maxlength="100"
                                placeholder="e.g. Coffee"
                                autocomplete="off"
                            >

                        </div>


                        <div class="form-group">

                            <label for="productPrice">
                                Price
                            </label>

                            <div class="price-input-wrapper">

                                <span class="price-prefix">
                                    ₱
                                </span>

                                <input
                                    type="number"
                                    id="productPrice"
                                    min="0.01"
                                    step="0.01"
                                    inputmode="decimal"
                                    placeholder="0.00"
                                >

                            </div>

                        </div>

                    </div>


                    <!-- =====================================
                         INITIAL AVAILABILITY
                         ===================================== -->

                    <div class="form-group">

                        <label for="productAvailability">
                            Availability
                        </label>

                        <select id="productAvailability">

                            <option value="true">
                                ACTIVE
                            </option>

                            <option value="false">
                                INACTIVE
                            </option>

                        </select>

                        <p class="field-help">
                            Inactive products will not be available
                            for customer ordering.
                        </p>

                    </div>


                    <!-- =====================================
                         INITIAL STOCK
                         ===================================== -->

                    <div class="form-group">

                        <label for="productStock">
                            Initial Stock Quantity
                        </label>

                        <input
                            type="number"
                            id="productStock"
                            min="0"
                            step="1"
                            inputmode="numeric"
                            value="0"
                            placeholder="0"
                        >

                        <p class="field-help">
                            For recipe-based products, ingredient
                            inventory will determine actual sellability.
                        </p>

                    </div>


                    <!-- =====================================
                         PRODUCT IMAGE
                         ===================================== -->

                    <div class="form-group">

                        <label for="productImage">
                            Product Image
                        </label>

                        <input
                            type="file"
                            id="productImage"
                            accept="image/jpeg,image/png,image/webp"
                        >

                        <p class="field-help">
                            JPG, PNG, or WEBP. Maximum file size: 5 MB.
                        </p>

                    </div>


                    <!-- =====================================
                         IMAGE PREVIEW
                         ===================================== -->

                    <div
                        class="image-preview-container"
                        id="imagePreviewContainer"
                        hidden
                    >

                        <div class="image-preview-label">
                            IMAGE PREVIEW
                        </div>

                        <div class="image-preview">

                            <img
                                id="productImagePreview"
                                src=""
                                alt="Product image preview"
                            >

                        </div>

                    </div>


                    <!-- =====================================
                         MODAL MESSAGE
                         ===================================== -->

                    <div
                        class="modal-message"
                        id="addProductMessage"
                        hidden
                    ></div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="secondary-button"
                        id="cancelAddProduct"
                    >
                        CANCEL
                    </button>

                    <button
                        type="button"
                        class="primary-button"
                        id="saveProductButton"
                    >
                        CREATE PRODUCT
                    </button>

                </div>

            </div>

        </div>


        <!-- =================================================
             PRODUCT DETAILS MODAL
             ================================================= -->

        <div
            class="modal-overlay"
            id="productDetailsModal"
            hidden
        >

            <div
                class="modal-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="productDetailsTitle"
            >

                <div class="modal-header">

                    <div>

                        <div class="modal-eyebrow">
                            PRODUCT
                        </div>

                        <h2 id="productDetailsTitle">
                            Product Details
                        </h2>

                        <p id="productDetailsSubtitle">
                            Product information
                        </p>

                    </div>


                    <button
                        type="button"
                        class="modal-close"
                        id="closeProductDetailsModal"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>


                <div
                    class="modal-body"
                    id="productDetailsBody"
                ></div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="secondary-button"
                        id="closeProductDetails"
                    >
                        CLOSE
                    </button>

                </div>

            </div>

        </div>


        <!-- =================================================
             RECIPE CONFIGURATION MODAL
             ================================================= -->

        <div
            class="modal-overlay"
            id="recipeModal"
            hidden
        >

            <div
                class="modal-card recipe-modal-card"
                role="dialog"
                aria-modal="true"
                aria-labelledby="recipeModalTitle"
            >

                <div class="modal-header">

                    <div>

                        <div class="modal-eyebrow">
                            RECIPE CONFIGURATION
                        </div>

                        <h2 id="recipeModalTitle">
                            Product Recipe
                        </h2>

                        <p id="recipeModalSubtitle">
                            Configure ingredients for this product.
                        </p>

                    </div>


                    <button
                        type="button"
                        class="modal-close"
                        id="closeRecipeModal"
                        aria-label="Close"
                    >
                        ×
                    </button>

                </div>


                <div class="modal-body">

                    <!-- =====================================
                         LOADING
                         ===================================== -->

                    <div
                        class="product-state"
                        id="recipeModalLoading"
                        hidden
                    >

                        <div class="state-spinner"></div>

                        <p>
                            Loading recipe...
                        </p>

                    </div>


                    <!-- =====================================
                         ERROR
                         ===================================== -->

                    <div
                        class="product-state error-state"
                        id="recipeModalError"
                        hidden
                    >

                        <div class="state-icon">
                            !
                        </div>

                        <p id="recipeModalErrorMessage">
                            Unable to load recipe.
                        </p>

                        <button
                            type="button"
                            class="secondary-button"
                            id="retryRecipeModalButton"
                        >
                            TRY AGAIN
                        </button>

                    </div>


                    <!-- =====================================
                         EMPTY
                         ===================================== -->

                    <div
                        class="product-state"
                        id="recipeModalEmpty"
                        hidden
                    >

                        <div class="state-icon">
                            +
                        </div>

                        <h3>
                            No recipe configured
                        </h3>

                        <p>
                            This product does not have any
                            ingredients configured yet.
                        </p>

                    </div>


                    <!-- =====================================
                         RECIPE CONTENT
                         ===================================== -->

                    <div
                        id="recipeModalContent"
                        hidden
                    >

                        <div class="recipe-product-summary">

                            <strong id="recipeProductName">
                                Product
                            </strong>

                            <span id="recipeProductStatus">
                                0 ingredients
                            </span>

                        </div>


                        <div class="recipe-list-wrapper">

                            <table class="recipe-table">

                                <thead>

                                    <tr>

                                        <th>
                                            INGREDIENT
                                        </th>

                                        <th>
                                            QUANTITY
                                        </th>

                                        <th>
                                            UNIT
                                        </th>

                                    </tr>

                                </thead>


                                <tbody
                                    id="recipeModalTableBody"
                                ></tbody>

                            </table>

                        </div>


                        <div
                            class="recipe-modal-message"
                            id="recipeModalMessage"
                            hidden
                        ></div>

                    </div>


                    <!-- =====================================
                         ADD INGREDIENT
                         ===================================== -->

                    <div
                        id="addRecipeIngredientSection"
                        hidden
                    >

                        <div class="modal-section-divider"></div>

                        <div class="modal-eyebrow">
                            ADD INGREDIENT
                        </div>

                        <div class="form-row">

                            <div class="form-group">

                                <label for="recipeIngredientSelect">
                                    Ingredient
                                </label>

                                <select
                                    id="recipeIngredientSelect"
                                >

                                    <option value="">
                                        Select an ingredient
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label for="recipeIngredientQuantity">
                                    Quantity Required
                                </label>

                                <input
                                    type="number"
                                    id="recipeIngredientQuantity"
                                    min="0.001"
                                    step="0.001"
                                    placeholder="0.000"
                                    inputmode="decimal"
                                >

                            </div>

                        </div>


                        <p
                            class="field-help"
                            id="recipeIngredientUnit"
                        >
                            Select an ingredient to see its unit.
                        </p>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="secondary-button"
                        id="cancelRecipeModal"
                    >
                        CLOSE
                    </button>


                    <button
                        type="button"
                        class="secondary-button"
                        id="addRecipeIngredientButton"
                    >
                        + ADD INGREDIENT
                    </button>


                    <button
                        type="button"
                        class="primary-button"
                        id="saveRecipeIngredientButton"
                        hidden
                    >
                        SAVE INGREDIENT
                    </button>

                </div>

            </div>

        </div>


        <!-- =================================================
     DELETE PRODUCT CONFIRMATION MODAL
     ================================================= -->

<div
    class="modal-overlay"
    id="deleteProductModal"
    hidden
>

    <div
        class="modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteProductTitle"
    >

        <div class="modal-header">

            <div>

                <div class="modal-eyebrow">
                    PRODUCT MANAGEMENT
                </div>

                <h2 id="deleteProductTitle">
                    Delete Product
                </h2>

                <p>
                    Confirm this product deletion.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                id="closeDeleteProductModal"
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <div class="modal-body">

            <div class="delete-confirmation-content">

                <p>
                    Are you sure you want to delete
                    <strong id="deleteProductName">
                        this product
                    </strong>?
                </p>

                <p>
                    This action permanently removes the product
                    if it has never been used in an order.
                </p>

                <p>
                    Products already used in orders cannot be
                    deleted because their historical order records
                    must be preserved.
                </p>

            </div>

        </div>


        <div class="modal-footer">

            <button
                type="button"
                class="secondary-button"
                id="cancelDeleteProduct"
            >
                CANCEL
            </button>

            <button
                type="button"
                class="primary-button"
                id="confirmDeleteProduct"
            >
                DELETE PRODUCT
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
                    Admin Portal is ready.
                </p>

            </div>

        </section>

    </main>

</div>


<script src="js/pagination.js"></script>
<script src="js/products.js?v=3"></script>

</body>

</html>
