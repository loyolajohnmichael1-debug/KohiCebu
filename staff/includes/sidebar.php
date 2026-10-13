<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
/* =========================================================
   KOHICEBU STAFF — SHARED LOGOUT MODAL
========================================================= */

.staff-logout-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
}

.staff-logout-modal[hidden] {
    display: none !important;
}

.staff-logout-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.58);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
}

.staff-logout-card {
    position: relative;
    z-index: 1;
    width: min(100%, 430px);
    padding: 30px;
    background: #ffffff;
    border: 1px solid #dededb;
    border-radius: 16px;
    box-shadow:
        0 24px 70px rgba(0, 0, 0, 0.22),
        0 8px 24px rgba(0, 0, 0, 0.08);
    animation: staffLogoutModalIn 0.2s ease-out;
}

@keyframes staffLogoutModalIn {

    from {
        opacity: 0;
        transform: translateY(10px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

}

.staff-logout-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    border: 1px solid #dededb;
    border-radius: 12px;
    background: #f5f5f3;
    color: #111111;
    font-size: 22px;
    font-weight: 700;
}

.staff-logout-content h2 {
    margin: 0;
    color: #111111;
    font-size: 24px;
    font-weight: 800;
    letter-spacing: -0.6px;
    line-height: 1.2;
}

.staff-logout-content p {
    margin: 9px 0 0;
    color: #707070;
    font-size: 14px;
    line-height: 1.6;
}

.staff-logout-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 28px;
}

.staff-logout-actions button {
    min-width: 110px;
    min-height: 42px;
    padding: 10px 16px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    transition:
        background 0.16s ease,
        border-color 0.16s ease,
        transform 0.16s ease,
        opacity 0.16s ease;
}

.staff-logout-actions button:hover {
    transform: translateY(-1px);
}

.staff-logout-actions button:active {
    transform: translateY(0);
}

.staff-logout-cancel {
    border: 1px solid #d8d8d5;
    background: #ffffff;
    color: #333333;
}

.staff-logout-cancel:hover {
    background: #f5f5f3;
    border-color: #c8c8c5;
}

.staff-logout-confirm {
    border: 1px solid #111111;
    background: #111111;
    color: #ffffff;
}

.staff-logout-confirm:hover {
    background: #292929;
}

.staff-logout-actions button:disabled {
    cursor: not-allowed;
    opacity: 0.55;
    transform: none;
}

@media (max-width: 520px) {

    .staff-logout-modal {
        padding: 18px;
    }

    .staff-logout-card {
        padding: 24px;
        border-radius: 14px;
    }

    .staff-logout-actions {
        flex-direction: column-reverse;
    }

    .staff-logout-actions button {
        width: 100%;
    }

}
</style>


<aside class="sidebar">

    <!-- =====================================
        BRAND
    ====================================== -->

    <div class="brand">

        <div class="brand-name">
            kohicebu.
        </div>

        <div class="brand-role">
            Staff Portal
        </div>

    </div>


    <!-- =====================================
        NAVIGATION
    ====================================== -->

    <nav class="nav">

        <div class="nav-label">
            Operations
        </div>


        <!-- Dashboard -->

        <a
            href="dashboard.php"
            class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">⌂</span>

            <span>
                Dashboard
            </span>
        </a>


        <!-- Order Queue -->

        <a
            href="orders.php"
            class="nav-link <?= $currentPage === 'orders.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">▣</span>

            <span>
                Order Queue
            </span>
        </a>


        <!-- Payments -->

        <a
            href="payments.php"
            class="nav-link <?= $currentPage === 'payments.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">₱</span>

            <span>
                Payments
            </span>
        </a>


        <!-- Account -->

        <div class="nav-label">
            Account
        </div>


        <a
            href="account.php"
            class="nav-link <?= $currentPage === 'account.php' ? 'active' : '' ?>"
        >
            <span class="nav-icon">◉</span>

            <span>
                My Account
            </span>
        </a>

    </nav>


    <!-- =====================================
        STAFF PROFILE
    ====================================== -->

    <div class="sidebar-bottom">

        <div class="staff-profile">

            <div
                class="staff-name"
                id="staffName"
            >
                Staff
            </div>


            <div
                class="staff-role"
                id="staffRole"
            >
                STAFF
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
     SHARED STAFF LOGOUT MODAL
========================================================= -->

<div
    class="staff-logout-modal"
    id="staffLogoutModal"
    hidden
    aria-hidden="true"
>

    <div
        class="staff-logout-backdrop"
        id="staffLogoutBackdrop"
    ></div>


    <div
        class="staff-logout-card"
        role="dialog"
        aria-modal="true"
        aria-labelledby="staffLogoutTitle"
    >

        <div class="staff-logout-icon">
            <span>↗</span>
        </div>


        <div class="staff-logout-content">

            <h2 id="staffLogoutTitle">
                Sign out?
            </h2>

            <p>
                Are you sure you want to sign out
                of the KohiCebu Staff Portal?
            </p>

        </div>


        <div class="staff-logout-actions">

            <button
                type="button"
                class="staff-logout-cancel"
                id="staffCancelLogoutButton"
            >
                Cancel
            </button>


            <button
                type="button"
                class="staff-logout-confirm"
                id="staffConfirmLogoutButton"
            >
                Sign Out
            </button>

        </div>

    </div>

</div>


<!-- =========================================================
     SHARED STAFF LOGOUT SCRIPT
========================================================= -->

<script>

(function () {

    const logoutButton =
        document.getElementById(
            "signOutButton"
        );

    const logoutModal =
        document.getElementById(
            "staffLogoutModal"
        );

    const logoutBackdrop =
        document.getElementById(
            "staffLogoutBackdrop"
        );

    const cancelButton =
        document.getElementById(
            "staffCancelLogoutButton"
        );

    const confirmButton =
        document.getElementById(
            "staffConfirmLogoutButton"
        );


    if (
        !logoutButton ||
        !logoutModal ||
        !cancelButton ||
        !confirmButton
    ) {
        return;
    }


    /* =====================================================
       OPEN MODAL
    ===================================================== */

    function openLogoutModal() {

        logoutModal.hidden = false;

        logoutModal.setAttribute(
            "aria-hidden",
            "false"
        );

        document.body.style.overflow =
            "hidden";

        confirmButton.disabled = false;

        cancelButton.disabled = false;

        confirmButton.textContent =
            "Sign Out";

    }


    /* =====================================================
       CLOSE MODAL
    ===================================================== */

    function closeLogoutModal() {

        logoutModal.hidden = true;

        logoutModal.setAttribute(
            "aria-hidden",
            "true"
        );

        document.body.style.overflow =
            "";

    }


    /* =====================================================
       CONFIRM LOGOUT
    ===================================================== */

    function confirmLogout() {

        confirmButton.disabled = true;

        cancelButton.disabled = true;

        confirmButton.textContent =
            "Signing Out...";


        sessionStorage.clear();


        setTimeout(
            function () {

                window.location.href =
                    "logout.php";

            },
            180
        );

    }


    /* =====================================================
       SIGN OUT BUTTON
    ===================================================== */

    logoutButton.addEventListener(
        "click",
        function () {

            openLogoutModal();

        }
    );


    /* =====================================================
       CANCEL
    ===================================================== */

    cancelButton.addEventListener(
        "click",
        function () {

            closeLogoutModal();

        }
    );


    /* =====================================================
       BACKDROP
    ===================================================== */

    logoutBackdrop.addEventListener(
        "click",
        function () {

            closeLogoutModal();

        }
    );


    /* =====================================================
       ESCAPE KEY
    ===================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                !logoutModal.hidden
            ) {

                closeLogoutModal();

            }

        }
    );


    /* =====================================================
       ENTER KEY
    ===================================================== */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Enter" &&
                !logoutModal.hidden &&
                !confirmButton.disabled &&
                document.activeElement === confirmButton
            ) {

                confirmLogout();

            }

        }
    );


    /* =====================================================
       CONFIRM BUTTON
    ===================================================== */

    confirmButton.addEventListener(
        "click",
        function () {

            confirmLogout();

        }
    );


})();

</script>