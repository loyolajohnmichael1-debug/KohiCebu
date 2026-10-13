/*
 * =========================================================
 * KOHI CEBU — MY ORDERS
 * =========================================================
 */

document.addEventListener("DOMContentLoaded", function () {

    const ordersLoading =
        document.getElementById("ordersLoading");

    const ordersError =
        document.getElementById("ordersError");

    const ordersEmpty =
        document.getElementById("ordersEmpty");

    const ordersList =
        document.getElementById("ordersList");

    const retryOrders =
        document.getElementById("retryOrders");


    /*
     * -----------------------------------------------------
     * INITIAL LOAD
     * -----------------------------------------------------
     */

    loadOrders();


    /*
     * -----------------------------------------------------
     * RETRY
     * -----------------------------------------------------
     */

    if (retryOrders) {

        retryOrders.addEventListener(
            "click",
            function () {

                loadOrders();

            }
        );

    }


    /*
     * -----------------------------------------------------
     * LOAD ORDERS
     * -----------------------------------------------------
     */

    async function loadOrders() {

        showLoading();

        try {

            const response =
                await fetch("api/orders.php", {
                    method: "GET",
                    headers: {
                        "Accept": "application/json"
                    },
                    credentials: "same-origin"
                });


            const result =
                await response.json();


            if (
                !response.ok ||
                !result.success
            ) {

                throw new Error(
                    result.message ||
                    "Unable to load orders."
                );

            }


            const orders =
                Array.isArray(result.orders)
                    ? result.orders
                    : [];


            if (orders.length === 0) {

                showEmpty();

                return;

            }


            renderOrders(orders);

        } catch (error) {

            console.error(
                "KohiCebu orders error:",
                error
            );

            showError();

        }

    }


    /*
     * -----------------------------------------------------
     * RENDER ORDERS
     * -----------------------------------------------------
     */

    function renderOrders(orders) {

        if (!ordersList) return;

        ordersList.innerHTML =
            orders
                .map(function (order) {

                    return createOrderCard(order);

                })
                .join("");


        hideElement(ordersLoading);
        hideElement(ordersError);
        hideElement(ordersEmpty);

        showElement(ordersList);

    }


    /*
     * -----------------------------------------------------
     * CREATE ORDER CARD
     * -----------------------------------------------------
     */

    function createOrderCard(order) {

        const orderId =
            Number(order.order_id || 0);


        const totalAmount =
            Number(order.total_amount || 0);


        const orderStatus =
            String(
                order.order_status || "UNKNOWN"
            );


        const paymentMethod =
            String(
                order.payment_method || ""
            );


        const paymentStatus =
            String(
                order.payment_status || ""
            );


        const orderSource =
            String(
                order.order_source || ""
            );


        const orderDate =
            formatOrderDate(
                order.order_date
            );


        const statusClass =
            getStatusClass(
                orderStatus
            );


        return `
            <article class="order-card">

                <div class="order-card__header">

                    <div>

                        <p class="order-card__number">
                            Order #${orderId}
                        </p>

                        <p class="order-card__date">
                            ${escapeHtml(orderDate)}
                        </p>

                    </div>

                    <span
                        class="order-status ${statusClass}"
                    >
                        ${escapeHtml(
                            formatStatus(orderStatus)
                        )}
                    </span>

                </div>


                <div class="order-card__body">

                    <div>

                        <p class="order-card__amount-label">
                            Total
                        </p>

                        <p class="order-card__amount">
                            ${formatPrice(totalAmount)}
                        </p>

                    </div>


                    <div class="order-card__meta">

                        ${
                            paymentMethod
                                ? `
                                    <span class="order-meta">
                                        ${escapeHtml(
                                            formatPaymentMethod(
                                                paymentMethod
                                            )
                                        )}
                                    </span>
                                  `
                                : ""
                        }

                        ${
                            paymentStatus
                                ? `
                                    <span class="order-meta">
                                        ${escapeHtml(
                                            formatPaymentStatus(
                                                paymentStatus
                                            )
                                        )}
                                    </span>
                                  `
                                : ""
                        }

                        ${
                            orderSource
                                ? `
                                    <span class="order-meta">
                                        ${escapeHtml(
                                            formatOrderSource(
                                                orderSource
                                            )
                                        )}
                                    </span>
                                  `
                                : ""
                        }

                    </div>

                </div>

            </article>
        `;

    }


    /*
     * -----------------------------------------------------
     * STATUS CLASS
     * -----------------------------------------------------
     */

    function getStatusClass(status) {

        const normalized =
            status
                .trim()
                .toUpperCase()
                .replace(/\s+/g, "_");


        switch (normalized) {

            case "COMPLETED":
                return "order-status--completed";

            case "READY":
                return "order-status--ready";

            case "PAID":
                return "order-status--paid";

            case "PREPARING":
                return "order-status--preparing";

            case "AWAITING_PAYMENT":
                return "order-status--awaiting-payment";

            default:
                return "";

        }

    }


    /*
     * -----------------------------------------------------
     * FORMAT STATUS
     * -----------------------------------------------------
     */

    function formatStatus(status) {

        return String(status || "")
            .trim()
            .replace(/_/g, " ")
            .replace(/\s+/g, " ")
            .toUpperCase();

    }


    /*
     * -----------------------------------------------------
     * FORMAT PAYMENT METHOD
     * -----------------------------------------------------
     */

    function formatPaymentMethod(method) {

        const normalized =
            String(method || "")
                .trim()
                .toUpperCase();


        if (normalized === "CASHIER") {
            return "CASHIER";
        }

        if (normalized === "ONLINE") {
            return "ONLINE";
        }

        if (normalized === "MAYA") {
            return "MAYA";
        }

        return normalized
            .replace(/_/g, " ");

    }


    /*
     * -----------------------------------------------------
     * FORMAT PAYMENT STATUS
     * -----------------------------------------------------
     */

    function formatPaymentStatus(status) {

        return String(status || "")
            .trim()
            .replace(/_/g, " ")
            .replace(/\s+/g, " ")
            .toUpperCase();

    }


    /*
     * -----------------------------------------------------
     * FORMAT ORDER SOURCE
     * -----------------------------------------------------
     */

    function formatOrderSource(source) {

        const normalized =
            String(source || "")
                .trim()
                .toUpperCase();


        if (normalized === "ONLINE_APP") {
            return "ONLINE APP";
        }

        return normalized
            .replace(/_/g, " ");

    }


    /*
     * -----------------------------------------------------
     * FORMAT DATE
     * -----------------------------------------------------
     */

    function formatOrderDate(dateValue) {

        if (!dateValue) {
            return "Date unavailable";
        }


        const date =
            new Date(
                String(dateValue).replace(" ", "T")
            );


        if (Number.isNaN(date.getTime())) {
            return String(dateValue);
        }


        return new Intl.DateTimeFormat(
            "en-PH",
            {
                month: "long",
                day: "numeric",
                year: "numeric",
                hour: "numeric",
                minute: "2-digit"
            }
        ).format(date);

    }


    /*
     * -----------------------------------------------------
     * FORMAT PRICE
     * -----------------------------------------------------
     */

    function formatPrice(amount) {

        return new Intl.NumberFormat(
            "en-PH",
            {
                style: "currency",
                currency: "PHP",
                minimumFractionDigits: 2
            }
        ).format(amount);

    }


    /*
     * -----------------------------------------------------
     * HTML ESCAPE
     * -----------------------------------------------------
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
     * -----------------------------------------------------
     * UI STATES
     * -----------------------------------------------------
     */

    function showLoading() {

        showElement(ordersLoading);

        hideElement(ordersError);
        hideElement(ordersEmpty);
        hideElement(ordersList);

    }


    function showError() {

        hideElement(ordersLoading);
        showElement(ordersError);
        hideElement(ordersEmpty);
        hideElement(ordersList);

    }


    function showEmpty() {

        hideElement(ordersLoading);
        hideElement(ordersError);
        showElement(ordersEmpty);
        hideElement(ordersList);

    }


    function showElement(element) {

        if (!element) return;

        element.hidden = false;

    }


    function hideElement(element) {

        if (!element) return;

        element.hidden = true;

    }

});