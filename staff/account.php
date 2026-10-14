<?php
/*
 * =========================================================
 * KOHICEBU STAFF PORTAL
 * MY ACCOUNT
 * =========================================================
 */
?>

<!DOCTYPE html>

<html lang="en">

<head>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>KohiCebu — My Account</title>


    <!--
        Account page stylesheet.
    -->

    <link
        rel="stylesheet"
        href="css/account.css"
    >
    <link rel="stylesheet" href="css/polish.css">

</head>


<body>

<div class="app">


    <!-- =================================================
         SIDEBAR
    ================================================== -->

    <?php include "includes/sidebar.php"; ?>


    <!-- =================================================
         MAIN CONTENT
    ================================================== -->

    <main class="main">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <header class="page-header">

            <div>

                <h1 class="page-title">
                    My Account
                </h1>

                <p class="page-subtitle">
                    View your staff account information.
                </p>

            </div>


            <div class="page-date">
                Staff Account
            </div>

        </header>



        <!-- =================================================
             PROFILE HEADER
        ================================================== -->

        <section class="account-profile-card">


            <!-- PROFILE -->

            <div class="account-profile">

                <div
                    class="account-avatar"
                    id="accountAvatar"
                >
                    S
                </div>


                <div class="account-profile-info">

                    <h2
                        class="account-profile-name"
                        id="accountProfileName"
                    >
                        Staff
                    </h2>


                    <div
                        class="account-profile-role"
                        id="accountProfileRole"
                    >
                        STAFF
                    </div>

                </div>

            </div>


            <!-- ACCOUNT STATUS -->

            <div class="account-status">

                <span class="account-status-dot"></span>

                <span>
                    Active Account
                </span>

            </div>

        </section>



        <!-- =================================================
             ACCOUNT INFORMATION
        ================================================== -->

        <section class="account-section">


            <div class="account-section-header">

                <div>

                    <div class="account-section-label">
                        Profile
                    </div>

                    <h2 class="account-section-title">
                        Account Information
                    </h2>

                </div>

            </div>



            <div class="account-information-grid">


                <!-- =================================================
                     FULL NAME
                ================================================== -->

                <div class="account-information-card">

                    <div class="account-information-label">
                        Full Name
                    </div>


                    <div
                        class="account-information-value"
                        id="accountFullName"
                    >
                        —
                    </div>

                </div>



                <!-- =================================================
                     EMAIL
                ================================================== -->

                <div class="account-information-card">

                    <div class="account-information-label">
                        Email Address
                    </div>


                    <div
                        class="account-information-value"
                        id="accountEmail"
                    >
                        —
                    </div>

                </div>



                <!-- =================================================
                     ROLE
                ================================================== -->

                <div class="account-information-card">

                    <div class="account-information-label">
                        Account Role
                    </div>


                    <div
                        class="account-information-value"
                        id="accountRole"
                    >
                        —
                    </div>

                </div>



                <!-- =================================================
                     STATUS
                ================================================== -->

                <div class="account-information-card">

                    <div class="account-information-label">
                        Account Status
                    </div>


                    <div class="account-information-status">

                        <span class="account-status-dot"></span>

                        <span>
                            Active
                        </span>

                    </div>

                </div>


            </div>

        </section>



        <!-- =================================================
             STAFF PORTAL NOTICE
        ================================================== -->

        <section class="account-notice">

            <div class="account-notice-content">

                <div class="account-notice-label">
                    Staff Portal
                </div>


                <div class="account-notice-title">
                    Staff Portal Account
                </div>


                <p class="account-notice-text">
                    This account is authorized to access
                    KohiCebu Staff Portal operations and
                    staff-only features.
                </p>

            </div>

        </section>


    </main>

</div>


<!-- =====================================================
     ACCOUNT JAVASCRIPT
====================================================== -->

<script src="js/account.js"></script>

</body>

</html>
