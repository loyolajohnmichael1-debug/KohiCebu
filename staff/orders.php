<?php
/*
 * KohiCebu Staff Portal
 * Order Queue
 *
 * Authentication:
 *   login.php → ../api/login.php
 *
 * Data:
 *   orders.js → ../api/get_staff_orders.php
 */
?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>KohiCebu — Order Queue</title>

    <link rel="stylesheet" href="css/orders.css">
    <link rel="stylesheet" href="css/polish.css">
    <link rel="stylesheet" href="css/operations.css">

</head>


<body>

<div class="app">


    <!-- =====================================
         SHARED SIDEBAR
    ====================================== -->

    <?php include "includes/sidebar.php"; ?>



    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main class="main">


        <!-- =====================================
             PAGE HEADER
        ====================================== -->

        <header class="page-header">


            <div>

                <h1 class="page-title">
                    Order Queue
                </h1>

                <p class="page-subtitle">
                    Manage incoming orders and monitor their progress.
                </p>

            </div>


            <div class="header-actions">

                <button
                    type="button"
                    class="refresh-button"
                    id="refreshOrders"
                >
                    Refresh
                </button>

            </div>


        </header>



        <!-- =====================================
             FILTERS
        ====================================== -->

        <section class="filters-section">


            <div class="filter-tabs">


                <button
                    type="button"
                    class="filter-tab active"
                    data-filter="ALL"
                >
                    All
                </button>


                <button
                    type="button"
                    class="filter-tab"
                    data-filter="AWAITING_PAYMENT"
                >
                    Awaiting Payment
                </button>


                <button
                    type="button"
                    class="filter-tab"
                    data-filter="PAID"
                >
                    Paid
                </button>


                <button
                    type="button"
                    class="filter-tab"
                    data-filter="PREPARING"
                >
                    Preparing
                </button>


                <button
                    type="button"
                    class="filter-tab"
                    data-filter="READY"
                >
                    Ready
                </button>


                <button
                    type="button"
                    class="filter-tab"
                    data-filter="COMPLETED"
                >
                    Completed
                </button>


            </div>


        </section>



        <!-- =====================================
             SUMMARY
        ====================================== -->

        <section class="summary-grid">


            <!-- ACTIVE ORDERS -->

            <div class="summary-card">

                <div class="summary-label">
                    Active Orders
                </div>

                <div
                    class="summary-value"
                    id="activeOrdersCount"
                >
                    —
                </div>

            </div>



            <!-- AWAITING PAYMENT -->

            <div class="summary-card">

                <div class="summary-label">
                    Awaiting Payment
                </div>

                <div
                    class="summary-value"
                    id="awaitingPaymentCount"
                >
                    —
                </div>

            </div>



            <!-- PREPARING -->

            <div class="summary-card">

                <div class="summary-label">
                    Preparing
                </div>

                <div
                    class="summary-value"
                    id="preparingCount"
                >
                    —
                </div>

            </div>



            <!-- READY -->

            <div class="summary-card">

                <div class="summary-label">
                    Ready
                </div>

                <div
                    class="summary-value"
                    id="readyCount"
                >
                    —
                </div>

            </div>


        </section>



        <!-- =====================================
             ORDERS
        ====================================== -->

        <section class="orders-section">


            <div class="section-header">

                <div>

                    <h2 class="section-title">
                        Orders
                    </h2>

                    <p class="section-subtitle">
                        Live orders from the KohiCebu ordering system.
                    </p>

                </div>


                <div
                    class="order-count"
                    id="orderCount"
                >
                    0 orders
                </div>

            </div>



            <!-- ORDER GRID -->

            <div
                class="orders-grid"
                id="ordersGrid"
            >

                <div class="orders-empty">

                    <div class="empty-title">
                        Loading orders...
                    </div>

                    <div class="empty-description">
                        Please wait while the order queue is loaded.
                    </div>

                </div>

            </div>


        </section>



        <!-- =====================================
             SYSTEM STATUS
        ====================================== -->

        <div class="system-banner">

            <span class="system-dot"></span>

            <span id="systemStatus">
                KohiCebu Staff Portal is connected.
            </span>

        </div>


    </main>

</div>



<script src="js/orders.js"></script>

</body>

</html>
