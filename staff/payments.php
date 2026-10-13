<?php
/*
 * KohiCebu Staff Portal
 * Payments
 *
 * Authentication:
 *   login.php → ../api/login.php
 *
 * Data:
 *   payments.js → ../api/get_staff_orders.php
 *
 * Cash payment confirmation:
 *   payments.js → ../api/confirm_cash_payment.php
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

    <title>KohiCebu — Payments</title>

    <link
        rel="stylesheet"
        href="css/payments.css"
    >
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

                <div class="page-eyebrow">
                    FINANCIAL OPERATIONS
                </div>

                <h1 class="page-title">
                    Payments
                </h1>

                <p class="page-subtitle">
                    Monitor customer payments and confirm cashier transactions.
                </p>

            </div>


            <div class="header-actions">

                <button
                    type="button"
                    class="refresh-button"
                    id="refreshPayments"
                >
                    Refresh
                </button>

            </div>

        </header>



        <!-- =====================================
             PAYMENT SUMMARY
        ====================================== -->

        <section class="summary-grid">


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

                <div class="summary-description">
                    Cashier payments waiting for confirmation.
                </div>

            </div>



            <!-- PAID -->

            <div class="summary-card">

                <div class="summary-label">
                    Paid
                </div>

                <div
                    class="summary-value"
                    id="paidPaymentCount"
                >
                    —
                </div>

                <div class="summary-description">
                    Orders with confirmed payment.
                </div>

            </div>



            <!-- FAILED -->

            <div class="summary-card">

                <div class="summary-label">
                    Failed
                </div>

                <div
                    class="summary-value"
                    id="failedPaymentCount"
                >
                    —
                </div>

                <div class="summary-description">
                    Payment attempts that failed.
                </div>

            </div>



            <!-- EXPIRED -->

            <div class="summary-card">

                <div class="summary-label">
                    Expired
                </div>

                <div
                    class="summary-value"
                    id="expiredPaymentCount"
                >
                    —
                </div>

                <div class="summary-description">
                    Payment sessions that expired.
                </div>

            </div>


        </section>



        <!-- =====================================
             PAYMENT CONTROLS
        ====================================== -->

        <section class="controls-section">


            <!-- FILTERS -->

            <div class="payment-filters">

                <button
                    type="button"
                    class="payment-filter active"
                    data-filter="ALL"
                >
                    All
                </button>


                <button
                    type="button"
                    class="payment-filter"
                    data-filter="AWAITING_PAYMENT"
                >
                    Awaiting Payment
                </button>


                <button
                    type="button"
                    class="payment-filter"
                    data-filter="PAID"
                >
                    Paid
                </button>


                <button
                    type="button"
                    class="payment-filter"
                    data-filter="FAILED"
                >
                    Failed
                </button>


                <button
                    type="button"
                    class="payment-filter"
                    data-filter="EXPIRED"
                >
                    Expired
                </button>


                <button
                    type="button"
                    class="payment-filter"
                    data-filter="CANCELLED"
                >
                    Cancelled
                </button>

            </div>



            <!-- SEARCH -->

            <div class="payment-search">

                <label
                    for="paymentSearch"
                    class="sr-only"
                >
                    Search payments
                </label>

                <input
                    type="search"
                    id="paymentSearch"
                    class="search-input"
                    placeholder="Search by order number or customer..."
                    autocomplete="off"
                >

            </div>


        </section>



        <!-- =====================================
             PAYMENT TRANSACTIONS
        ====================================== -->

        <section class="payments-section">


            <div class="section-header">

                <div>

                    <h2 class="section-title">
                        Payment Transactions
                    </h2>

                    <p class="section-subtitle">
                        Payment records from the KohiCebu ordering system.
                    </p>

                </div>


                <div
                    class="payment-result-count"
                    id="paymentResultCount"
                >
                    0 transactions
                </div>

            </div>



            <!-- =================================
                 LOADING STATE
            ================================== -->

            <div
                class="payment-state"
                id="paymentLoading"
            >

                <div class="state-title">
                    Loading payments...
                </div>

                <div class="state-description">
                    Please wait while payment transactions are retrieved.
                </div>

            </div>



            <!-- =================================
                 ERROR STATE
            ================================== -->

            <div
                class="payment-state error-state"
                id="paymentError"
                hidden
            >

                <div class="state-title">
                    Unable to load payments
                </div>

                <div
                    class="state-description"
                    id="paymentErrorMessage"
                >
                    Unable to retrieve payment transactions.
                </div>


                <button
                    type="button"
                    class="state-button"
                    id="retryPayments"
                >
                    Try Again
                </button>

            </div>



            <!-- =================================
                 EMPTY STATE
            ================================== -->

            <div
                class="payment-state"
                id="paymentEmpty"
                hidden
            >

                <div class="state-title">
                    No payment transactions found
                </div>

                <div class="state-description">
                    There are no payment records matching the current filter.
                </div>

            </div>



            <!-- =================================
                 PAYMENT LIST
            ================================== -->

            <div
                class="payment-list"
                id="paymentList"
                hidden
            >

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



<script src="js/payments.js"></script>

</body>

</html>
