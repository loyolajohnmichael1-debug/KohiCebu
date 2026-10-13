<?php

/*
 * =========================================================
 * KOHICEBU ADMIN PORTAL
 * ADMINISTRATOR DASHBOARD
 * =========================================================
 *
 * Authentication:
 * ../api/login.php
 *
 * IMPORTANT:
 * The frontend checks ADMIN for user experience.
 * Actual authorization must also be enforced
 * by every protected API endpoint.
 *
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
        KohiCebu — Admin Dashboard
    </title>


    <!-- =====================================================
         SHARED ADMIN STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/dashboard.css"
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

    <main class="main">


        <!-- =================================================
             PAGE HEADER
             ================================================= -->

        <header class="page-header">

            <div>

                <div class="page-eyebrow">
                    ADMINISTRATION
                </div>

                <h1>
                    Admin Dashboard
                </h1>

                <p>
                    Manage and monitor the core
                    KohiCebu system.
                </p>

            </div>


            <div
                class="header-date"
                id="currentDate"
            ></div>

        </header>


        <!-- =================================================
             WELCOME PANEL
             ================================================= -->

        <section class="welcome-card">

            <div class="welcome-content">

                <div class="welcome-label">
                    ADMINISTRATOR
                </div>

                <h2 id="welcomeName">
                    Welcome back.
                </h2>

                <p>
                    From here you can manage inventory,
                    product recipes, staff access,
                    promotions and other administrative
                    functions.
                </p>

            </div>


            <div class="welcome-quote">

                <span>
                    Good systems
                </span>

                <strong>
                    brew better days.
                </strong>

                <small>
                    — KohiCebu
                </small>

            </div>

        </section>


        <!-- =================================================
             SYSTEM OVERVIEW
             ================================================= -->

        <section class="section">

            <div class="section-header">

                <div>

                    <h2>
                        System Overview
                    </h2>

                    <p>
                        A quick view of the KohiCebu system.
                    </p>

                </div>


                <button
                    type="button"
                    class="dashboard-refresh"
                    id="refreshDashboardButton"
                >

                    <span>
                        ↻
                    </span>

                    Refresh Data

                </button>

            </div>


            <div class="stats-grid">


                <!-- =================================================
                     PRODUCTS
                     ================================================= -->

                <div class="stat-card">

                    <div class="stat-label">
                        PRODUCTS
                    </div>

                    <div
                        class="stat-value"
                        id="productCount"
                    >
                        —
                    </div>

                    <div class="stat-description">
                        Menu products
                    </div>

                </div>


                <!-- =================================================
                     INGREDIENTS
                     ================================================= -->

                <div class="stat-card">

                    <div class="stat-label">
                        INGREDIENTS
                    </div>

                    <div
                        class="stat-value"
                        id="ingredientCount"
                    >
                        —
                    </div>

                    <div class="stat-description">
                        Inventory ingredients
                    </div>

                </div>


                <!-- =================================================
                     STAFF
                     ================================================= -->

                <div class="stat-card">

                    <div class="stat-label">
                        STAFF
                    </div>

                    <div
                        class="stat-value"
                        id="staffCount"
                    >
                        —
                    </div>

                    <div class="stat-description">
                        Staff accounts
                    </div>

                </div>


                <!-- =================================================
                     ORDERS TODAY
                     ================================================= -->

                <div class="stat-card">

                    <div class="stat-label">
                        ORDERS TODAY
                    </div>

                    <div
                        class="stat-value"
                        id="ordersToday"
                    >
                        —
                    </div>

                    <div class="stat-description">
                        Today's orders
                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             MANAGEMENT
             ================================================= -->

        <section class="section">

            <div class="section-header">

                <div>

                    <h2>
                        Management
                    </h2>

                    <p>
                        Administrative controls and
                        system configuration.
                    </p>

                </div>

            </div>


            <div class="management-grid">


                <!-- =================================================
                     INVENTORY
                     ================================================= -->

                <a
                    href="inventory.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        ▦
                    </div>

                    <div class="management-content">

                        <h3>
                            Inventory
                        </h3>

                        <p>
                            Manage ingredients, stock,
                            adjustments, waste and
                            inventory transactions.
                        </p>

                        <span class="management-action">
                            Manage Inventory →
                        </span>

                    </div>

                </a>


                <!-- =================================================
                     RECIPE MANAGEMENT
                     ================================================= -->

                <a
                    href="recipes.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        ≡
                    </div>

                    <div class="management-content">

                        <h3>
                            Recipe Management
                        </h3>

                        <p>
                            Configure the ingredients
                            and quantities used by
                            each product.
                        </p>

                        <span class="management-action">
                            Manage Recipes →
                        </span>

                    </div>

                </a>


                <!-- =================================================
                     STAFF MANAGEMENT
                     ================================================= -->

                <a
                    href="staff.php"
                    class="management-card"
                >

                    <div class="management-icon">
                        ◉
                    </div>

                    <div class="management-content">

                        <h3>
                            Staff Management
                        </h3>

                        <p>
                            Manage staff accounts,
                            roles and system access.
                        </p>

                        <span class="management-action">
                            Manage Staff →
                        </span>

                    </div>

                </a>


                <!-- =================================================
                     PROMOTIONS & UPDATES
                     ================================================= -->

                <a
                    href="website_updates.php"
                    class="management-card"
                >

                    <div class="management-icon management-icon--promo">
                        ▣
                    </div>

                    <div class="management-content">

                        <h3>
                            Promotions & Updates
                        </h3>

                        <p>
                            Manage promotions,
                            announcements, new products
                            and events.
                        </p>

                        <span class="management-action">
                            Manage Updates →
                        </span>

                    </div>

                </a>

            </div>

        </section>


        <!-- =================================================
             ADMINISTRATOR NOTICE
             ================================================= -->

        <section class="admin-notice">

            <div class="notice-icon">
                !
            </div>

            <div>

                <strong>
                    Administrator Access
                </strong>

                <p>
                    Changes made from the Admin Portal
                    can affect inventory, recipes,
                    staff access and system operations.
                    Review changes carefully before saving.
                </p>

            </div>

        </section>


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


<!-- =========================================================
     DASHBOARD JAVASCRIPT
     ========================================================= -->

<script src="js/dashboard.js"></script>

</body>

</html>