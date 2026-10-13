<?php

$productId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

$pageTitle = 'Product — KohiCebu.';

require_once __DIR__ . '/includes/header.php';

?>

<!-- Product Page Styles -->
<link rel="stylesheet" href="css/product.css">

<main class="product-page">

    <!-- =========================================
         PRODUCT DETAILS
         ========================================= -->

    <section class="product-section section">

        <div class="container">

            <!-- Loading State -->

            <div
                class="product-loading"
                id="productLoading"
            >

                <div class="product-loading__image"></div>

                <div class="product-loading__content">

                    <div class="product-loading__line"></div>
                    <div class="product-loading__line product-loading__line--large"></div>
                    <div class="product-loading__line"></div>

                </div>

            </div>


            <!-- Error State -->

            <div
                class="product-message"
                id="productError"
                hidden
            >

                <p>
                    We couldn't load this product right now.
                </p>

                <a
                    href="menu.php"
                    class="text-link"
                >
                    Back to Menu →
                </a>

            </div>


            <!-- Product Not Found -->

            <div
                class="product-message"
                id="productNotFound"
                hidden
            >

                <h2>
                    Product not found.
                </h2>

                <p>
                    This product may no longer be available.
                </p>

                <a
                    href="menu.php"
                    class="text-link"
                >
                    Back to Menu →
                </a>

            </div>


            <!-- Product Content -->

            <div
                class="product-detail"
                id="productDetail"
                hidden
            >

                <!-- Product Image -->

                <div class="product-detail__visual">

                    <div
                        class="product-detail__image"
                        id="productImage"
                    ></div>

                </div>


                <!-- Product Information -->

                <div class="product-detail__content">

                    <a
                        href="menu.php"
                        class="product-detail__back"
                    >
                        ← Back to Menu
                    </a>


                    <span
                        class="product-detail__category"
                        id="productCategory"
                    ></span>


                    <h1
                        class="product-detail__name"
                        id="productName"
                    ></h1>


                    <div
                        class="product-detail__price"
                        id="productPrice"
                    ></div>


                    <p
                        class="product-detail__description"
                        id="productDescription"
                    ></p>


                    <!-- Availability -->

                    <div
                        class="product-detail__availability"
                        id="productAvailability"
                    ></div>


                    <!-- Quantity -->

                    <div
                        class="product-detail__quantity"
                        id="productQuantityWrapper"
                    >

                        <span>
                            Quantity
                        </span>

                        <div class="quantity-control">

                            <button
                                type="button"
                                id="quantityDecrease"
                                aria-label="Decrease quantity"
                            >
                                −
                            </button>

                            <span
                                id="quantityValue"
                            >
                                1
                            </span>

                            <button
                                type="button"
                                id="quantityIncrease"
                                aria-label="Increase quantity"
                            >
                                +
                            </button>

                        </div>

                    </div>


                    <!-- Order Now -->

                    <button
                        type="button"
                        class="button button--dark product-detail__add"
                        id="addToCartButton"
                    >
                        Order Now
                    </button>


                    <!-- Unavailable -->

                    <div
                        class="product-detail__unavailable"
                        id="productUnavailable"
                        hidden
                    >
                        This product is currently unavailable.
                    </div>

                </div>

            </div>


            <!-- =========================================
                 ORDER DRAWER
                 ========================================= -->

            <div
                class="order-drawer"
                id="orderDrawer"
                aria-hidden="true"
            >

                <!-- Drawer Backdrop -->

                <div
                    class="order-drawer__backdrop"
                    id="orderDrawerBackdrop"
                ></div>


                <!-- Drawer Panel -->

                <aside
                    class="order-drawer__panel"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="orderDrawerTitle"
                >

                    <!-- Drawer Header -->

                    <div class="order-drawer__header">

                        <div>

                            <p class="eyebrow">
                                KOHI CEBU
                            </p>

                            <h2 id="orderDrawerTitle">
                                Your Order
                            </h2>

                        </div>


                        <button
                            type="button"
                            class="order-drawer__close"
                            id="closeOrderDrawer"
                            aria-label="Close order panel"
                        >
                            ×
                        </button>

                    </div>


                    <!-- Drawer Body -->

                    <div
                        class="order-drawer__body"
                        id="orderDrawerBody"
                    >

                        <!-- Selected Product -->

                        <div class="order-drawer__item">

                            <div
                                class="order-drawer__item-image"
                                id="drawerProductImage"
                            ></div>


                            <div class="order-drawer__item-info">

                                <p
                                    class="order-drawer__item-category"
                                    id="drawerProductCategory"
                                ></p>

                                <h3
                                    id="drawerProductName"
                                ></h3>

                                <p
                                    class="order-drawer__item-price"
                                    id="drawerProductPrice"
                                ></p>

                            </div>

                        </div>


                        <!-- Drawer Quantity -->

                        <div class="order-drawer__quantity">

                            <span>
                                Quantity
                            </span>


                            <div class="order-drawer__quantity-control">

                                <button
                                    type="button"
                                    id="drawerQuantityDecrease"
                                    aria-label="Decrease quantity"
                                >
                                    −
                                </button>

                                <span
                                    id="drawerQuantityValue"
                                >
                                    1
                                </span>

                                <button
                                    type="button"
                                    id="drawerQuantityIncrease"
                                    aria-label="Increase quantity"
                                >
                                    +
                                </button>

                            </div>

                        </div>


                        <!-- Drawer Summary -->

                        <div class="order-drawer__summary">

                            <div class="order-drawer__summary-row">

                                <span>
                                    Subtotal
                                </span>

                                <strong
                                    id="drawerSubtotal"
                                >
                                    ₱0.00
                                </strong>

                            </div>

                        </div>

                    </div>


                    <!-- Drawer Footer -->

                    <div class="order-drawer__footer">

                        <button
                            type="button"
                            class="button button--dark order-drawer__checkout"
                            id="drawerCheckoutButton"
                        >
                            Continue to Checkout →
                        </button>


                        <a
                            href="menu.php"
                            class="order-drawer__continue"
                        >
                            Continue Ordering
                        </a>

                    </div>

                </aside>

            </div>

        </div>

    </section>


    <!-- =========================================
         PRODUCT CTA
         ========================================= -->

    <section class="product-cta section">

        <div class="container">

            <div class="product-cta__inner">

                <p class="eyebrow">
                    MORE FROM KOHI CEBU
                </p>

                <h2>
                    Find something<br>
                    else you love.
                </h2>

                <a
                    href="menu.php"
                    class="button button--light"
                >
                    Back to Menu
                </a>

            </div>

        </div>

    </section>

</main>


<!-- Product Page Script -->
<script src="js/product.js"></script>

<?php

require_once __DIR__ . '/includes/footer.php';

?>