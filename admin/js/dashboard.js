document.addEventListener("DOMContentLoaded", function () {

    const productCount =
        document.getElementById("productCount");

    const ingredientCount =
        document.getElementById("ingredientCount");

    const staffCount =
        document.getElementById("staffCount");

    const ordersToday =
        document.getElementById("ordersToday");

    const refreshButton =
        document.getElementById("refreshDashboardButton");

    const systemStatus =
        document.getElementById("systemStatus");

    const welcomeName =
        document.getElementById("welcomeName");


    /*
     * =====================================================
     * API
     * =====================================================
     */

    const API_URL =
        "../api/get_dashboard_stats.php";


    /*
     * =====================================================
     * ADMIN SESSION
     * =====================================================
     */

    function getAdminSession() {

        return {

            token:
                sessionStorage.getItem("token"),

            role:
                sessionStorage.getItem("role"),

            fullName:
                sessionStorage.getItem("full_name")

        };

    }


    /*
     * =====================================================
     * WELCOME NAME
     * =====================================================
     */

    function updateWelcomeName() {

        const session =
            getAdminSession();

        if (
            welcomeName &&
            session.fullName
        ) {

            welcomeName.textContent =
                "Welcome back, "
                + session.fullName
                + ".";

        }

    }


    /*
     * =====================================================
     * LOAD DASHBOARD DATA
     * =====================================================
     */

    async function loadDashboardData() {

        const session =
            getAdminSession();


        /*
         * -------------------------------------------------
         * TOKEN CHECK
         * -------------------------------------------------
         */

        if (!session.token) {

            if (systemStatus) {

                systemStatus.textContent =
                    "Admin session not found.";

            }

            return;
        }


        /*
         * -------------------------------------------------
         * LOADING STATE
         * -------------------------------------------------
         */

        if (productCount) {
            productCount.textContent = "…";
        }

        if (ingredientCount) {
            ingredientCount.textContent = "…";
        }

        if (staffCount) {
            staffCount.textContent = "…";
        }

        if (ordersToday) {
            ordersToday.textContent = "…";
        }

        if (systemStatus) {

            systemStatus.textContent =
                "Loading dashboard data...";

        }


        if (refreshButton) {

            refreshButton.disabled = true;

        }


        try {

            /*
             * -------------------------------------------------
             * REQUEST
             * -------------------------------------------------
             */

            const response =
                await fetch(
                    API_URL,
                    {
                        method: "GET",

                        headers: {

                            "Authorization":
                                "Bearer "
                                + session.token,

                            "Accept":
                                "application/json"

                        }

                    }
                );


            /*
             * -------------------------------------------------
             * SESSION EXPIRED
             * -------------------------------------------------
             */

            if (response.status === 401) {

                sessionStorage.clear();

                window.location.href =
                    "login.php";

                return;
            }


            /*
             * -------------------------------------------------
             * FORBIDDEN
             * -------------------------------------------------
             */

            if (response.status === 403) {

                throw new Error(
                    "Administrator access required."
                );

            }


            /*
             * -------------------------------------------------
             * HTTP ERROR
             * -------------------------------------------------
             */

            if (!response.ok) {

                throw new Error(
                    "Dashboard request failed."
                );

            }


            /*
             * -------------------------------------------------
             * JSON
             * -------------------------------------------------
             */

            const data =
                await response.json();


            /*
             * -------------------------------------------------
             * API ERROR
             * -------------------------------------------------
             */

            if (!data.success) {

                throw new Error(
                    data.message ||
                    "Unable to load dashboard data."
                );

            }


            /*
             * -------------------------------------------------
             * UPDATE STATISTICS
             * -------------------------------------------------
             */

            const stats =
                data.stats;


            if (productCount) {

                productCount.textContent =
                    Number(
                        stats.products
                    ).toLocaleString();

            }


            if (ingredientCount) {

                ingredientCount.textContent =
                    Number(
                        stats.ingredients
                    ).toLocaleString();

            }


            if (staffCount) {

                staffCount.textContent =
                    Number(
                        stats.staff
                    ).toLocaleString();

            }


            if (ordersToday) {

                ordersToday.textContent =
                    Number(
                        stats.orders_today
                    ).toLocaleString();

            }


            /*
             * -------------------------------------------------
             * SUCCESS STATUS
             * -------------------------------------------------
             */

            if (systemStatus) {

                systemStatus.textContent =
                    "Dashboard data is up to date.";

            }

        } catch (error) {

            console.error(
                "Dashboard Error:",
                error
            );


            /*
             * -------------------------------------------------
             * ERROR DISPLAY
             * -------------------------------------------------
             */

            if (productCount) {
                productCount.textContent = "—";
            }

            if (ingredientCount) {
                ingredientCount.textContent = "—";
            }

            if (staffCount) {
                staffCount.textContent = "—";
            }

            if (ordersToday) {
                ordersToday.textContent = "—";
            }


            if (systemStatus) {

                systemStatus.textContent =
                    error.message ||
                    "Unable to load dashboard data.";

            }

        } finally {

            if (refreshButton) {

                refreshButton.disabled = false;

            }

        }

    }


    /*
     * =====================================================
     * REFRESH BUTTON
     * =====================================================
     */

    if (refreshButton) {

        refreshButton.addEventListener(
            "click",
            loadDashboardData
        );

    }


    /*
     * =====================================================
     * INITIALIZE
     * =====================================================
     */

    updateWelcomeName();

    loadDashboardData();

});