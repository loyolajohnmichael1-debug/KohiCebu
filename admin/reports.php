<?php

/*
 * =========================================================
 * KohiCebu Admin Portal
 *
 * Reports
 *
 * Provides:
 *
 * - Date range filtering
 * - Sales summary
 * - Order overview
 * - Financial overview
 * - Sales trend
 * - Completion overview
 * - Top selling products
 * - Payment method breakdown
 *
 * Reporting data is loaded through
 * the protected reporting API.
 * =========================================================
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
        KohiCebu — Reports
    </title>


    <!-- =====================================================
         SHARED ADMIN PORTAL STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >


    <!-- =====================================================
         PAGE-SPECIFIC STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/reports.css"
    >

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
                    MONITORING
                </div>

                <h1>
                    Reports
                </h1>

                <p>
                    Review sales, orders, payments, and product
                    performance across KohiCebu.
                </p>

            </div>


            <div class="header-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="refreshReportsButton"
                >
                    REFRESH
                </button>

            </div>

        </header>


        <!-- =================================================
             REPORT PERIOD
             ================================================= -->

        <section class="report-period">

            <div class="report-period-content">

                <div class="section-eyebrow">
                    REPORT PERIOD
                </div>

                <h2 id="reportPeriodTitle">
                    All Time
                </h2>

                <p id="reportPeriodDescription">
                    Showing all recorded order data.
                </p>

            </div>


            <div class="report-period-controls">

                <div class="filter-group">

                    <label for="reportPeriodSelect">
                        PERIOD
                    </label>

                    <select
                        id="reportPeriodSelect"
                    >

                        <option value="all">
                            All Time
                        </option>

                        <option value="today">
                            Today
                        </option>

                        <option value="week">
                            This Week
                        </option>

                        <option value="month">
                            This Month
                        </option>

                        <option value="custom">
                            Custom Range
                        </option>

                    </select>

                </div>


                <!-- =================================================
                     CUSTOM DATE RANGE
                     ================================================= -->

                <div
                    class="custom-date-controls"
                    id="customDateControls"
                    hidden
                >

                    <div class="filter-group">

                        <label for="reportStartDate">
                            START DATE
                        </label>

                        <input
                            type="date"
                            id="reportStartDate"
                        >

                    </div>


                    <div class="filter-group">

                        <label for="reportEndDate">
                            END DATE
                        </label>

                        <input
                            type="date"
                            id="reportEndDate"
                        >

                    </div>


                    <button
                        type="button"
                        class="primary-button"
                        id="applyDateRangeButton"
                    >
                        APPLY
                    </button>

                </div>

            </div>

        </section>


        <!-- =================================================
             SALES SUMMARY
             ================================================= -->

        <section class="reports-summary">


            <!-- PAID REVENUE -->

            <div class="summary-card summary-card--primary">

                <div class="summary-label">
                    PAID REVENUE
                </div>

                <div
                    class="summary-value"
                    id="paidRevenue"
                >
                    ₱0.00
                </div>

                <div class="summary-note">
                    Successfully paid orders
                </div>

            </div>


            <!-- TOTAL ORDERS -->

            <div class="summary-card">

                <div class="summary-label">
                    TOTAL ORDERS
                </div>

                <div
                    class="summary-value"
                    id="totalOrders"
                >
                    0
                </div>

                <div class="summary-note">
                    All recorded orders
                </div>

            </div>


            <!-- PAID ORDERS -->

            <div class="summary-card">

                <div class="summary-label">
                    PAID ORDERS
                </div>

                <div
                    class="summary-value"
                    id="paidOrders"
                >
                    0
                </div>

                <div class="summary-note">
                    Orders with confirmed payment
                </div>

            </div>


            <!-- UNPAID VALUE -->

            <div class="summary-card">

                <div class="summary-label">
                    UNPAID VALUE
                </div>

                <div
                    class="summary-value"
                    id="unpaidOrderValue"
                >
                    ₱0.00
                </div>

                <div class="summary-note">
                    Orders awaiting payment
                </div>

            </div>

        </section>


        <!-- =================================================
             ORDER OVERVIEW
             ================================================= -->

        <section class="reports-section">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        ORDER MONITORING
                    </div>

                    <h2>
                        Order Overview
                    </h2>

                </div>

            </div>


            <div class="reports-overview-grid">


                <!-- =================================================
                     ORDER SUMMARY
                     ================================================= -->

                <div class="report-panel">

                    <div class="report-panel-header">

                        <div class="report-panel-eyebrow">
                            ORDER SUMMARY
                        </div>

                        <h3>
                            Order Activity
                        </h3>

                    </div>


                    <div class="metric-list">

                        <div class="metric-row">

                            <span>
                                Total Orders
                            </span>

                            <strong id="summaryTotalOrders">
                                0
                            </strong>

                        </div>


                        <div class="metric-row">

                            <span>
                                Paid Orders
                            </span>

                            <strong id="summaryPaidOrders">
                                0
                            </strong>

                        </div>


                        <div class="metric-row">

                            <span>
                                Unpaid Orders
                            </span>

                            <strong id="unpaidOrders">
                                0
                            </strong>

                        </div>


                        <div class="metric-row">

                            <span>
                                Completed Orders
                            </span>

                            <strong id="summaryCompletedOrders">
                                0
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- =================================================
                     FINANCIAL SUMMARY
                     ================================================= -->

                <div class="report-panel">

                    <div class="report-panel-header">

                        <div class="report-panel-eyebrow">
                            FINANCIAL SUMMARY
                        </div>

                        <h3>
                            Revenue Overview
                        </h3>

                    </div>


                    <div class="metric-list">

                        <div class="metric-row">

                            <span>
                                Gross Order Value
                            </span>

                            <strong id="grossOrderValue">
                                ₱0.00
                            </strong>

                        </div>


                        <div class="metric-row">

                            <span>
                                Paid Revenue
                            </span>

                            <strong id="summaryPaidRevenue">
                                ₱0.00
                            </strong>

                        </div>


                        <div class="metric-row">

                            <span>
                                Unpaid Value
                            </span>

                            <strong id="summaryUnpaidValue">
                                ₱0.00
                            </strong>

                        </div>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             SALES TREND
             ================================================= -->

        <section class="reports-section">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        SALES PERFORMANCE
                    </div>

                    <h2>
                        Sales Trend
                    </h2>

                    <p>
                        Daily paid revenue for the selected reporting period.
                    </p>

                </div>

            </div>


            <div class="sales-trend-card">


                <!-- =================================================
                     SALES TREND CHART
                     ================================================= -->

                <div
                    class="sales-trend-chart"
                    id="salesTrendChart"
                >

                    <div class="sales-trend-empty">
                        Loading sales trend...
                    </div>

                </div>


                <!-- =================================================
                     SALES TREND SUMMARY
                     ================================================= -->

                <div
                    class="sales-trend-summary"
                    id="salesTrendSummary"
                >

                    <div class="trend-summary-item">

                        <span class="trend-summary-label">
                            TOTAL PAID REVENUE
                        </span>

                        <strong id="trendTotalRevenue">
                            ₱0.00
                        </strong>

                    </div>


                    <div class="trend-summary-item">

                        <span class="trend-summary-label">
                            PAID ORDERS
                        </span>

                        <strong id="trendTotalOrders">
                            0
                        </strong>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             COMPLETION OVERVIEW
             ================================================= -->

        <section class="reports-section">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        ORDER STATUS
                    </div>

                    <h2>
                        Completion Overview
                    </h2>

                </div>

            </div>


            <div class="completion-panel">

                <div class="completion-stat">

                    <span>
                        COMPLETED ORDERS
                    </span>

                    <strong id="completedOrders">
                        0
                    </strong>

                </div>


                <div class="completion-description">

                    <strong>
                        Completed orders recorded in the selected period
                    </strong>

                    <p>
                        This value reflects orders currently marked as completed.
                    </p>

                </div>

            </div>

        </section>


        <!-- =================================================
             TOP SELLING PRODUCTS
             ================================================= -->

        <section class="reports-section">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        PRODUCT PERFORMANCE
                    </div>

                    <h2>
                        Top Selling Products
                    </h2>

                </div>

            </div>


            <div class="reports-table-section">

                <div class="table-wrapper">

                    <table class="reports-table">

                        <thead>

                            <tr>

                                <th>
                                    PRODUCT
                                </th>

                                <th>
                                    CATEGORY
                                </th>

                                <th>
                                    UNITS SOLD
                                </th>

                                <th>
                                    SALES
                                </th>

                            </tr>

                        </thead>


                        <tbody id="topProductsTable">

                            <tr>

                                <td
                                    colspan="4"
                                    class="table-loading"
                                >
                                    Loading product data...

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </section>


        <!-- =================================================
             PAYMENT METHODS
             ================================================= -->

        <section class="reports-section">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        PAYMENT MONITORING
                    </div>

                    <h2>
                        Payment Methods
                    </h2>

                </div>

            </div>


            <div
                class="payment-grid"
                id="paymentMethods"
            >

                <div class="payment-empty">
                    Loading payment data...
                </div>

            </div>

        </section>


        <!-- =================================================
             REPORT STATUS
             ================================================= -->

        <section class="reports-section reports-section-last">

            <div
                class="report-status"
                id="reportStatus"
            >

                <span class="report-status-dot"></span>

                <div>

                    <strong>
                        Reports are up to date
                    </strong>

                    <p>
                        Data is loaded from the KohiCebu reporting API.
                    </p>

                </div>

            </div>

        </section>


    </main>

</div>


<!-- =========================================================
     PAGE JAVASCRIPT
     ========================================================= -->

<script src="js/reports.js"></script>

</body>

</html>