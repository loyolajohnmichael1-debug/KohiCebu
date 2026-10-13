<?php

$currentPage = basename($_SERVER['PHP_SELF']);

?>

<aside class="sidebar">

    <!-- BRAND -->

    <div class="brand">

        <div class="brand-name">
            kohicebu.
        </div>

        <div class="brand-role">
            Admin Portal
        </div>

    </div>


    <!-- NAVIGATION -->

    <nav class="nav">

        <div class="nav-label">
            Management
        </div>


        <!-- DASHBOARD -->

        <a
            href="dashboard.php"
            class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ⌂
            </span>

            <span>
                Dashboard
            </span>

        </a>


        <!-- INVENTORY -->

        <a
            href="inventory.php"
            class="nav-link <?= $currentPage === 'inventory.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ▦
            </span>

            <span>
                Inventory
            </span>

        </a>


        <!-- PRODUCTS -->

        <a
            href="products.php"
            class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ▣
            </span>

            <span>
                Products
            </span>

        </a>


        <!-- RECIPES -->

        <a
            href="recipes.php"
            class="nav-link <?= $currentPage === 'recipes.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ≡
            </span>

            <span>
                Recipes
            </span>

        </a>


        <!-- STAFF MANAGEMENT -->

        <a
            href="staff.php"
            class="nav-link <?= $currentPage === 'staff.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ◉
            </span>

            <span>
                Staff Management
            </span>

        </a>


        <!-- PROMOTIONS & UPDATES -->

        <a
            href="website_updates.php"
            class="nav-link <?= $currentPage === 'website_updates.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ▤
            </span>

            <span>
                Promotions & Updates
            </span>

        </a>


        <!-- MONITORING -->

        <div class="nav-label">
            Monitoring
        </div>


        <!-- REPORTS -->

        <a
            href="reports.php"
            class="nav-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ▤
            </span>

            <span>
                Reports
            </span>

        </a>


        <!-- AUDIT LOGS -->

        <a
            href="audit_logs.php"
            class="nav-link <?= $currentPage === 'audit_logs.php' ? 'active' : '' ?>"
        >

            <span class="nav-icon">
                ◷
            </span>

            <span>
                Audit Logs
            </span>

        </a>

    </nav>


    <!-- ACCOUNT -->

    <div class="sidebar-bottom">

        <div class="admin-profile">

            <div
                class="admin-name"
                id="adminName"
            >
                Administrator
            </div>

            <div
                class="admin-role"
                id="adminRole"
            >
                ADMIN
            </div>

            <button
                type="button"
                class="sign-out"
                id="signOutButton"
            >
                Sign Out
            </button>

        </div>

    </div>

</aside>


<!-- =========================================================
     SIGN OUT CONFIRMATION MODAL
     ========================================================= -->

<div
    class="logout-modal"
    id="logoutModal"
    aria-hidden="true"
>

    <div
        class="logout-modal-backdrop"
        id="logoutModalBackdrop"
    ></div>


    <div
        class="logout-modal-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="logoutModalTitle"
    >

        <div class="logout-modal-icon">
            <span>↗</span>
        </div>


        <div class="logout-modal-content">

            <h2 id="logoutModalTitle">
                Sign out?
            </h2>

            <p>
                Are you sure you want to sign out of the
                KohiCebu Admin Portal?
            </p>

        </div>


        <div class="logout-modal-actions">

            <button
                type="button"
                class="logout-cancel-button"
                id="cancelLogoutButton"
            >
                Cancel
            </button>

            <button
                type="button"
                class="logout-confirm-button"
                id="confirmLogoutButton"
            >
                Sign Out
            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     SIGN OUT MODAL SCRIPT
     ========================================================= -->

<script>

(function () {

    const signOutButton =
        document.getElementById("signOutButton");

    const logoutModal =
        document.getElementById("logoutModal");

    const logoutModalBackdrop =
        document.getElementById("logoutModalBackdrop");

    const cancelLogoutButton =
        document.getElementById("cancelLogoutButton");

    const confirmLogoutButton =
        document.getElementById("confirmLogoutButton");


    if (
        !signOutButton ||
        !logoutModal ||
        !cancelLogoutButton ||
        !confirmLogoutButton
    ) {
        return;
    }


    let isLoggingOut = false;


    /*
     * Open logout modal.
     */

    function openLogoutModal() {

        if (isLoggingOut) {
            return;
        }


        logoutModal.classList.add(
            "is-open"
        );

        logoutModal.setAttribute(
            "aria-hidden",
            "false"
        );


        document.body.classList.add(
            "logout-modal-open"
        );


        window.setTimeout(
            function () {

                cancelLogoutButton.focus();

            },
            80
        );

    }


    /*
     * Close logout modal.
     */

    function closeLogoutModal() {

        if (isLoggingOut) {
            return;
        }


        logoutModal.classList.remove(
            "is-open"
        );

        logoutModal.setAttribute(
            "aria-hidden",
            "true"
        );


        document.body.classList.remove(
            "logout-modal-open"
        );

    }


    /*
     * Sign Out button.
     */

    signOutButton.addEventListener(
        "click",
        function () {

            openLogoutModal();

        }
    );


    /*
     * Cancel button.
     */

    cancelLogoutButton.addEventListener(
        "click",
        function () {

            closeLogoutModal();

        }
    );


    /*
     * Click outside modal.
     */

    if (logoutModalBackdrop) {

        logoutModalBackdrop.addEventListener(
            "click",
            function () {

                closeLogoutModal();

            }
        );

    }


    /*
     * Confirm logout.
     */

    confirmLogoutButton.addEventListener(
        "click",
        function () {

            if (isLoggingOut) {
                return;
            }


            isLoggingOut = true;


            confirmLogoutButton.disabled = true;

            cancelLogoutButton.disabled = true;


            confirmLogoutButton.textContent =
                "Signing Out...";


            window.setTimeout(
                function () {

                    window.location.href =
                        "logout.php";

                },
                180
            );

        }
    );


    /*
     * Escape key closes the modal.
     */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                logoutModal.classList.contains("is-open")
            ) {

                closeLogoutModal();

            }

        }
    );


    /*
     * Enter key confirms logout when
     * the confirm button is focused.
     */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Enter" &&
                logoutModal.classList.contains("is-open") &&
                document.activeElement === confirmLogoutButton
            ) {

                confirmLogoutButton.click();

            }

        }
    );

})();

</script>



