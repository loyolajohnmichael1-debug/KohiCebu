<?php

/*
 * =========================================================
 * KOHI CEBU — CUSTOMER CHECKOUT
 * =========================================================
 */


/*
 * ---------------------------------------------------------
 * CUSTOMER AUTHENTICATION
 * ---------------------------------------------------------
 */

require_once __DIR__ . '/includes/auth.php';


/*
 * ---------------------------------------------------------
 * REQUIRE LOGIN
 * ---------------------------------------------------------
 */

if (!isCustomerLoggedIn()) {

    header(
        "Location: login.php?redirect=" .
        urlencode("checkout.php")
    );

    exit;
}

if (getCustomerRole() !== 'CUSTOMER') {
    header('Location: menu.php');
    exit;
}


/*
 * ---------------------------------------------------------
 * CUSTOMER SESSION DATA
 * ---------------------------------------------------------
 *
 * The authenticated customer is used as the
 * source of checkout identity.
 *
 * No customer information is manually entered
 * on this page.
 * ---------------------------------------------------------
 */

$customer = getCurrentCustomer() ?? [];


/*
 * ---------------------------------------------------------
 * CUSTOMER INFORMATION
 * ---------------------------------------------------------
 */

$customerId = $customer["user_id"] ?? "";


$customerName = $customer["full_name"] ?? "";


$customerEmail = $customer["email"] ?? "";


$customerPhone = $customer["phone"] ?? "";


/*
 * ---------------------------------------------------------
 * SAFE DISPLAY VALUES
 * ---------------------------------------------------------
 */

$displayCustomerName =
    trim((string) $customerName);


$displayCustomerEmail =
    trim((string) $customerEmail);


$displayCustomerPhone =
    trim((string) $customerPhone);


/*
 * ---------------------------------------------------------
 * PAGE TITLE
 * ---------------------------------------------------------
 */

$pageTitle =
    'Checkout — KohiCebu.';


/*
 * ---------------------------------------------------------
 * HEADER
 * ---------------------------------------------------------
 */

require_once
    __DIR__ .
    '/includes/header.php';

?>


<link
    rel="stylesheet"
    href="css/checkout.css"
>


<main class="checkout-page">


    <!-- =====================================================
         CHECKOUT HERO
         ===================================================== -->

    <section class="checkout-hero section">

        <div class="container">

            <p class="eyebrow">
                KOHI CEBU ORDER
            </p>

            <h1>
                Almost there.
            </h1>

            <p class="checkout-hero__intro">
                Review your order, confirm your account,
                and choose how you'd like to pay.
            </p>

        </div>

    </section>


    <!-- =====================================================
         CHECKOUT CONTENT
         ===================================================== -->

    <section class="checkout-section section">

        <div class="container">


            <!-- =================================================
                 LOADING
                 ================================================= -->

            <div
                class="checkout-loading"
                id="checkoutLoading"
            >

                <div
                    class="checkout-loading__line
                           checkout-loading__line--large"
                ></div>

                <div
                    class="checkout-loading__line"
                ></div>

                <div
                    class="checkout-loading__line"
                ></div>

            </div>


            <!-- =================================================
                 EMPTY ORDER
                 ================================================= -->

            <div
                class="checkout-empty"
                id="checkoutEmpty"
                hidden
            >

                <p class="eyebrow">
                    NO ORDER FOUND
                </p>

                <h2>
                    Your order is empty.
                </h2>

                <p>
                    Add something from the menu before
                    continuing to checkout.
                </p>

                <a
                    href="menu.php"
                    class="button button--dark"
                >
                    Explore Menu
                </a>

            </div>


            <!-- =================================================
                 CHECKOUT LAYOUT
                 ================================================= -->

            <div
                class="checkout-layout"
                id="checkoutLayout"
                hidden

                data-customer-id="<?=

                    htmlspecialchars(
                        (string) $customerId,
                        ENT_QUOTES,
                        'UTF-8'
                    )

                ?>"

                data-customer-name="<?=

                    htmlspecialchars(
                        $displayCustomerName,
                        ENT_QUOTES,
                        'UTF-8'
                    )

                ?>"

                data-customer-email="<?=

                    htmlspecialchars(
                        $displayCustomerEmail,
                        ENT_QUOTES,
                        'UTF-8'
                    )

                ?>"

                data-customer-phone="<?=

                    htmlspecialchars(
                        $displayCustomerPhone,
                        ENT_QUOTES,
                        'UTF-8'
                    )

                ?>"
            >


                <!-- =============================================
                     CHECKOUT MAIN
                     ============================================= -->

                <div class="checkout-main">


                    <!-- =========================================
                         01 — YOUR ACCOUNT
                         ========================================= -->

                    <div class="checkout-block">


                        <div class="checkout-block__heading">

                            <p class="eyebrow">
                                01
                            </p>

                            <div>

                                <h2>
                                    Your Account
                                </h2>

                                <p>
                                    Your order will be placed
                                    under your signed-in account.
                                </p>

                            </div>

                        </div>


                        <!-- =====================================
                             ACCOUNT CARD
                             ===================================== -->

                        <div
                            class="checkout-account"
                            id="checkoutAccount"
                        >

                            <div
                                class="checkout-account__avatar"
                                aria-hidden="true"
                            >

                                <span>

                                    <?= htmlspecialchars(

                                        strtoupper(

                                            substr(

                                                $displayCustomerName !== ''
                                                    ? $displayCustomerName
                                                    : 'K',

                                                0,
                                                1

                                            )

                                        ),

                                        ENT_QUOTES,
                                        'UTF-8'

                                    ) ?>

                                </span>

                            </div>


                            <div
                                class="checkout-account__content"
                            >

                                <p
                                    class="checkout-account__label"
                                >
                                    ORDERING AS
                                </p>


                                <h3
                                    class="checkout-account__name"
                                >

                                    <?= htmlspecialchars(

                                        $displayCustomerName !== ''
                                            ? $displayCustomerName
                                            : 'KohiCebu Customer',

                                        ENT_QUOTES,
                                        'UTF-8'

                                    ) ?>

                                </h3>


                                <div
                                    class="checkout-account__details"
                                >

                                    <?php if (
                                        $displayCustomerEmail !== ''
                                    ): ?>

                                        <span>

                                            <?= htmlspecialchars(

                                                $displayCustomerEmail,

                                                ENT_QUOTES,
                                                'UTF-8'

                                            ) ?>

                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        $displayCustomerPhone !== ''
                                    ): ?>

                                        <span>

                                            <?= htmlspecialchars(

                                                $displayCustomerPhone,

                                                ENT_QUOTES,
                                                'UTF-8'

                                            ) ?>

                                        </span>

                                    <?php endif; ?>

                                </div>


                                <div
                                    class="checkout-account__status"
                                >

                                    <span
                                        class="checkout-account__status-dot"
                                    ></span>

                                    <span>
                                        Signed in
                                    </span>

                                </div>

                            </div>


                            <a
                                href="account.php"
                                class="checkout-account__link"
                            >
                                Manage Account
                            </a>

                        </div>

                    </div>


                    <!-- =========================================
                         02 — ORDER TYPE
                         ========================================= -->

                    <div class="checkout-block">

                        <div
                            class="checkout-block__heading"
                        >

                            <p class="eyebrow">
                                02
                            </p>

                            <div>

                                <h2>
                                    Order Type
                                </h2>

                                <p>
                                    Choose how you'll receive
                                    your order.
                                </p>

                            </div>

                        </div>


                        <div class="checkout-options">

                            <button
                                type="button"
                                class="checkout-option is-selected"
                                data-order-type="PICKUP"
                            >

                                <span
                                    class="checkout-option__radio"
                                ></span>

                                <span
                                    class="checkout-option__content"
                                >

                                    <strong>
                                        Pick Up
                                    </strong>

                                    <small>
                                        Collect your order at
                                        KohiCebu.
                                    </small>

                                </span>

                            </button>

                        </div>

                    </div>


                    <!-- =========================================
                         03 — PAYMENT METHOD
                         ========================================= -->

                    <div class="checkout-block">

                        <div
                            class="checkout-block__heading"
                        >

                            <p class="eyebrow">
                                03
                            </p>

                            <div>

                                <h2>
                                    Payment
                                </h2>

                                <p>
                                    Choose your preferred
                                    payment method.
                                </p>

                            </div>

                        </div>


                        <div class="checkout-options">


                            <!-- CASHIER -->

                            <button
                                type="button"
                                class="checkout-option is-selected"
                                data-payment-method="CASHIER"
                            >

                                <span
                                    class="checkout-option__radio"
                                ></span>

                                <span
                                    class="checkout-option__content"
                                >

                                    <strong>
                                        Pay at Cashier
                                    </strong>

                                    <small>
                                        Pay when you pick up
                                        your order.
                                    </small>

                                </span>

                            </button>


                            <!-- ONLINE -->

                            <button
                                type="button"
                                class="checkout-option"
                                data-payment-method="ONLINE"
                            >

                                <span
                                    class="checkout-option__radio"
                                ></span>

                                <span
                                    class="checkout-option__content"
                                >

                                    <strong>
                                        Pay Online
                                    </strong>

                                    <small>
                                        Pay securely online
                                        before pickup.
                                    </small>

                                </span>

                            </button>

                        </div>

                    </div>


                </div>

                <!-- =============================================
                     ORDER SUMMARY
                     ============================================= -->

                <aside class="checkout-summary">


                    <div
                        class="checkout-summary__header"
                    >

                        <p class="eyebrow">
                            YOUR ORDER
                        </p>

                        <h2>
                            Order Summary
                        </h2>

                    </div>


                    <!-- =========================================
                         ITEMS
                         ========================================= -->

                    <div
                        class="checkout-summary__items"
                        id="checkoutSummaryItems"
                    >
                    </div>


                    <!-- =========================================
                         TOTALS
                         ========================================= -->

                    <div
                        class="checkout-summary__totals"
                    >


                        <div
                            class="checkout-summary__row"
                        >

                            <span>
                                Items
                            </span>

                            <span
                                id="checkoutItemCount"
                            >
                                0
                            </span>

                        </div>


                        <div
                            class="checkout-summary__row"
                        >

                            <span>
                                Subtotal
                            </span>

                            <span
                                id="checkoutSubtotal"
                            >
                                ₱0.00
                            </span>

                        </div>


                        <div
                            class="
                                checkout-summary__row
                                checkout-summary__row--total
                            "
                        >

                            <span>
                                Total
                            </span>

                            <strong
                                id="checkoutTotal"
                            >
                                ₱0.00
                            </strong>

                        </div>


                    </div>


                    <!-- =========================================
                         PLACE ORDER
                         ========================================= -->

                    <button
                        type="button"
                        class="
                            button
                            button--dark
                            checkout-summary__submit
                        "
                        id="placeOrderButton"
                    >
                        Place Order
                    </button>


                    <!-- =========================================
                         ADD MORE ITEMS
                         ========================================= -->

                    <a
                        href="menu.php"
                        class="checkout-summary__back"
                        id="addMoreItemsButton"
                    >
                        ＋ Add More Items
                    </a>


                    <p class="checkout-summary__note">

                        By placing your order, you confirm
                        that the information provided is correct.

                    </p>


                </aside>


            </div>


            <!-- =================================================
                 ERROR
                 ================================================= -->

            <div
                class="checkout-error"
                id="checkoutError"
                hidden
            >

                <h2>
                    Something went wrong.
                </h2>

                <p>
                    We couldn't load your order.
                    Please try again.
                </p>

                <button
                    type="button"
                    class="button button--dark"
                    id="retryCheckout"
                >
                    Try Again
                </button>

            </div>


        </div>

    </section>


</main>


<script src="js/checkout.js"></script>


<?php

require_once
    __DIR__ .
    '/includes/footer.php';

?>
