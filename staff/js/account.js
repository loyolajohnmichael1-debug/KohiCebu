/* =========================================================
   KOHICEBU STAFF PORTAL
   MY ACCOUNT
========================================================= */


/* =========================================================
   SESSION DATA
========================================================= */

function getAccountSession() {

    return {

        fullName:
            sessionStorage.getItem("full_name") ||
            localStorage.getItem("full_name") ||
            sessionStorage.getItem("name") ||
            localStorage.getItem("name") ||
            "Staff",

        email:
            sessionStorage.getItem("email") ||
            localStorage.getItem("email") ||
            "—",

        role:
            sessionStorage.getItem("role") ||
            localStorage.getItem("role") ||
            "STAFF"

    };

}


/* =========================================================
   GET INITIAL
========================================================= */

function getInitial(name) {

    if (!name) {
        return "S";
    }


    const trimmedName =
        String(name).trim();


    if (!trimmedName) {
        return "S";
    }


    return trimmedName
        .charAt(0)
        .toUpperCase();

}


/* =========================================================
   DISPLAY SIDEBAR STAFF INFORMATION
========================================================= */

function displaySidebarStaffInformation(
    session
) {

    const staffName =
        document.getElementById(
            "staffName"
        );

    const staffRole =
        document.getElementById(
            "staffRole"
        );


    if (staffName) {

        staffName.textContent =
            session.fullName;

    }


    if (staffRole) {

        staffRole.textContent =
            session.role;

    }

}


/* =========================================================
   DISPLAY ACCOUNT
========================================================= */

function loadAccountInformation() {

    const session =
        getAccountSession();


    /* =====================================================
       ELEMENTS
    ===================================================== */

    const accountAvatar =
        document.getElementById(
            "accountAvatar"
        );

    const accountName =
        document.getElementById(
            "accountName"
        );

    const accountRoleBadge =
        document.getElementById(
            "accountRole"
        );

    const accountFullName =
        document.getElementById(
            "accountFullName"
        );

    const accountEmail =
        document.getElementById(
            "accountEmail"
        );

    const accountAccountRole =
        document.getElementById(
            "accountAccountRole"
        );


    /* =====================================================
       SIDEBAR
    ===================================================== */

    displaySidebarStaffInformation(
        session
    );


    /* =====================================================
       AVATAR
    ===================================================== */

    if (accountAvatar) {

        accountAvatar.textContent =
            getInitial(
                session.fullName
            );

    }


    /* =====================================================
       PROFILE NAME
    ===================================================== */

    if (accountName) {

        accountName.textContent =
            session.fullName;

    }


    /* =====================================================
       PROFILE ROLE
    ===================================================== */

    if (accountRoleBadge) {

        accountRoleBadge.textContent =
            session.role;

    }


    /* =====================================================
       FULL NAME
    ===================================================== */

    if (accountFullName) {

        accountFullName.textContent =
            session.fullName;

    }


    /* =====================================================
       EMAIL
    ===================================================== */

    if (accountEmail) {

        accountEmail.textContent =
            session.email;

    }


    /* =====================================================
       ACCOUNT ROLE
    ===================================================== */

    if (accountAccountRole) {

        accountAccountRole.textContent =
            session.role;

    }

}


/* =========================================================
   INITIALIZE
========================================================= */

function initializeAccountPage() {

    loadAccountInformation();

}


/* =========================================================
   PAGE READY
========================================================= */

if (
    document.readyState === "loading"
) {

    document.addEventListener(
        "DOMContentLoaded",
        initializeAccountPage
    );

}
else {

    initializeAccountPage();

}