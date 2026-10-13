/* =========================================================
   KOHICEBU ADMIN — STANDARD NAVIGATION
   =========================================================

   Purpose:
   - Keep normal PHP page navigation
   - Preserve shared sidebar behavior
   - Keep active navigation state
   - Avoid SPA DOM replacement
   - Avoid dynamically re-executing page scripts
   - Prevent conflicts with page-specific modals/functions

   IMPORTANT:
   This file is loaded by sidebar.php.
   ========================================================= */

(function () {

    "use strict";


    /* =====================================================
       PREVENT DUPLICATE INITIALIZATION
       ===================================================== */

    if (window.KohiNavigation) {
        return;
    }


    /* =====================================================
       UTILITY — RESOLVE URL
       ===================================================== */

    function resolveUrl(href) {

        if (!href) {
            return null;
        }

        try {

            return new URL(
                href,
                window.location.href
            );

        }
        catch (error) {

            return null;

        }

    }


    /* =====================================================
       UTILITY — GET PAGE NAME
       ===================================================== */

    function getPageName(url) {

        if (!url) {
            return "";
        }

        const pathname =
            url.pathname;

        return pathname.substring(
            pathname.lastIndexOf("/") + 1
        );

    }


    /* =====================================================
       UPDATE ACTIVE NAVIGATION
       ===================================================== */

    function updateActiveNavigation() {

        const currentPage =
            getPageName(
                new URL(
                    window.location.href
                )
            );


        document
            .querySelectorAll(".nav-link")
            .forEach(
                function (link) {

                    const href =
                        link.getAttribute("href");


                    const linkUrl =
                        resolveUrl(href);


                    const linkPage =
                        getPageName(
                            linkUrl
                        );


                    link.classList.toggle(
                        "active",
                        linkPage === currentPage
                    );

                }
            );

    }


    /* =====================================================
       REMOVE OLD NAVIGATION STATE
       ===================================================== */

    function clearNavigationState() {

        document
            .querySelectorAll(
                ".nav-link.is-navigating"
            )
            .forEach(
                function (link) {

                    link.classList.remove(
                        "is-navigating"
                    );

                }
            );

    }


    /* =====================================================
       STANDARD NAVIGATION
       =====================================================

       We intentionally DO NOT intercept clicks.

       The browser will handle:

           products.php
           recipes.php
           inventory.php
           reports.php
           audit_logs.php

       normally.

       This allows every page to load its own:

       - PHP
       - CSS
       - JavaScript
       - modals
       - event listeners
       - forms
       - buttons
       ===================================================== */


    /* =====================================================
       INITIALIZE
       ===================================================== */

    updateActiveNavigation();

    clearNavigationState();


    /* =====================================================
       PUBLIC API
       ===================================================== */

    window.KohiNavigation = {

        /*
         * Compatibility method.
         *
         * If another part of the Admin Portal calls
         * KohiNavigation.navigate(), simply perform
         * normal browser navigation.
         */

        navigate: function (destination) {

            if (!destination) {
                return;
            }


            window.location.href =
                destination;

        }

    };


})();