document.addEventListener("DOMContentLoaded", function () {

    /*
     * =====================================================
     * ELEMENTS
     * =====================================================
     */

    const paidRevenue =
        document.getElementById("paidRevenue");

    const totalOrders =
        document.getElementById("totalOrders");

    const paidOrders =
        document.getElementById("paidOrders");

    const completedOrders =
        document.getElementById("completedOrders");

    const unpaidOrderValue =
        document.getElementById("unpaidOrderValue");

    const summaryTotalOrders =
        document.getElementById("summaryTotalOrders");

    const summaryPaidOrders =
        document.getElementById("summaryPaidOrders");

    const unpaidOrders =
        document.getElementById("unpaidOrders");

    const summaryCompletedOrders =
        document.getElementById("summaryCompletedOrders");

    const grossOrderValue =
        document.getElementById("grossOrderValue");

    const summaryPaidRevenue =
        document.getElementById("summaryPaidRevenue");

    const summaryUnpaidValue =
        document.getElementById("summaryUnpaidValue");

    const topProductsTable =
        document.getElementById("topProductsTable");

    const paymentMethods =
        document.getElementById("paymentMethods");

    const refreshButton =
        document.getElementById("refreshReportsButton");

    const reportStatus =
        document.getElementById("reportStatus");

    const currentDate =
        document.getElementById("currentDate");


    /*
     * Sales Trend
     */

    const salesTrendChart =
        document.getElementById("salesTrendChart");

    const trendTotalRevenue =
        document.getElementById("trendTotalRevenue");

    const trendTotalOrders =
        document.getElementById("trendTotalOrders");


    /*
     * Report period controls
     */

    const reportPeriodSelect =
        document.getElementById("reportPeriodSelect");

    const customDateControls =
        document.getElementById("customDateControls");

    const reportStartDate =
        document.getElementById("reportStartDate");

    const reportEndDate =
        document.getElementById("reportEndDate");

    const applyDateRangeButton =
        document.getElementById("applyDateRangeButton");

    const reportPeriodTitle =
        document.getElementById("reportPeriodTitle");

    const reportPeriodDescription =
        document.getElementById("reportPeriodDescription");


    /*
     * =====================================================
     * API
     * =====================================================
     */

    const API_URL =
        "../api/get_reports.php";


    /*
     * =====================================================
     * CURRENT FILTER
     * =====================================================
     */

    let currentFilter = {

        type: "all",

        dateFrom: null,

        dateTo: null

    };


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
     * DATE
     * =====================================================
     */

    function updateCurrentDate() {

        if (!currentDate) {
            return;
        }

        const now =
            new Date();

        currentDate.textContent =
            now.toLocaleDateString(
                "en-PH",
                {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric"
                }
            );

    }


    /*
     * =====================================================
     * DATE HELPERS
     * =====================================================
     */

    function padNumber(number) {

        return String(number)
            .padStart(2, "0");

    }


    function formatDateForApi(date) {

        return (
            date.getFullYear()
            + "-"
            + padNumber(
                date.getMonth() + 1
            )
            + "-"
            + padNumber(
                date.getDate()
            )
        );

    }


    function getTodayDate() {

        return formatDateForApi(
            new Date()
        );

    }


    function getStartOfWeek() {

        const date =
            new Date();

        const day =
            date.getDay();

        /*
         * Sunday = 0
         * Monday = 1
         *
         * Reports use Monday
         * as the beginning of the week.
         */

        const difference =
            day === 0
                ? 6
                : day - 1;

        date.setDate(
            date.getDate() - difference
        );

        return formatDateForApi(
            date
        );

    }


    function getEndOfWeek() {

        const date =
            new Date();

        const day =
            date.getDay();

        const difference =
            day === 0
                ? 0
                : 7 - day;

        date.setDate(
            date.getDate() + difference
        );

        return formatDateForApi(
            date
        );

    }


    function getStartOfMonth() {

        const now =
            new Date();

        const date =
            new Date(
                now.getFullYear(),
                now.getMonth(),
                1
            );

        return formatDateForApi(
            date
        );

    }


    function getEndOfMonth() {

        const now =
            new Date();

        const date =
            new Date(
                now.getFullYear(),
                now.getMonth() + 1,
                0
            );

        return formatDateForApi(
            date
        );

    }


    /*
     * =====================================================
     * PERIOD DESCRIPTION
     * =====================================================
     */

    function updatePeriodDisplay() {

        if (
            !reportPeriodTitle ||
            !reportPeriodDescription
        ) {

            return;

        }


        switch (
            currentFilter.type
        ) {

            case "today":

                reportPeriodTitle.textContent =
                    "Today";

                reportPeriodDescription.textContent =
                    "Showing orders recorded today.";

                break;


            case "week":

                reportPeriodTitle.textContent =
                    "This Week";

                reportPeriodDescription.textContent =
                    "Showing orders recorded from Monday through Sunday.";

                break;


            case "month":

                reportPeriodTitle.textContent =
                    "This Month";

                reportPeriodDescription.textContent =
                    "Showing orders recorded during the current month.";

                break;


            case "custom":

                reportPeriodTitle.textContent =
                    "Custom Range";

                reportPeriodDescription.textContent =
                    currentFilter.dateFrom
                    + " to "
                    + currentFilter.dateTo;

                break;


            case "all":

            default:

                reportPeriodTitle.textContent =
                    "All Time";

                reportPeriodDescription.textContent =
                    "Showing all recorded order data.";

                break;

        }

    }


    /*
     * =====================================================
     * CURRENCY FORMAT
     * =====================================================
     */

    function formatCurrency(value) {

        return new Intl.NumberFormat(
            "en-PH",
            {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(
            Number(value) || 0
        );

    }


    /*
     * =====================================================
     * NUMBER FORMAT
     * =====================================================
     */

    function formatNumber(value) {

        return Number(
            value || 0
        ).toLocaleString(
            "en-PH"
        );

    }


    /*
     * =====================================================
     * SALES TREND
     * =====================================================
     */

    function formatTrendDate(dateString) {

        if (!dateString) {
            return "";
        }


        const parts =
            dateString.split("-");


        if (parts.length !== 3) {
            return dateString;
        }


        const date =
            new Date(
                Number(parts[0]),
                Number(parts[1]) - 1,
                Number(parts[2])
            );


        return date.toLocaleDateString(
            "en-PH",
            {
                month: "short",
                day: "numeric"
            }
        );

    }


    function formatTrendDateLong(dateString) {

        if (!dateString) {
            return "";
        }


        const parts =
            dateString.split("-");


        if (parts.length !== 3) {
            return dateString;
        }


        const date =
            new Date(
                Number(parts[0]),
                Number(parts[1]) - 1,
                Number(parts[2])
            );


        return date.toLocaleDateString(
            "en-PH",
            {
                weekday: "short",
                year: "numeric",
                month: "long",
                day: "numeric"
            }
        );

    }


    function renderSalesTrend(trend) {

        if (!salesTrendChart) {
            return;
        }


        /*
         * -------------------------------------------------
         * EMPTY DATA
         * -------------------------------------------------
         */

        if (
            !Array.isArray(trend) ||
            trend.length === 0
        ) {

            salesTrendChart.innerHTML = `
                <div class="sales-trend-empty">
                    No sales data available for this period.
                </div>
            `;


            if (trendTotalRevenue) {

                trendTotalRevenue.textContent =
                    formatCurrency(0);

            }


            if (trendTotalOrders) {

                trendTotalOrders.textContent =
                    formatNumber(0);

            }

            return;

        }


        /*
         * -------------------------------------------------
         * PREPARE DATA
         * -------------------------------------------------
         */

        const points =
            trend.map(function (item) {

                return {

                    date:
                        item.date,

                    orderCount:
                        Number(
                            item.order_count
                        ) || 0,

                    revenue:
                        Number(
                            item.paid_revenue
                        ) || 0

                };

            });


        /*
         * -------------------------------------------------
         * TOTALS
         * -------------------------------------------------
         */

        const totalRevenue =
            points.reduce(
                function (total, item) {

                    return total
                        + item.revenue;

                },
                0
            );


        const totalOrders =
            points.reduce(
                function (total, item) {

                    return total
                        + item.orderCount;

                },
                0
            );


        if (trendTotalRevenue) {

            trendTotalRevenue.textContent =
                formatCurrency(
                    totalRevenue
                );

        }


        if (trendTotalOrders) {

            trendTotalOrders.textContent =
                formatNumber(
                    totalOrders
                );

        }


        /*
         * -------------------------------------------------
         * CHART DIMENSIONS
         * -------------------------------------------------
         */

        const graphWidth =
            Math.max(
                520,
                points.length * 90
            );


        const graphHeight =
            250;


        const chartLeft =
            48;

        const chartRight =
            20;

        const chartTop =
            10;

        const chartBottom =
            34;


        const usableWidth =
            graphWidth
            - chartLeft
            - chartRight;


        const usableHeight =
            graphHeight
            - chartTop
            - chartBottom;


        /*
         * -------------------------------------------------
         * MAX VALUE
         * -------------------------------------------------
         */

        const maxRevenue =
            Math.max(
                ...points.map(
                    function (item) {
                        return item.revenue;
                    }
                ),
                0
            );


        /*
         * Keep the chart readable
         * when all values are zero.
         */

        const chartMax =
            maxRevenue > 0
                ? maxRevenue
                : 100;


        /*
         * -------------------------------------------------
         * BUILD CHART
         * -------------------------------------------------
         */

        let gridLines = "";

        let yLabels = "";

        let xLabels = "";

        let chartPoints = "";

        let chartLines = "";


        const gridCount =
            4;


        /*
         * Y-axis grid
         */

        for (
            let i = 0;
            i <= gridCount;
            i++
        ) {

            const ratio =
                i / gridCount;


            const value =
                chartMax
                * (1 - ratio);


            const top =
                chartTop
                + (
                    usableHeight
                    * ratio
                );


            gridLines += `
                <div
                    class="sales-trend-grid-line"
                    style="position:absolute; top:${top}px; left:0;"
                ></div>
            `;


            yLabels += `
                <span
                    class="sales-trend-y-label"
                    style="top:${top}px;"
                >
                    ${escapeHtml(
                        formatCurrency(
                            value
                        )
                    )}
                </span>
            `;

        }


        /*
         * X-axis positions
         */

        points.forEach(
            function (item, index) {

                const ratio =
                    points.length === 1
                        ? 0.5
                        : index /
                            (
                                points.length - 1
                            );


                const x =
                    chartLeft
                    + (
                        usableWidth
                        * ratio
                    );


                const revenueRatio =
                    item.revenue
                    / chartMax;


                const y =
                    chartTop
                    + usableHeight
                    - (
                        usableHeight
                        * revenueRatio
                    );


                /*
                 * Point
                 */

                chartPoints += `
                    <div
                        class="sales-trend-point"
                        style="
                            left:${x}px;
                            top:${y}px;
                        "
                    ></div>

                    <div
                        class="sales-trend-tooltip"
                        style="
                            left:${x}px;
                            top:${y}px;
                        "
                    >

                        <strong>
                            ${escapeHtml(
                                formatTrendDateLong(
                                    item.date
                                )
                            )}
                        </strong>

                        Revenue:
                        ${escapeHtml(
                            formatCurrency(
                                item.revenue
                            )
                        )}

                        <br>

                        Orders:
                        ${escapeHtml(
                            formatNumber(
                                item.orderCount
                            )
                        )}

                    </div>
                `;


                /*
                 * X-axis label
                 */

                xLabels += `
                    <span
                        class="sales-trend-x-label"
                        style="left:${x}px;"
                    >
                        ${escapeHtml(
                            formatTrendDate(
                                item.date
                            )
                        )}
                    </span>
                `;


                /*
                 * Connecting line
                 */

                if (
                    index <
                    points.length - 1
                ) {

                    const next =
                        points[index + 1];


                    const nextRatio =
                        points.length === 1
                            ? 0.5
                            : (
                                index + 1
                            ) /
                            (
                                points.length - 1
                            );


                    const nextX =
                        chartLeft
                        + (
                            usableWidth
                            * nextRatio
                        );


                    const nextRevenueRatio =
                        next.revenue
                        / chartMax;


                    const nextY =
                        chartTop
                        + usableHeight
                        - (
                            usableHeight
                            * nextRevenueRatio
                        );


                    const dx =
                        nextX - x;

                    const dy =
                        nextY - y;


                    const length =
                        Math.sqrt(
                            (
                                dx * dx
                            )
                            +
                            (
                                dy * dy
                            )
                        );


                    const angle =
                        Math.atan2(
                            dy,
                            dx
                        )
                        * (
                            180 / Math.PI
                        );


                    chartLines += `
                        <div
                            class="sales-trend-line"
                            style="
                                left:${x}px;
                                top:${y}px;
                                width:${length}px;
                                transform:rotate(${angle}deg);
                            "
                        ></div>
                    `;

                }

            }
        );


        salesTrendChart.innerHTML = `

            <div
                class="sales-trend-graph"
                style="
                    min-width:${graphWidth}px;
                    height:${graphHeight}px;
                "
            >

                <div
                    class="sales-trend-grid"
                    style="
                        top:${chartTop}px;
                        bottom:${chartBottom}px;
                    "
                >

                    ${gridLines}

                </div>


                <div
                    class="sales-trend-y-axis"
                    style="
                        top:${chartTop}px;
                        bottom:${chartBottom}px;
                    "
                >

                    ${yLabels}

                </div>


                <div
                    class="sales-trend-points"
                    style="
                        top:${chartTop}px;
                        bottom:${chartBottom}px;
                    "
                >

                    ${chartLines}

                    ${chartPoints}

                </div>


                <div
                    class="sales-trend-x-axis"
                >

                    ${xLabels}

                </div>

            </div>

        `;

    }


    /*
     * =====================================================
     * LOADING STATE
     * =====================================================
     */

    function showLoadingState() {

        const loadingValue = "…";


        if (paidRevenue) {
            paidRevenue.textContent =
                loadingValue;
        }

        if (totalOrders) {
            totalOrders.textContent =
                loadingValue;
        }

        if (paidOrders) {
            paidOrders.textContent =
                loadingValue;
        }

        if (completedOrders) {
            completedOrders.textContent =
                loadingValue;
        }

        if (unpaidOrderValue) {
            unpaidOrderValue.textContent =
                loadingValue;
        }

        if (summaryTotalOrders) {
            summaryTotalOrders.textContent =
                loadingValue;
        }

        if (summaryPaidOrders) {
            summaryPaidOrders.textContent =
                loadingValue;
        }

        if (unpaidOrders) {
            unpaidOrders.textContent =
                loadingValue;
        }

        if (summaryCompletedOrders) {
            summaryCompletedOrders.textContent =
                loadingValue;
        }

        if (grossOrderValue) {
            grossOrderValue.textContent =
                loadingValue;
        }

        if (summaryPaidRevenue) {
            summaryPaidRevenue.textContent =
                loadingValue;
        }

        if (summaryUnpaidValue) {
            summaryUnpaidValue.textContent =
                loadingValue;
        }


        /*
         * Sales Trend loading state
         */

        if (salesTrendChart) {

            salesTrendChart.innerHTML = `
                <div class="sales-trend-empty">
                    Loading sales trend...
                </div>
            `;

        }


        if (trendTotalRevenue) {

            trendTotalRevenue.textContent =
                loadingValue;

        }


        if (trendTotalOrders) {

            trendTotalOrders.textContent =
                loadingValue;

        }


        if (topProductsTable) {

            topProductsTable.innerHTML = `
                <tr>
                    <td
                        colspan="4"
                        class="table-loading"
                    >
                        Loading product performance...
                    </td>
                </tr>
            `;

        }


        if (paymentMethods) {

            paymentMethods.innerHTML = `
                <div class="payment-empty">
                    Loading payment data...
                </div>
            `;

        }


        updateStatus(
            "Loading report data",
            "Please wait while the selected report period is loaded."
        );

    }


    /*
     * =====================================================
     * TOP PRODUCTS
     * =====================================================
     */

    function renderTopProducts(products) {

        if (!topProductsTable) {
            return;
        }


        if (
            !Array.isArray(products) ||
            products.length === 0
        ) {

            topProductsTable.innerHTML = `
                <tr>
                    <td
                        colspan="4"
                        class="table-loading"
                    >
                        No paid product sales found for this period.
                    </td>
                </tr>
            `;

            return;

        }


        let rows = "";


        products.forEach(function (product) {

            rows += `
                <tr>

                    <td>
                        ${escapeHtml(
                            product.product_name
                        )}
                    </td>

                    <td>
                        ${escapeHtml(
                            product.category ||
                            "—"
                        )}
                    </td>

                    <td>
                        ${formatNumber(
                            product.units_sold
                        )}
                    </td>

                    <td>
                        ${formatCurrency(
                            product.sales_amount
                        )}
                    </td>

                </tr>
            `;

        });


        topProductsTable.innerHTML =
            rows;

    }


    /*
     * =====================================================
     * PAYMENT METHODS
     * =====================================================
     */

    function renderPaymentMethods(payments) {

        if (!paymentMethods) {
            return;
        }


        if (
            !Array.isArray(payments) ||
            payments.length === 0
        ) {

            paymentMethods.innerHTML = `
                <div class="payment-empty">
                    No paid payment records found for this period.
                </div>
            `;

            return;

        }


        let cards = "";


        payments.forEach(function (payment) {

            const method =
                payment.payment_method ||
                "Unknown";

            const orderCount =
                Number(
                    payment.order_count
                ) || 0;


            cards += `
                <div class="payment-card">

                    <div class="payment-info">

                        <div class="payment-method">
                            ${escapeHtml(
                                method
                            )}
                        </div>

                        <div class="payment-orders">

                            ${formatNumber(
                                orderCount
                            )}

                            ${
                                orderCount === 1
                                    ? "order"
                                    : "orders"
                            }

                        </div>

                    </div>


                    <div class="payment-amount">

                        ${formatCurrency(
                            payment.total_amount
                        )}

                    </div>

                </div>
            `;

        });


        paymentMethods.innerHTML =
            cards;

    }


    /*
     * =====================================================
     * HTML ESCAPE
     * =====================================================
     */

    function escapeHtml(value) {

        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");

    }


    /*
     * =====================================================
     * REPORT STATUS
     * =====================================================
     */

    function updateStatus(
        title,
        message
    ) {

        if (!reportStatus) {
            return;
        }


        const titleElement =
            reportStatus.querySelector("strong");

        const messageElement =
            reportStatus.querySelector("p");


        if (titleElement) {

            titleElement.textContent =
                title;

        }


        if (messageElement) {

            messageElement.textContent =
                message;

        }

    }


    /*
     * =====================================================
     * BUILD API URL
     * =====================================================
     */

    function buildApiUrl() {

        const params =
            new URLSearchParams();


        if (currentFilter.dateFrom) {

            params.set(
                "date_from",
                currentFilter.dateFrom
            );

        }


        if (currentFilter.dateTo) {

            params.set(
                "date_to",
                currentFilter.dateTo
            );

        }


        const query =
            params.toString();


        if (!query) {

            return API_URL;

        }


        return API_URL
            + "?"
            + query;

    }


    /*
     * =====================================================
     * LOAD REPORT DATA
     * =====================================================
     */

    async function loadReportData() {

        const session =
            getAdminSession();


        if (!session.token) {

            updateStatus(
                "Admin session not found",
                "Please log in again to access reports."
            );

            return;

        }


        showLoadingState();


        if (refreshButton) {

            refreshButton.disabled =
                true;

        }


        if (applyDateRangeButton) {

            applyDateRangeButton.disabled =
                true;

        }


        try {

            const response =
                await fetch(
                    buildApiUrl(),
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

            if (
                response.status === 401
            ) {

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

            if (
                response.status === 403
            ) {

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
                    "Reports request failed."
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
                    "Unable to load report data."
                );

            }


            /*
             * -------------------------------------------------
             * SUMMARY
             * -------------------------------------------------
             */

            const summary =
                data.summary || {};


            /*
             * -------------------------------------------------
             * SALES SUMMARY
             * -------------------------------------------------
             */

            if (paidRevenue) {

                paidRevenue.textContent =
                    formatCurrency(
                        summary.paid_revenue
                    );

            }


            if (totalOrders) {

                totalOrders.textContent =
                    formatNumber(
                        summary.total_orders
                    );

            }


            if (paidOrders) {

                paidOrders.textContent =
                    formatNumber(
                        summary.paid_orders
                    );

            }


            if (completedOrders) {

                completedOrders.textContent =
                    formatNumber(
                        summary.completed_orders
                    );

            }


            if (unpaidOrderValue) {

                unpaidOrderValue.textContent =
                    formatCurrency(
                        summary.unpaid_order_value
                    );

            }


            /*
             * -------------------------------------------------
             * ORDER OVERVIEW
             * -------------------------------------------------
             */

            if (summaryTotalOrders) {

                summaryTotalOrders.textContent =
                    formatNumber(
                        summary.total_orders
                    );

            }


            if (summaryPaidOrders) {

                summaryPaidOrders.textContent =
                    formatNumber(
                        summary.paid_orders
                    );

            }


            if (unpaidOrders) {

                unpaidOrders.textContent =
                    formatNumber(
                        summary.unpaid_orders
                    );

            }


            if (summaryCompletedOrders) {

                summaryCompletedOrders.textContent =
                    formatNumber(
                        summary.completed_orders
                    );

            }


            /*
             * -------------------------------------------------
             * FINANCIAL OVERVIEW
             * -------------------------------------------------
             */

            if (grossOrderValue) {

                grossOrderValue.textContent =
                    formatCurrency(
                        summary.gross_order_value
                    );

            }


            if (summaryPaidRevenue) {

                summaryPaidRevenue.textContent =
                    formatCurrency(
                        summary.paid_revenue
                    );

            }


            if (summaryUnpaidValue) {

                summaryUnpaidValue.textContent =
                    formatCurrency(
                        summary.unpaid_order_value
                    );

            }


            /*
             * -------------------------------------------------
             * SALES TREND
             * -------------------------------------------------
             */

            renderSalesTrend(
                data.sales_trend
            );


            /*
             * -------------------------------------------------
             * PRODUCT PERFORMANCE
             * -------------------------------------------------
             */

            renderTopProducts(
                data.top_products
            );


            /*
             * -------------------------------------------------
             * PAYMENT METHODS
             * -------------------------------------------------
             */

            renderPaymentMethods(
                data.payment_breakdown
            );


            /*
             * -------------------------------------------------
             * SUCCESS
             * -------------------------------------------------
             */

            updateStatus(
                "Reports are up to date",
                "Data is loaded for the selected report period."
            );

        } catch (error) {

            console.error(
                "Reports Error:",
                error
            );


            const errorValue =
                "—";


            if (paidRevenue) {
                paidRevenue.textContent =
                    errorValue;
            }

            if (totalOrders) {
                totalOrders.textContent =
                    errorValue;
            }

            if (paidOrders) {
                paidOrders.textContent =
                    errorValue;
            }

            if (completedOrders) {
                completedOrders.textContent =
                    errorValue;
            }

            if (unpaidOrderValue) {
                unpaidOrderValue.textContent =
                    errorValue;
            }

            if (summaryTotalOrders) {
                summaryTotalOrders.textContent =
                    errorValue;
            }

            if (summaryPaidOrders) {
                summaryPaidOrders.textContent =
                    errorValue;
            }

            if (unpaidOrders) {
                unpaidOrders.textContent =
                    errorValue;
            }

            if (summaryCompletedOrders) {
                summaryCompletedOrders.textContent =
                    errorValue;
            }

            if (grossOrderValue) {
                grossOrderValue.textContent =
                    errorValue;
            }

            if (summaryPaidRevenue) {
                summaryPaidRevenue.textContent =
                    errorValue;
            }

            if (summaryUnpaidValue) {
                summaryUnpaidValue.textContent =
                    errorValue;
            }


            /*
             * Sales Trend error
             */

            if (salesTrendChart) {

                salesTrendChart.innerHTML = `
                    <div class="sales-trend-empty">
                        Unable to load sales trend.
                    </div>
                `;

            }


            if (trendTotalRevenue) {

                trendTotalRevenue.textContent =
                    errorValue;

            }


            if (trendTotalOrders) {

                trendTotalOrders.textContent =
                    errorValue;

            }


            if (topProductsTable) {

                topProductsTable.innerHTML = `
                    <tr>
                        <td
                            colspan="4"
                            class="table-loading"
                        >
                            Unable to load product performance.
                        </td>
                    </tr>
                `;

            }


            if (paymentMethods) {

                paymentMethods.innerHTML = `
                    <div class="payment-empty">
                        Unable to load payment data.
                    </div>
                `;

            }


            updateStatus(
                "Unable to load reports",
                error.message ||
                "Please try again."
            );

        } finally {

            if (refreshButton) {

                refreshButton.disabled =
                    false;

            }

            if (applyDateRangeButton) {

                applyDateRangeButton.disabled =
                    false;

            }

        }

    }


    /*
     * =====================================================
     * PERIOD CHANGE
     * =====================================================
     */

    if (reportPeriodSelect) {

        reportPeriodSelect.addEventListener(
            "change",
            function () {

                const selected =
                    reportPeriodSelect.value;


                /*
                 * ---------------------------------------------
                 * CUSTOM RANGE
                 * ---------------------------------------------
                 */

                if (selected === "custom") {

                    if (customDateControls) {

                        customDateControls.hidden =
                            false;

                    }

                    updatePeriodDisplay();

                    return;

                }


                /*
                 * Hide custom controls
                 */

                if (customDateControls) {

                    customDateControls.hidden =
                        true;

                }


                /*
                 * ---------------------------------------------
                 * TODAY
                 * ---------------------------------------------
                 */

                if (selected === "today") {

                    const today =
                        getTodayDate();

                    currentFilter = {

                        type: "today",

                        dateFrom: today,

                        dateTo: today

                    };

                }


                /*
                 * ---------------------------------------------
                 * THIS WEEK
                 * ---------------------------------------------
                 */

                else if (
                    selected === "week"
                ) {

                    currentFilter = {

                        type: "week",

                        dateFrom:
                            getStartOfWeek(),

                        dateTo:
                            getEndOfWeek()

                    };

                }


                /*
                 * ---------------------------------------------
                 * THIS MONTH
                 * ---------------------------------------------
 */

                else if (
                    selected === "month"
                ) {

                    currentFilter = {

                        type: "month",

                        dateFrom:
                            getStartOfMonth(),

                        dateTo:
                            getEndOfMonth()

                    };

                }


                /*
                 * ---------------------------------------------
                 * ALL TIME
                 * ---------------------------------------------
                 */

                else {

                    currentFilter = {

                        type: "all",

                        dateFrom: null,

                        dateTo: null

                    };

                }


                updatePeriodDisplay();

                loadReportData();

            }
        );

    }


    /*
     * =====================================================
     * APPLY CUSTOM DATE RANGE
     * =====================================================
     */

    if (applyDateRangeButton) {

        applyDateRangeButton.addEventListener(
            "click",
            function () {

                const startDate =
                    reportStartDate
                        ? reportStartDate.value
                        : "";

                const endDate =
                    reportEndDate
                        ? reportEndDate.value
                        : "";


                /*
                 * ---------------------------------------------
                 * VALIDATION
                 * ---------------------------------------------
                 */

                if (
                    !startDate ||
                    !endDate
                ) {

                    updateStatus(
                        "Date range required",
                        "Please select both a start date and an end date."
                    );

                    return;

                }


                if (
                    startDate > endDate
                ) {

                    updateStatus(
                        "Invalid date range",
                        "The start date cannot be later than the end date."
                    );

                    return;

                }


                /*
                 * ---------------------------------------------
                 * APPLY FILTER
                 * ---------------------------------------------
                 */

                currentFilter = {

                    type: "custom",

                    dateFrom: startDate,

                    dateTo: endDate

                };


                updatePeriodDisplay();

                loadReportData();

            }
        );

    }


    /*
     * =====================================================
     * REFRESH
     * =====================================================
     */

    if (refreshButton) {

        refreshButton.addEventListener(
            "click",
            loadReportData
        );

    }


    /*
     * =====================================================
     * INITIALIZE
     * =====================================================
     */

    updateCurrentDate();

    updatePeriodDisplay();

    loadReportData();

});