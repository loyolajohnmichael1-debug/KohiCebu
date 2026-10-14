<?php

/*
 * =========================================================
 * KohiCebu Admin Portal
 *
 * Promotions & Website Updates
 *
 * Manages:
 *
 * - Promotions
 * - Announcements
 * - New Product updates
 * - Events
 *
 * Database operations are handled
 * through protected API endpoints.
 * =========================================================
 */

$currentPage = basename($_SERVER['PHP_SELF']);

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

    <title>
        KohiCebu — Promotions & Updates
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
        href="css/website_updates.css"
    >
    <link rel="stylesheet" href="css/pagination.css">

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
                    CONTENT MANAGEMENT
                </div>

                <h1>
                    Promotions & Updates
                </h1>

                <p>
                    Manage promotions, announcements,
                    product updates, and events shown
                    across KohiCebu.
                </p>

            </div>


            <div class="header-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="refreshUpdatesButton"
                >
                    REFRESH
                </button>

                <button
                    type="button"
                    class="primary-button"
                    id="addUpdateButton"
                >
                    + ADD UPDATE
                </button>

            </div>

        </header>


        <!-- =================================================
             SUMMARY
             ================================================= -->

        <section class="updates-summary">


            <div class="summary-card">

                <div class="summary-label">
                    TOTAL UPDATES
                </div>

                <div
                    class="summary-value"
                    id="totalUpdates"
                >
                    —
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    PUBLISHED
                </div>

                <div
                    class="summary-value"
                    id="publishedUpdates"
                >
                    —
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    DRAFTS
                </div>

                <div
                    class="summary-value"
                    id="draftUpdates"
                >
                    —
                </div>

            </div>


            <div class="summary-card">

                <div class="summary-label">
                    ACTIVE NOW
                </div>

                <div
                    class="summary-value"
                    id="activeUpdates"
                >
                    —
                </div>

            </div>

        </section>


        <!-- =================================================
             FILTER BAR
             ================================================= -->

        <section class="updates-toolbar">

            <div class="filter-group">

                <label for="updateTypeFilter">
                    TYPE
                </label>

                <select id="updateTypeFilter">

                    <option value="">
                        All Types
                    </option>

                    <option value="PROMO">
                        Promotion
                    </option>

                    <option value="ANNOUNCEMENT">
                        Announcement
                    </option>

                    <option value="NEW_PRODUCT">
                        New Product
                    </option>

                    <option value="EVENT">
                        Event
                    </option>

                </select>

            </div>


            <div class="filter-group">

                <label for="updateStatusFilter">
                    STATUS
                </label>

                <select id="updateStatusFilter">

                    <option value="">
                        All Statuses
                    </option>

                    <option value="published">
                        Published
                    </option>

                    <option value="draft">
                        Draft
                    </option>

                    <option value="active">
                        Active Now
                    </option>

                    <option value="expired">
                        Expired
                    </option>

                </select>

            </div>

        </section>


        <!-- =================================================
             UPDATES SECTION
             ================================================= -->

        <section class="updates-section">

            <div class="section-header">

                <div>

                    <div class="section-eyebrow">
                        CONTENT
                    </div>

                    <h2>
                        Current Updates
                    </h2>

                </div>

            </div>


            <!-- =================================================
                 UPDATES TABLE
                 ================================================= -->

            <div class="table-wrapper">

                <table class="updates-table">

                    <thead>

                        <tr>

                            <th>
                                UPDATE
                            </th>

                            <th>
                                TYPE
                            </th>

                            <th>
                                SCHEDULE
                            </th>

                            <th>
                                STATUS
                            </th>

                            <th>
                                ACTION
                            </th>

                        </tr>

                    </thead>

                    <tbody id="updatesTableBody">

                        <tr>

                            <td
                                colspan="5"
                                class="table-loading"
                            >
                                Loading updates...
                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

            <nav class="admin-pagination" id="updatesPagination" aria-label="Promotions and updates pages" hidden></nav>

        </section>


    </main>

</div>


<!-- =========================================================
     ADD / EDIT UPDATE MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="updateModal"
    hidden
>

    <div
        class="modal-card update-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="updateModalTitle"
    >


        <!-- =================================================
             MODAL HEADER
             ================================================= -->

        <div class="modal-header">

            <div>

                <div class="modal-eyebrow">
                    CONTENT MANAGEMENT
                </div>

                <h2 id="updateModalTitle">
                    Add Update
                </h2>

                <p>
                    Create a promotion, announcement,
                    product update, or event.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                id="closeUpdateModal"
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <!-- =================================================
             UPDATE FORM
             ================================================= -->

        <form
            id="updateForm"
            enctype="multipart/form-data"
        >


            <!-- =================================================
                 HIDDEN UPDATE ID
                 ================================================= -->

            <input
                type="hidden"
                id="updateId"
                name="update_id"
            >


            <!-- =================================================
                 TYPE
                 ================================================= -->

            <div class="form-group">

                <label for="updateType">
                    TYPE
                </label>

                <select
                    id="updateType"
                    name="update_type"
                    required
                >

                    <option value="">
                        Select type
                    </option>

                    <option value="PROMO">
                        Promotion
                    </option>

                    <option value="ANNOUNCEMENT">
                        Announcement
                    </option>

                    <option value="NEW_PRODUCT">
                        New Product
                    </option>

                    <option value="EVENT">
                        Event
                    </option>

                </select>

            </div>


            <!-- =================================================
                 TITLE
                 ================================================= -->

            <div class="form-group">

                <label for="updateTitle">
                    TITLE
                </label>

                <input
                    type="text"
                    id="updateTitle"
                    name="title"
                    maxlength="150"
                    placeholder="e.g. Weekend Coffee Special"
                    required
                >

            </div>


            <!-- =================================================
                 DESCRIPTION
                 ================================================= -->

            <div class="form-group">

                <label for="updateDescription">
                    DESCRIPTION
                </label>

                <textarea
                    id="updateDescription"
                    name="description"
                    rows="5"
                    placeholder="Write the details of this update..."
                    required
                ></textarea>

            </div>


            <!-- =================================================
                 IMAGE
                 ================================================= -->

            <div class="form-group">

                <label for="updateImage">
                    IMAGE
                </label>

                <input
                    type="file"
                    id="updateImage"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                >

                <small class="form-help">
                    JPG, PNG, or WebP. Optional.
                </small>

            </div>


            <!-- =================================================
                 SCHEDULE
                 ================================================= -->

            <div class="form-section-label">
                SCHEDULE
            </div>


            <div class="form-row">

                <div class="form-group">

                    <label for="updateStartDate">
                        START DATE
                    </label>

                    <input
                        type="date"
                        id="updateStartDate"
                        name="start_date"
                    >

                </div>


                <div class="form-group">

                    <label for="updateEndDate">
                        END DATE
                    </label>

                    <input
                        type="date"
                        id="updateEndDate"
                        name="end_date"
                    >

                </div>

            </div>


            <!-- =================================================
                 PUBLISH STATUS
                 ================================================= -->

            <div class="form-group">

                <label>
                    STATUS
                </label>

                <label class="checkbox-field">

                    <input
                        type="checkbox"
                        id="updatePublished"
                        name="is_published"
                    >

                    <span>
                        Publish this update
                    </span>

                </label>

                <small class="form-help">
                    Unchecked updates are saved as drafts.
                </small>

            </div>


            <!-- =================================================
                 FORM MESSAGE
                 ================================================= -->

            <div
                class="form-message"
                id="updateFormMessage"
                hidden
            ></div>


            <!-- =================================================
                 MODAL ACTIONS
                 ================================================= -->

            <div class="modal-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="cancelUpdateButton"
                >
                    CANCEL
                </button>

                <button
                    type="submit"
                    class="primary-button"
                    id="saveUpdateButton"
                >
                    SAVE UPDATE
                </button>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     DELETE UPDATE CONFIRMATION MODAL
     ========================================================= -->

<div
    class="modal-overlay"
    id="deleteUpdateModal"
    hidden
>

    <div
        class="modal-card delete-update-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="deleteUpdateModalTitle"
    >


        <!-- =================================================
             DELETE MODAL HEADER
             ================================================= -->

        <div class="modal-header">

            <div>

                <div class="modal-eyebrow">
                    CONTENT MANAGEMENT
                </div>

                <h2 id="deleteUpdateModalTitle">
                    Delete Update
                </h2>

                <p>
                    This action will permanently remove
                    this website update.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                id="closeDeleteUpdateModal"
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <!-- =================================================
             DELETE CONTENT
             ================================================= -->

        <div class="delete-update-content">


            <!-- =================================================
                 WARNING
                 ================================================= -->

            <div class="delete-update-warning">

                <div class="delete-update-warning-icon">
                    !
                </div>

                <div>

                    <strong>
                        Are you sure?
                    </strong>

                    <p>
                        This update will be removed from
                        the database. Any associated
                        uploaded image will also be deleted.
                    </p>

                </div>

            </div>


            <!-- =================================================
                 SELECTED UPDATE
                 ================================================= -->

            <div class="delete-update-preview">

                <span class="delete-update-preview-label">
                    UPDATE
                </span>

                <strong
                    id="deleteUpdateTitle"
                >
                    —
                </strong>

            </div>


            <!-- =================================================
                 DELETE MESSAGE
                 ================================================= -->

            <div
                class="form-message"
                id="deleteUpdateMessage"
                hidden
            ></div>


            <!-- =================================================
                 DELETE ACTIONS
                 ================================================= -->

            <div class="modal-actions">

                <button
                    type="button"
                    class="secondary-button"
                    id="cancelDeleteUpdateButton"
                >
                    CANCEL
                </button>

                <button
                    type="button"
                    class="danger-button"
                    id="confirmDeleteUpdateButton"
                >
                    DELETE UPDATE
                </button>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     PAGE JAVASCRIPT
     ========================================================= -->

<script src="js/pagination.js"></script>
<script src="js/website_updates.js"></script>

</body>

</html>
