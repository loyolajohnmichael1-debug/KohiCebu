<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Staff Management | KohiCebu Admin</title>

    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/staff.css">
    <link rel="stylesheet" href="css/pagination.css">
</head>

<body>

<?php include "includes/sidebar.php"; ?>

<main class="main-content">

    <!-- PAGE HEADER -->
    <header class="page-header">

        <div>
            <h1>Staff Management</h1>
            <p>Manage KohiCebu staff accounts and access.</p>
        </div>

        <button
            type="button"
            class="primary-button"
            id="addStaffButton"
        >
            + Add Staff
        </button>

    </header>


    <!-- =========================================================
         STAFF NOTIFICATION
    ========================================================= -->

    <div
        class="staff-notification"
        id="staffNotification"
        hidden
        role="status"
        aria-live="polite"
    >

        <div
            class="notification-icon"
            id="notificationIcon"
        >
            ✓
        </div>


        <div class="notification-content">

            <div
                class="notification-title"
                id="notificationTitle"
            >
                Success
            </div>

            <div
                class="notification-message"
                id="notificationMessage"
            >
                Staff account updated successfully.
            </div>

        </div>


        <button
            type="button"
            class="notification-close"
            id="closeStaffNotification"
            aria-label="Close notification"
        >
            &times;
        </button>

    </div>


    <!-- SUMMARY CARDS -->
    <section class="summary-grid">

        <div class="summary-card">

            <div class="summary-label">
                TOTAL STAFF
            </div>

            <div
                class="summary-value"
                id="totalStaff"
            >
                0
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                ACTIVE
            </div>

            <div
                class="summary-value"
                id="activeStaff"
            >
                0
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-label">
                INACTIVE
            </div>

            <div
                class="summary-value"
                id="inactiveStaff"
            >
                0
            </div>

        </div>

    </section>


    <!-- STAFF LIST -->
    <section class="content-card">

        <div class="card-header">

            <div>

                <h2>
                    Staff Accounts
                </h2>

                <p id="staffResultCount">
                    0 staff members
                </p>

            </div>


            <button
                type="button"
                class="secondary-button"
                id="refreshStaffButton"
            >
                Refresh
            </button>

        </div>


        <!-- FILTERS -->
        <div class="staff-filters">

            <div class="search-wrapper">

                <input
                    type="text"
                    id="staffSearch"
                    class="search-input"
                    placeholder="Search staff name, email, or phone..."
                >

            </div>


            <select
                id="staffStatusFilter"
                class="filter-select"
            >

                <option value="ALL">
                    All Status
                </option>

                <option value="ACTIVE">
                    Active
                </option>

                <option value="INACTIVE">
                    Inactive
                </option>

            </select>

        </div>


        <!-- LOADING -->
        <div
            class="staff-state"
            id="staffLoading"
            hidden
        >

            <div class="loading-spinner"></div>

            <p>
                Loading staff accounts...
            </p>

        </div>


        <!-- ERROR -->
        <div
            class="staff-state error-state"
            id="staffError"
            hidden
        >

            <p id="staffErrorMessage">
                Unable to load staff accounts.
            </p>

            <button
                type="button"
                class="secondary-button"
                id="retryStaffButton"
            >
                Try Again
            </button>

        </div>


        <!-- EMPTY -->
        <div
            class="staff-state"
            id="staffEmpty"
            hidden
        >

            <p>
                No staff accounts found.
            </p>

        </div>


        <!-- TABLE -->
        <div
            class="table-wrapper"
            id="staffTableWrapper"
        >

            <table class="staff-table">

                <thead>

                    <tr>
                        <th>STAFF</th>
                        <th>EMAIL</th>
                        <th>PHONE</th>
                        <th>ADDRESS</th>
                        <th>ROLE</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>

                </thead>


                <tbody id="staffTableBody">
                    <!-- Staff rows inserted by JavaScript -->
                </tbody>

                </table>

        </div>

        <nav class="admin-pagination" id="staffPagination" aria-label="Staff pages" hidden></nav>

    </section>

</main>


<!-- =========================================================
     ADD STAFF MODAL
========================================================= -->

<div
    class="modal-overlay"
    id="addStaffModal"
    hidden
>

    <div class="modal">

        <div class="modal-header">

            <div>

                <h2>
                    Add Staff Account
                </h2>

                <p>
                    Create a new KohiCebu staff account.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                id="closeAddStaffModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>


        <form id="addStaffForm">

            <div class="form-group">

                <label for="staffFullName">
                    Full Name
                </label>

                <input
                    type="text"
                    id="staffFullName"
                    name="full_name"
                    placeholder="Enter staff full name"
                    required
                >

            </div>


            <div class="form-group">

                <label for="staffEmail">
                    Email Address
                </label>

                <input
                    type="email"
                    id="staffEmail"
                    name="email"
                    placeholder="Enter staff email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="staffPhone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="staffPhone"
                    name="phone"
                    placeholder="Enter phone number"
                >

            </div>


            <div class="form-group">

                <label for="staffAddress">
                    Address
                </label>

                <textarea
                    id="staffAddress"
                    name="address"
                    rows="3"
                    placeholder="Enter staff address"
                ></textarea>

            </div>


            <div class="form-group">

                <label for="staffPassword">
                    Password
                </label>

                <input
                    type="password"
                    id="staffPassword"
                    name="password"
                    placeholder="Minimum 8 characters"
                    minlength="8"
                    required
                >

            </div>


            <div
                class="form-message"
                id="addStaffMessage"
                hidden
            ></div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="cancelAddStaffButton"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="primary-button"
                    id="saveStaffButton"
                >
                    Create Staff
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     EDIT STAFF MODAL
========================================================= -->

<div
    class="modal-overlay"
    id="editStaffModal"
    hidden
>

    <div class="modal">

        <div class="modal-header">

            <div>

                <h2>
                    Edit Staff Account
                </h2>

                <p>
                    Update the staff member's profile information.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                id="closeEditStaffModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>


        <form id="editStaffForm">

            <input
                type="hidden"
                id="editStaffId"
            >


            <div class="form-group">

                <label for="editStaffFullName">
                    Full Name
                </label>

                <input
                    type="text"
                    id="editStaffFullName"
                    name="full_name"
                    placeholder="Enter staff full name"
                    required
                >

            </div>


            <div class="form-group">

                <label for="editStaffEmail">
                    Email Address
                </label>

                <input
                    type="email"
                    id="editStaffEmail"
                    name="email"
                    placeholder="Enter staff email"
                    required
                >

            </div>


            <div class="form-group">

                <label for="editStaffPhone">
                    Phone Number
                </label>

                <input
                    type="text"
                    id="editStaffPhone"
                    name="phone"
                    placeholder="Enter phone number"
                >

            </div>


            <div class="form-group">

                <label for="editStaffAddress">
                    Address
                </label>

                <textarea
                    id="editStaffAddress"
                    name="address"
                    rows="3"
                    placeholder="Enter staff address"
                ></textarea>

            </div>


            <div
                class="form-message"
                id="editStaffMessage"
                hidden
            ></div>


            <div class="modal-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="cancelEditStaffButton"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="primary-button"
                    id="updateStaffButton"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     CONFIRMATION MODAL
========================================================= -->

<div
    class="modal-overlay"
    id="confirmationModal"
    hidden
>

    <div class="modal confirmation-modal">

        <div class="modal-header">

            <div>

                <h2 id="confirmationTitle">
                    Confirm Action
                </h2>

                <p id="confirmationMessage">
                    Are you sure you want to continue?
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                id="closeConfirmationModal"
                aria-label="Close"
            >
                &times;
            </button>

        </div>


        <div class="modal-actions">

            <button
                type="button"
                class="secondary-button"
                id="cancelConfirmationButton"
            >
                Cancel
            </button>


            <button
                type="button"
                class="primary-button"
                id="confirmActionButton"
            >
                Confirm
            </button>

        </div>

    </div>

</div>


<script src="js/pagination.js"></script>
<script src="js/staff.js"></script>

</body>
</html>
