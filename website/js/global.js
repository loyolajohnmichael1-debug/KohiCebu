document.addEventListener("DOMContentLoaded", function () {

    const menuButton =
        document.getElementById("mobileMenuButton");

    const mobileNav =
        document.getElementById("mobileNav");

    const header =
        document.querySelector(".site-header");


    /* =========================================
       MOBILE MENU
       ========================================= */

    if (menuButton && mobileNav) {

        function closeMenu() {

            menuButton.classList.remove("is-active");

            mobileNav.classList.remove("is-open");

            menuButton.setAttribute(
                "aria-expanded",
                "false"
            );

            menuButton.setAttribute(
                "aria-label",
                "Open menu"
            );

            document.body.classList.remove(
                "menu-open"
            );
        }


        function openMenu() {

            menuButton.classList.add("is-active");

            mobileNav.classList.add("is-open");

            menuButton.setAttribute(
                "aria-expanded",
                "true"
            );

            menuButton.setAttribute(
                "aria-label",
                "Close menu"
            );

            document.body.classList.add(
                "menu-open"
            );
        }


        menuButton.addEventListener(
            "click",
            function () {

                const isOpen =
                    mobileNav.classList.contains(
                        "is-open"
                    );

                if (isOpen) {
                    closeMenu();
                } else {
                    openMenu();
                }

            }
        );


        mobileNav
            .querySelectorAll("a")
            .forEach(function (link) {

                link.addEventListener(
                    "click",
                    closeMenu
                );

            });


        document.addEventListener(
            "keydown",
            function (event) {

                if (event.key === "Escape") {
                    closeMenu();
                }

            }
        );

    }


    /* =========================================
       HEADER SCROLL EFFECT
       ========================================= */

    if (header) {

        function updateHeader() {

            if (window.scrollY > 20) {

                header.classList.add(
                    "is-scrolled"
                );

            } else {

                header.classList.remove(
                    "is-scrolled"
                );

            }

        }

        updateHeader();

        window.addEventListener(
            "scroll",
            updateHeader,
            {
                passive: true
            }
        );
    }

});