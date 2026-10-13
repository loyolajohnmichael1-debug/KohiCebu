<?php

/*
 * =========================================================
 * KOHI CEBU — MY ORDERS
 * =========================================================
 */

require_once __DIR__ . '/includes/auth.php';


/*
 * ---------------------------------------------------------
 * REQUIRE CUSTOMER LOGIN
 * ---------------------------------------------------------
 */

requireCustomerLogin();
if (getCustomerRole() !== 'CUSTOMER') {
    header('Location: menu.php');
    exit;
}


/*
 * ---------------------------------------------------------
 * PAGE TITLE
 * ---------------------------------------------------------
 */

$pageTitle = 'My Orders — KohiCebu.';

require_once __DIR__ . '/includes/header.php';

?>

<link
    rel="stylesheet"
    href="css/orders.css"
>


<main class="orders-page">

    <!-- =====================================================
         ORDERS HERO
         ===================================================== -->

    <section class="orders-hero">

        <div class="container">

            <p class="eyebrow">
                ORDER HISTORY
            </p>

            <h1>
                My Orders
            </h1>

            <p class="orders-hero__text">
                View your recent KohiCebu orders
                and keep track of their status.
            </p>

        </div>

    </section>


    <!-- =====================================================
         ORDERS SECTION
         ===================================================== -->

    <section class="orders-section">

        <div class="container">

            <!-- Loading -->

            <div
                id="ordersLoading"
                class="orders-state"
            >

                <div class="orders-loading">

                    <div class="orders-skeleton"></div>
                    <div class="orders-skeleton"></div>
                    <div class="orders-skeleton"></div>

                </div>

            </div>


            <!-- Error -->

            <div
                id="ordersError"
                class="orders-state orders-state--error"
                hidden
            >

                <div class="orders-state__icon">
                    !
                </div>

                <h2>
                    Unable to load your orders.
                </h2>

                <p>
                    Something went wrong while retrieving
                    your order history.
                </p>

                <button
                    type="button"
                    id="retryOrders"
                    class="button button--dark"
                >
                    TRY AGAIN
                </button>

            </div>


            <!-- Empty -->

            <div
                id="ordersEmpty"
                class="orders-state"
                hidden
            >

                <div class="orders-state__icon">
                    —
                </div>

                <p class="eyebrow">
                    NO ORDERS YET
                </p>

                <h2>
                    Your order history is empty.
                </h2>

                <p>
                    Ready for your first KohiCebu order?
                </p>

                <a
                    href="menu.php"
                    class="button button--dark"
                >
                    EXPLORE MENU
                </a>

            </div>


            <!-- Orders -->

            <div
                id="ordersList"
                class="orders-list"
                hidden
            ></div>

        </div>

    </section>

</main>


<script src="js/orders.js"></script>


<?php

require_once __DIR__ . '/includes/footer.php';

?>
