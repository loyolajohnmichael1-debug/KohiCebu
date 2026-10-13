<?php
/*
 * KohiCebu Staff Portal
 * Staff Dashboard
 *
 * This page is the UI shell.
 *
 * Authentication:
 *   login.php → ../api/login.php
 *
 * Data:
 *   dashboard.js → Staff API
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

    <title>KohiCebu — Staff Dashboard</title>

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >
    <link rel="stylesheet" href="css/polish.css">
    <link rel="stylesheet" href="css/operations.css">

</head>


<body>

<div class="app">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <?php include "includes/sidebar.php"; ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <header class="page-header">

            <div>

                <h1 class="page-title">
                    Staff Dashboard
                </h1>

                <p class="page-subtitle">
                    Monitor orders and manage today's operations.
                </p>

            </div>


            <div
                class="page-date"
                id="currentDate"
            >
                —
            </div>

        </header>


        <!-- =================================================
             SHIFT STATUS
        ================================================== -->

        <section
            class="shift-card"
            id="shiftCard"
        >

            <div class="shift-card-content">

                <div
                    class="shift-status-icon"
                    id="shiftStatusIcon"
                >
                    —
                </div>


                <div class="shift-info">

                    <div
                        class="shift-status-label"
                        id="shiftStatusLabel"
                    >
                        Checking shift status...
                    </div>


                    <div
                        class="shift-cashier-name"
                        id="shiftCashierName"
                    >
                        —
                    </div>


                    <div
                        class="shift-details"
                        id="shiftDetails"
                    >
                        Please wait...
                    </div>

                </div>

            </div>


            <div class="shift-actions">

                <button
                    type="button"
                    class="shift-button"
                    id="startShiftButton"
                    hidden
                >
                    Start Shift
                </button>


                <button
                    type="button"
                    class="shift-button"
                    id="endShiftButton"
                    hidden
                >
                    End Shift
                </button>

            </div>

        </section>


        <!-- =================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <!-- AWAITING PAYMENT -->

            <div class="stat-card">

                <div class="stat-label">
                    Awaiting Payment
                </div>


                <div
                    class="stat-value"
                    id="awaitingPaymentCount"
                >
                    —
                </div>


                <div class="stat-description">
                    Orders waiting for cashier confirmation
                </div>

            </div>


            <!-- PREPARING -->

            <div class="stat-card">

                <div class="stat-label">
                    Preparing
                </div>


                <div
                    class="stat-value"
                    id="preparingCount"
                >
                    —
                </div>


                <div class="stat-description">
                    Orders currently being prepared
                </div>

            </div>


            <!-- READY -->

            <div class="stat-card">

                <div class="stat-label">
                    Ready
                </div>


                <div
                    class="stat-value"
                    id="readyCount"
                >
                    —
                </div>


                <div class="stat-description">
                    Orders ready for pickup
                </div>

            </div>


            <!-- COMPLETED -->

            <div class="stat-card">

                <div class="stat-label">
                    Completed
                </div>


                <div
                    class="stat-value"
                    id="completedCount"
                >
                    —
                </div>


                <div class="stat-description">
                    Completed orders
                </div>

            </div>

        </section>


        <!-- =================================================
             ORDER QUEUE
        ================================================== -->

        <section class="section">

            <div class="section-header">

                <h2 class="section-title">
                    Order Queue
                </h2>


                <span class="section-link">
                    Live queue
                </span>

            </div>


            <div class="queue-grid">


                <!-- AWAITING PAYMENT -->

                <div class="queue-card">

                    <div class="queue-header">

                        <div class="queue-title">
                            Awaiting Payment
                        </div>


                        <div
                            class="queue-count"
                            id="awaitingPaymentQueueCount"
                        >
                            —
                        </div>

                    </div>


                    <div
                        class="queue-empty"
                        id="awaitingPaymentQueue"
                    >
                        Loading order queue...
                    </div>

                </div>


                <!-- PREPARING -->

                <div class="queue-card">

                    <div class="queue-header">

                        <div class="queue-title">
                            Preparing
                        </div>


                        <div
                            class="queue-count"
                            id="preparingQueueCount"
                        >
                            —
                        </div>

                    </div>


                    <div
                        class="queue-empty"
                        id="preparingQueue"
                    >
                        Loading order queue...
                    </div>

                </div>


                <!-- READY -->

                <div class="queue-card">

                    <div class="queue-header">

                        <div class="queue-title">
                            Ready
                        </div>


                        <div
                            class="queue-count"
                            id="readyQueueCount"
                        >
                            —
                        </div>

                    </div>


                    <div
                        class="queue-empty"
                        id="readyQueue"
                    >
                        Loading order queue...
                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             QUICK ACTIONS
        ================================================== -->

        <section class="section">

            <div class="section-header">

                <h2 class="section-title">
                    Quick Actions
                </h2>

            </div>


            <div class="actions-grid">


                <!-- ORDER QUEUE -->

                <div class="action-card">

                    <div class="action-title">
                        Order Queue
                    </div>


                    <div class="action-description">
                        View incoming orders, confirm payments,
                        and manage preparation status.
                    </div>

                </div>


                <!-- PAYMENTS -->

                <div class="action-card">

                    <div class="action-title">
                        Payments
                    </div>


                    <div class="action-description">
                        Review orders waiting for cashier
                        payment confirmation.
                    </div>

                </div>


                <!-- ORDER HISTORY -->

                <div class="action-card">

                    <div class="action-title">
                        Order History
                    </div>


                    <div class="action-description">
                        Review completed and previous orders.
                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             SYSTEM STATUS
        ================================================== -->

        <div class="system-banner">

            <span class="system-dot"></span>


            <span id="systemStatus">
                KohiCebu Staff Portal is connected.
            </span>

        </div>


    </main>

</div>


<!-- =========================================================
     END SHIFT CONFIRMATION MODAL
========================================================== -->

<div
    class="shift-modal-overlay"
    id="endShiftModal"
    hidden
>

    <div
        class="shift-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="endShiftModalTitle"
    >


        <!-- MODAL ICON -->

        <div class="shift-modal-icon">
            <span>↪</span>
        </div>


        <!-- MODAL CONTENT -->

        <div class="shift-modal-content">

            <h2
                class="shift-modal-title"
                id="endShiftModalTitle"
            >
                End Shift
            </h2>


            <p class="shift-modal-question">
                Are you sure you want to end your current shift?
            </p>


            <p class="shift-modal-description">
                This will close your active shift and record
                the end time. You can start a new shift again later.
            </p>

        </div>


        <!-- MODAL DIVIDER -->

        <div class="shift-modal-divider"></div>


        <!-- MODAL ACTIONS -->

        <div class="shift-modal-actions">

            <button
                type="button"
                class="shift-modal-button shift-modal-cancel"
                id="cancelEndShiftButton"
            >
                Cancel
            </button>


            <button
                type="button"
                class="shift-modal-button shift-modal-confirm"
                id="confirmEndShiftButton"
            >
                End Shift
            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     STAFF DASHBOARD JAVASCRIPT
========================================================== -->

<script src="js/dashboard.js"></script>


</body>

</html>
