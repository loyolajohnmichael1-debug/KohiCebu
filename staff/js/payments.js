/* =========================================================
   KOHICEBU STAFF PORTAL
   PAYMENTS
   ========================================================= */

const API_BASE = "../api/";


/* =========================================================
   AUTHENTICATION
   ========================================================= */

const token =
    sessionStorage.getItem("token") ||
    localStorage.getItem("token");

const role =
    sessionStorage.getItem("role") ||
    localStorage.getItem("role");


if (
    !token ||
    (role !== "STAFF" && role !== "ADMIN")
) {
    window.location.href = "login.php";
}


/* =========================================================
   STAFF SESSION DISPLAY
   ========================================================= */

/*
 * Keep the reusable Staff Portal sidebar consistent
 * with the Dashboard and Order Queue.
 *
 * The authenticated staff information is stored in
 * sessionStorage during login. localStorage is used
 * as a fallback for existing sessions.
 */

function getSessionValue(key) {

    return (
        sessionStorage.getItem(key) ||
        localStorage.getItem(key) ||
        ""
    );

}


function displayStaffInformation() {

    const staffName =
        document.getElementById("staffName");

    const staffRole =
        document.getElementById("staffRole");


    const fullName =
        getSessionValue("full_name") ||
        getSessionValue("name") ||
        "Staff";


    const staffRoleValue =
        getSessionValue("role") ||
        "STAFF";


    if (staffName) {

        staffName.textContent =
            fullName;

    }


    if (staffRole) {

        staffRole.textContent =
            staffRoleValue;

    }

}


/* =========================================================
   DOM ELEMENTS
   ========================================================= */

const awaitingPaymentCount =
    document.getElementById(
        "awaitingPaymentCount"
    );

const paidPaymentCount =
    document.getElementById(
        "paidPaymentCount"
    );

const failedPaymentCount =
    document.getElementById(
        "failedPaymentCount"
    );

const expiredPaymentCount =
    document.getElementById(
        "expiredPaymentCount"
    );

const paymentResultCount =
    document.getElementById(
        "paymentResultCount"
    );

const paymentList =
    document.getElementById(
        "paymentList"
    );

const paymentLoading =
    document.getElementById(
        "paymentLoading"
    );

const paymentError =
    document.getElementById(
        "paymentError"
    );

const paymentErrorMessage =
    document.getElementById(
        "paymentErrorMessage"
    );

const paymentEmpty =
    document.getElementById(
        "paymentEmpty"
    );

const refreshPayments =
    document.getElementById(
        "refreshPayments"
    );

const retryPayments =
    document.getElementById(
        "retryPayments"
    );

const paymentSearch =
    document.getElementById(
        "paymentSearch"
    );

const paymentFilters =
    document.querySelectorAll(
        ".payment-filter"
    );

const systemStatus =
    document.getElementById(
        "systemStatus"
    );


/* =========================================================
   STATE
   ========================================================= */

let allOrders = [];

let selectedFilter = "ALL";

let searchTerm = "";

let isLoading = false;


/* =========================================================
   HELPERS
   ========================================================= */

function escapeHtml(value) {

    if (
        value === null ||
        value === undefined
    ) {
        return "";
    }

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


function normalizeStatus(value) {

    return String(value || "")
        .trim()
        .toUpperCase();

}


function formatCurrency(value) {

    const amount =
        Number(value || 0);

    return "₱" +
        amount.toLocaleString(
            "en-PH",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


function formatDate(value) {

    if (!value) {
        return "—";
    }

    const date =
        new Date(
            String(value).replace(
                " ",
                "T"
            )
        );

    if (
        Number.isNaN(
            date.getTime()
        )
    ) {
        return escapeHtml(value);
    }

    return date.toLocaleString(
        "en-PH",
        {
            month: "short",
            day: "numeric",
            year: "numeric",
            hour: "numeric",
            minute: "2-digit"
        }
    );

}


/* =========================================================
   PAYMENT STATE
   ========================================================= */

function getPaymentStatus(order) {

    return normalizeStatus(
        order.payment_status
    );

}


function getOrderStatus(order) {

    return normalizeStatus(
        order.order_status
    );

}


function getPaymentMethod(order) {

    return normalizeStatus(
        order.payment_method
    );

}


/* =========================================================
   PAYMENT CLASSIFICATION
   ========================================================= */

/*
 * This classification follows the payment/order states
 * already used by the KohiCebu backend.
 */

function getPaymentState(order) {

    const paymentStatus =
        getPaymentStatus(order);

    const orderStatus =
        getOrderStatus(order);

    const paymentMethod =
        getPaymentMethod(order);


    /*
     * Cashier order waiting for payment.
     */

    if (
        orderStatus === "AWAITING_PAYMENT" &&
        paymentMethod === "CASHIER" &&
        (
            paymentStatus === "" ||
            paymentStatus === "UNPAID" ||
            paymentStatus === "PENDING"
        )
    ) {

        return "AWAITING_PAYMENT";

    }


    /*
     * Payment failures.
     */

    if (
        paymentStatus === "FAILED" ||
        orderStatus === "PAYMENT_FAILED"
    ) {

        return "FAILED";

    }


    /*
     * Expired payment.
     */

    if (
        paymentStatus === "EXPIRED" ||
        orderStatus === "PAYMENT_EXPIRED"
    ) {

        return "EXPIRED";

    }


    /*
     * Cancelled payment.
     */

    if (
        paymentStatus === "CANCELLED" ||
        orderStatus === "PAYMENT_CANCELLED"
    ) {

        return "CANCELLED";

    }


    /*
     * Successfully paid.
     */

    if (
        paymentStatus === "PAID"
    ) {

        return "PAID";

    }


    /*
     * Fallback.
     */

    if (
        paymentStatus === "UNPAID" ||
        paymentStatus === "PENDING"
    ) {

        return "AWAITING_PAYMENT";

    }


    return "UNKNOWN";

}


/* =========================================================
   LOAD ORDERS / PAYMENTS
   ========================================================= */

async function loadPayments() {

    if (isLoading) {
        return;
    }

    isLoading = true;


    showLoadingState();


    try {

        const response =
            await fetch(
                API_BASE +
                "get_staff_orders.php",
                {
                    method: "GET",

                    headers: {
                        "Authorization":
                            "Bearer " + token
                    }
                }
            );


        const data =
            await response.json();


        if (
            !response.ok ||
            !data.success
        ) {

            throw new Error(
                data.message ||
                "Unable to retrieve payment information."
            );

        }


        allOrders =
            Array.isArray(data.orders)
                ? data.orders
                : [];


        updateSummary();

        renderPayments();


        if (systemStatus) {

            systemStatus.textContent =
                `Payment information updated. ${allOrders.length} order records retrieved.`;

        }

    }
    catch (error) {

        console.error(
            "Payments loading error:",
            error
        );


        showError(
            error.message ||
            "Unable to retrieve payment information."
        );


        if (systemStatus) {

            systemStatus.textContent =
                "Unable to update payment information.";

        }

    }
    finally {

        isLoading = false;

    }

}


/* =========================================================
   SUMMARY
   ========================================================= */

function updateSummary() {

    let awaiting = 0;

    let paid = 0;

    let failed = 0;

    let expired = 0;


    allOrders.forEach(
        order => {

            const state =
                getPaymentState(order);


            if (
                state ===
                "AWAITING_PAYMENT"
            ) {
                awaiting++;
            }


            if (
                state === "PAID"
            ) {
                paid++;
            }


            if (
                state === "FAILED"
            ) {
                failed++;
            }


            if (
                state === "EXPIRED"
            ) {
                expired++;
            }

        }
    );


    if (awaitingPaymentCount) {

        awaitingPaymentCount.textContent =
            awaiting;

    }


    if (paidPaymentCount) {

        paidPaymentCount.textContent =
            paid;

    }


    if (failedPaymentCount) {

        failedPaymentCount.textContent =
            failed;

    }


    if (expiredPaymentCount) {

        expiredPaymentCount.textContent =
            expired;

    }

}


/* =========================================================
   FILTERING
   ========================================================= */

function getFilteredPayments() {

    let filtered =
        allOrders.filter(
            order =>
                getPaymentState(order) !==
                "UNKNOWN"
        );


    if (
        selectedFilter !== "ALL"
    ) {

        filtered =
            filtered.filter(
                order =>
                    getPaymentState(order) ===
                    selectedFilter
            );

    }


    if (searchTerm) {

        const query =
            searchTerm.toLowerCase();


        filtered =
            filtered.filter(
                order => {

                    const orderId =
                        String(
                            order.order_id || ""
                        );


                    const customer =
                        order.customer || {};


                    const customerName =
                        String(
                            customer.full_name || ""
                        );


                    const customerEmail =
                        String(
                            customer.email || ""
                        );


                    return (

                        orderId
                            .toLowerCase()
                            .includes(query)

                        ||

                        customerName
                            .toLowerCase()
                            .includes(query)

                        ||

                        customerEmail
                            .toLowerCase()
                            .includes(query)

                    );

                }
            );

    }


    return filtered;

}


/* =========================================================
   RENDER PAYMENTS
   ========================================================= */

function renderPayments() {

    if (!paymentList) {
        return;
    }


    const payments =
        getFilteredPayments();


    if (paymentResultCount) {

        paymentResultCount.textContent =
            `${payments.length} ${
                payments.length === 1
                    ? "transaction"
                    : "transactions"
            }`;

    }


    hideStates();


    if (
        payments.length === 0
    ) {

        paymentList.innerHTML = "";

        paymentList.hidden = true;

        paymentEmpty.hidden = false;

        return;

    }


    paymentEmpty.hidden = true;

    paymentList.innerHTML =
        payments
            .map(
                order =>
                    renderPaymentCard(order)
            )
            .join("");


    paymentList.hidden = false;

}


/* =========================================================
   PAYMENT CARD
   ========================================================= */

function renderPaymentCard(order) {

    const orderId =
        Number(order.order_id);


    const customer =
        order.customer || {};


    const customerName =
        customer.full_name ||
        "Customer";


    const customerEmail =
        customer.email ||
        "—";


    const amount =
        formatCurrency(
            order.total_amount
        );


    const paymentMethod =
        getPaymentMethod(order);


    const paymentState =
        getPaymentState(order);


    const orderStatus =
        getOrderStatus(order);


    const orderDate =
        formatDate(
            order.order_date
        );


    const paidDate =
        order.paid_at
            ? formatDate(
                order.paid_at
            )
            : "—";


    const mayaReference =
        order.maya_payment_id ||
        order.maya_checkout_id ||
        "";


    return `
        <article class="payment-card">


            <div class="payment-card-header">

                <div>

                    <div class="payment-order-number">
                        Order #${orderId}
                    </div>

                    <div class="payment-customer">
                        ${escapeHtml(
                            customerName
                        )}
                    </div>

                    <div class="payment-customer-email">
                        ${escapeHtml(
                            customerEmail
                        )}
                    </div>

                </div>


                <div class="payment-amount">
                    ${amount}
                </div>

            </div>



            <div class="payment-card-details">


                <div class="payment-detail">

                    <div class="payment-detail-label">
                        Payment Method
                    </div>

                    <div class="payment-detail-value">
                        <span class="payment-method">
                            ${escapeHtml(
                                paymentMethod ||
                                "—"
                            )}
                        </span>
                    </div>

                </div>



                <div class="payment-detail">

                    <div class="payment-detail-label">
                        Payment Status
                    </div>

                    <div class="payment-detail-value">
                        ${renderPaymentStatus(
                            paymentState
                        )}
                    </div>

                </div>



                <div class="payment-detail">

                    <div class="payment-detail-label">
                        Order Status
                    </div>

                    <div class="payment-detail-value">
                        ${escapeHtml(
                            orderStatus ||
                            "—"
                        )}
                    </div>

                </div>



                <div class="payment-detail">

                    <div class="payment-detail-label">
                        Paid At
                    </div>

                    <div class="payment-detail-value">
                        ${escapeHtml(
                            paidDate
                        )}
                    </div>

                </div>


            </div>



            ${
                mayaReference
                    ? `
                        <div class="payment-reference">

                            <span class="payment-reference-label">
                                MAYA REFERENCE
                            </span>

                            <span class="payment-reference-value">
                                ${escapeHtml(
                                    mayaReference
                                )}
                            </span>

                        </div>
                    `
                    : ""
            }



            <div class="payment-card-footer">


                <div>

                    <div class="payment-date">
                        Ordered ${escapeHtml(
                            orderDate
                        )}
                    </div>


                    ${renderVerificationNote(
                        order
                    )}

                </div>


                <div class="payment-actions">

                    ${renderPaymentAction(
                        order
                    )}

                </div>


            </div>


        </article>
    `;

}


/* =========================================================
   PAYMENT STATUS BADGE
   ========================================================= */

function renderPaymentStatus(
    state
) {

    let className =
        "payment-status";


    let label =
        state;


    switch (state) {

        case "PAID":

            className +=
                " payment-status-paid";

            label = "PAID";

            break;


        case "AWAITING_PAYMENT":

            className +=
                " payment-status-awaiting";

            label = "AWAITING PAYMENT";

            break;


        case "FAILED":

            className +=
                " payment-status-failed";

            label = "FAILED";

            break;


        case "EXPIRED":

            className +=
                " payment-status-expired";

            label = "EXPIRED";

            break;


        case "CANCELLED":

            className +=
                " payment-status-cancelled";

            label = "CANCELLED";

            break;


        default:

            label = "UNKNOWN";

            break;

    }


    return `
        <span class="${className}">
            ${escapeHtml(label)}
        </span>
    `;

}


/* =========================================================
   PAYMENT ACTION
   ========================================================= */

function renderPaymentAction(
    order
) {

    const state =
        getPaymentState(order);


    const paymentMethod =
        getPaymentMethod(order);


    /*
     * Only CASHIER + unpaid/awaiting
     * can be manually confirmed.
     */

    if (
        state === "AWAITING_PAYMENT" &&
        paymentMethod === "CASHIER"
    ) {

        return `
            <button
                type="button"
                class="payment-action-button confirm"
                data-action="confirm-cash"
                data-order-id="${Number(
                    order.order_id
                )}"
            >
                CONFIRM PAYMENT
            </button>
        `;

    }


    /*
     * Maya payment status is controlled
     * by the existing payment/webhook flow.
     */

    if (
        paymentMethod === "MAYA"
    ) {

        return `
            <div class="payment-verification-note">

                <span
                    class="payment-verification-dot"
                ></span>

                <span>
                    Maya payment status verified automatically
                </span>

            </div>
        `;

    }


    return "";

}


/* =========================================================
   VERIFICATION NOTE
   ========================================================= */

function renderVerificationNote(
    order
) {

    const state =
        getPaymentState(order);


    const method =
        getPaymentMethod(order);


    if (
        method === "MAYA" &&
        state === "PAID"
    ) {

        return `
            <div class="payment-verification-note">

                <span
                    class="payment-verification-dot"
                ></span>

                <span>
                    Verified payment
                </span>

            </div>
        `;

    }


    if (
        method === "CASHIER" &&
        state === "AWAITING_PAYMENT"
    ) {

        return `
            <div class="payment-verification-note">

                <span
                    class="payment-verification-dot"
                    style="background: var(--warning);"
                ></span>

                <span>
                    Waiting for cashier confirmation
                </span>

            </div>
        `;

    }


    return "";

}


/* =========================================================
   CONFIRM CASH PAYMENT
   ========================================================= */

async function confirmCashPayment(
    orderId
) {

    const confirmed =
        window.confirm(
            `Confirm that cash payment has been received for Order #${orderId}?`
        );


    if (!confirmed) {
        return;
    }


    try {

        setSystemStatus(
            `Confirming payment for Order #${orderId}...`
        );


        const response =
            await fetch(
                API_BASE +
                "confirm_cash_payment.php",
                {
                    method: "POST",

                    headers: {

                        "Content-Type":
                            "application/json",

                        "Authorization":
                            "Bearer " + token

                    },

                    body: JSON.stringify({

                        order_id:
                            Number(orderId)

                    })

                }
            );


        const data =
            await response.json();


        if (
            !response.ok ||
            !data.success
        ) {

            throw new Error(
                data.message ||
                "Unable to confirm cash payment."
            );

        }


        /*
         * Reload from the server.
         *
         * We do NOT locally change the payment state.
         * The server/database is authoritative.
         */

        await loadPayments();


        setSystemStatus(
            `Cash payment for Order #${orderId} was confirmed successfully.`
        );


    }
    catch (error) {

        console.error(
            "Cash payment confirmation error:",
            error
        );


        window.alert(
            error.message ||
            "Unable to confirm cash payment."
        );


        setSystemStatus(
            "Cash payment confirmation failed."
        );

    }

}


/* =========================================================
   FILTER EVENTS
   ========================================================= */

paymentFilters.forEach(
    filter => {

        filter.addEventListener(
            "click",
            () => {

                paymentFilters.forEach(
                    item =>
                        item.classList.remove(
                            "active"
                        )
                );


                filter.classList.add(
                    "active"
                );


                selectedFilter =
                    normalizeStatus(
                        filter.dataset.filter
                    );


                renderPayments();

            }
        );

    }
);


/* =========================================================
   SEARCH
   ========================================================= */

if (paymentSearch) {

    paymentSearch.addEventListener(
        "input",
        event => {

            searchTerm =
                event.target.value.trim();


            renderPayments();

        }
    );

}


/* =========================================================
   PAYMENT ACTION EVENTS
   ========================================================= */

if (paymentList) {

    paymentList.addEventListener(
        "click",
        event => {

            const button =
                event.target.closest(
                    "[data-action]"
                );


            if (!button) {
                return;
            }


            const action =
                button.dataset.action;


            const orderId =
                Number(
                    button.dataset.orderId
                );


            if (
                action ===
                "confirm-cash"
            ) {

                confirmCashPayment(
                    orderId
                );

            }

        }
    );

}


/* =========================================================
   REFRESH
   ========================================================= */

if (refreshPayments) {

    refreshPayments.addEventListener(
        "click",
        loadPayments
    );

}


if (retryPayments) {

    retryPayments.addEventListener(
        "click",
        loadPayments
    );

}


/* =========================================================
   UI STATES
   ========================================================= */

function hideStates() {

    if (paymentLoading) {
        paymentLoading.hidden = true;
    }

    if (paymentError) {
        paymentError.hidden = true;
    }

    if (paymentEmpty) {
        paymentEmpty.hidden = true;
    }

}


function showLoadingState() {

    if (paymentLoading) {
        paymentLoading.hidden = false;
    }

    if (paymentError) {
        paymentError.hidden = true;
    }

    if (paymentEmpty) {
        paymentEmpty.hidden = true;
    }

    if (paymentList) {
        paymentList.hidden = true;
    }

}


function showError(
    message
) {

    if (paymentLoading) {
        paymentLoading.hidden = true;
    }

    if (paymentEmpty) {
        paymentEmpty.hidden = true;
    }

    if (paymentList) {
        paymentList.hidden = true;
    }


    if (paymentError) {

        paymentError.hidden = false;

    }


    if (paymentErrorMessage) {

        paymentErrorMessage.textContent =
            message;

    }

}


function setSystemStatus(
    message
) {

    if (systemStatus) {

        systemStatus.textContent =
            message;

    }

}


/* =========================================================
   INITIAL LOAD
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        /*
         * Populate the reusable sidebar first.
         */

        displayStaffInformation();

        /*
         * Then load payment data.
         */

        loadPayments();

    }
);