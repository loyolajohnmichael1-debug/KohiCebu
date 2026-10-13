<?php

session_start();

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

    <title>Audit Logs | KohiCebu Admin</title>


    <!-- =====================================================
         SHARED ADMIN PORTAL STYLES
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/dashboard.css"
    >


    <!-- =====================================================
         AUDIT LOGS PAGE STYLES
    ====================================================== -->

    <link
        rel="stylesheet"
        href="css/audit_logs.css"
    >

</head>


<body>

<div class="app">


    <!-- =====================================================
         SHARED ADMIN SIDEBAR
    ====================================================== -->

    <?php
    require_once "includes/sidebar.php";
    ?>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <main class="main main-content">


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <section class="page-header">

            <div>

                <p class="page-eyebrow">
                    SYSTEM ACTIVITY
                </p>

                <h1>
                    Audit Logs
                </h1>

                <p class="page-description">
                    Review important administrative and staff
                    activities recorded across the KohiCebu system.
                </p>

            </div>


            <button
                type="button"
                id="refreshAuditLogsButton"
                class="refresh-button"
            >
                Refresh
            </button>

        </section>


        <!-- =====================================================
             FILTERS
        ====================================================== -->

        <section class="filter-card">

            <div class="filter-group">

                <label for="dateFrom">
                    From
                </label>

                <input
                    type="date"
                    id="dateFrom"
                >

            </div>


            <div class="filter-group">

                <label for="dateTo">
                    To
                </label>

                <input
                    type="date"
                    id="dateTo"
                >

            </div>


            <button
                type="button"
                id="applyAuditFiltersButton"
                class="apply-button"
            >
                Apply Filters
            </button>


            <button
                type="button"
                id="clearAuditFiltersButton"
                class="clear-button"
            >
                Clear
            </button>

        </section>


        <!-- =====================================================
             AUDIT LOG SUMMARY
        ====================================================== -->

        <section class="summary-card">

            <div>

                <span class="summary-label">
                    Recorded Activities
                </span>

                <strong
                    id="auditLogCount"
                >
                    0
                </strong>

            </div>

        </section>


        <!-- =====================================================
             AUDIT LOG TABLE
        ====================================================== -->

        <section class="table-card">

            <div class="table-header">

                <div>

                    <h2>
                        Activity History
                    </h2>

                    <p>
                        The most recent system activities
                        are displayed first.
                    </p>

                </div>

            </div>


            <div
                class="table-wrapper"
            >

                <table>

                    <thead>

                        <tr>

                            <th>
                                DATE & TIME
                            </th>

                            <th>
                                USER
                            </th>

                            <th>
                                ROLE
                            </th>

                            <th>
                                ACTION
                            </th>

                            <th>
                                MODULE
                            </th>

                            <th>
                                TARGET
                            </th>

                            <th>
                                DESCRIPTION
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        id="auditLogsTableBody"
                    >

                        <tr>

                            <td
                                colspan="7"
                                class="empty-state"
                            >
                                Loading audit logs...
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </section>


    </main>

</div>


<!-- =========================================================
     AUDIT LOGS JAVASCRIPT
========================================================= -->

<script
    src="js/audit_logs.js"
></script>

</body>

</html>