<?php

$pageTitle = 'Cart — KohiCebu.';

require_once __DIR__ . '/includes/header.php';

?>

<link rel="stylesheet" href="css/cart.css">

<main class="cart-page">

    <!-- =====================================================
         CART HERO
         ===================================================== -->

    <section class="cart-hero section">
        <div class="container">

            <p class="eyebrow">YOUR KOHI ORDER</p>

            <h1>
                Your cart.
            </h1>

            <p class="cart-hero__intro">
                Review your selections before you continue
                to checkout.
            </p>

        </div>
    </section>


    <!-- =====================================================
         CART CONTENT
         ===================================================== -->

    <section class="cart-section section">
        <div class="container">

            <!-- Loading -->
            <div
                class="cart-loading"
                id="cartLoading"
            >
                <div class="cart-loading__line cart-loading__line--large"></div>
                <div class="cart-loading__line"></div>
                <div class="cart-loading__line"></div>
            </div>


            <!-- Empty Cart -->
            <div
                class="cart-empty"
                id="cartEmpty"
                hidden
            >

                <div class="cart-empty__icon">
                    00
                </div>

                <p class="eyebrow">
                    NOTHING HERE YET
                </p>

                <h2>
                    Your cart is empty.
                </h2>

                <p>
                    Explore the Kohi menu and find something
                    you would like to enjoy.
                </p>

                <a
                    href="menu.php"
                    class="button button--dark"
                >
                    Explore Menu
                </a>

            </div>


            <!-- Cart -->
            <div
                class="cart-layout"
                id="cartLayout"
                hidden
            >

                <!-- =================================================
                     CART ITEMS
                     ================================================= -->

                <div class="cart-items">

                    <div class="cart-items__header">

                        <div>
                            <p class="eyebrow">
                                YOUR SELECTION
                            </p>

                            <h2>
                                Cart Items
                            </h2>
                        </div>

                        <a
                            href="menu.php"
                            class="text-link"
                        >
                            Continue Shopping →
                        </a>

                    </div>


                    <div
                        class="cart-items__list"
                        id="cartItemsList"
                    >
                    </div>

                </div>


                <!-- =================================================
                     CART SUMMARY
                     ================================================= -->

                <aside class="cart-summary">

                    <div class="cart-summary__header">

                        <p class="eyebrow">
                            ORDER SUMMARY
                        </p>

                        <h2>
                            Your order
                        </h2>

                    </div>


                    <div class="cart-summary__rows">

                        <div class="cart-summary__row">

                            <span>
                                Items
                            </span>

                            <span id="cartItemCount">
                                0
                            </span>

                        </div>


                        <div class="cart-summary__row">

                            <span>
                                Subtotal
                            </span>

                            <span id="cartSubtotal">
                                ₱0.00
                            </span>

                        </div>


                        <div class="cart-summary__row cart-summary__row--total">

                            <span>
                                Total
                            </span>

                            <strong id="cartTotal">
                                ₱0.00
                            </strong>

                        </div>

                    </div>


                    <button
                        type="button"
                        class="button button--dark cart-summary__checkout"
                        id="checkoutButton"
                    >
                        Proceed to Checkout
                    </button>


                    <p class="cart-summary__note">
                        Final order details and payment options
                        will be confirmed at checkout.
                    </p>

                </aside>

            </div>


            <!-- Error -->
            <div
                class="cart-error"
                id="cartError"
                hidden
            >

                <h2>
                    We couldn't load your cart.
                </h2>

                <p>
                    Please refresh the page and try again.
                </p>

                <button
                    type="button"
                    class="button button--dark"
                    id="retryCart"
                >
                    Try Again
                </button>

            </div>

        </div>
    </section>


    <!-- =====================================================
         CART CTA
         ===================================================== -->

    <section class="cart-cta section">

        <div class="container">

            <div class="cart-cta__inner">

                <p class="eyebrow">
                    KEEP EXPLORING
                </p>

                <h2>
                    Good drinks.<br>
                    Good choices.
                </h2>

                <a
                    href="menu.php"
                    class="button button--light"
                >
                    View Menu
                </a>

            </div>

        </div>

    </section>

</main>


<script src="js/cart.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>