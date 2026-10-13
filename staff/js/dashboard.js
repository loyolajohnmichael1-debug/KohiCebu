const API_BASE = "../api/";


/* =========================================================
   SESSION
========================================================= */

function getStaffSession() {

    return {
        token: sessionStorage.getItem("token"),
        role: sessionStorage.getItem("role"),
        userId: sessionStorage.getItem("user_id"),
        fullName: sessionStorage.getItem("full_name"),
        email: sessionStorage.getItem("email"),
        expiresAt: sessionStorage.getItem("token_expiry")
    };

}


/* =========================================================
   LOGIN REDIRECT
========================================================= */

function redirectToLogin() {

    sessionStorage.clear();

    window.location.href = "login.php";

}


/* =========================================================
   SESSION VALIDATION
========================================================= */

function checkStaffSession() {

    const session = getStaffSession();


    if (!session.token) {

        redirectToLogin();

        return false;

    }


    if (
        session.role !== "STAFF" &&
        session.role !== "ADMIN"
    ) {

        redirectToLogin();

        return false;

    }


    if (session.expiresAt) {

        const expiry =
            new Date(session.expiresAt).getTime();


        if (
            !isNaN(expiry) &&
            Date.now() >= expiry
        ) {

            redirectToLogin();

            return false;

        }

    }


    return true;

}


/* =========================================================
   STAFF INFORMATION
========================================================= */

function displayStaffInfo() {

    const session = getStaffSession();


    const staffName =
        document.getElementById("staffName");

    const staffRole =
        document.getElementById("staffRole");


    if (staffName) {

        staffName.textContent =
            session.fullName || "Staff";

    }


    if (staffRole) {

        staffRole.textContent =
            session.role || "STAFF";

    }

}


/* =========================================================
   CURRENT DATE
========================================================= */

function displayDate() {

    const dateElement =
        document.getElementById("currentDate");


    if (!dateElement) {
        return;
    }


    const now = new Date();


    dateElement.textContent =
        now.toLocaleDateString(
            "en-US",
            {
                weekday: "long",
                month: "long",
                day: "numeric",
                year: "numeric"
            }
        );

}


/* =========================================================
   API REQUEST
========================================================= */

async function staffApiRequest(
    endpoint,
    options = {}
) {

    const session =
        getStaffSession();


    if (!session.token) {

        redirectToLogin();

        return null;

    }


    const headers = {

        "Content-Type":
            "application/json",

        ...(options.headers || {})

    };


    headers["Authorization"] =
        "Bearer " + session.token;


    try {

        const response =
            await fetch(
                API_BASE + endpoint,
                {
                    ...options,
                    headers: headers
                }
            );


        if (response.status === 401) {

            redirectToLogin();

            return null;

        }


        if (response.status === 403) {

            console.error(
                "Staff API: Permission denied."
            );

            return null;

        }


        return response;

    }

    catch (error) {

        console.error(
            "Staff API connection error:",
            error
        );

        return null;

    }

}


/* =========================================================
   LOAD STAFF ORDERS
========================================================= */

async function loadStaffOrders() {

    const systemStatus =
        document.getElementById(
            "systemStatus"
        );


    if (systemStatus) {

        systemStatus.textContent =
            "Loading live order data...";

    }


    const response =
        await staffApiRequest(
            "get_staff_orders.php"
        );


    if (!response) {
        return;
    }


    let data;


    try {

        data = await response.json();

    }

    catch (error) {

        console.error(
            "Invalid API response:",
            error
        );

        showDashboardError();

        return;

    }


    if (!data.success) {

        console.error(
            "Staff Orders API:",
            data.message
        );

        showDashboardError();

        return;

    }


    const orders =
        Array.isArray(data.orders)
            ? data.orders
            : [];


    updateDashboard(orders);


    if (systemStatus) {

        systemStatus.textContent =
            "Live order data connected.";

    }

}


/* =========================================================
   SHIFT MANAGEMENT
========================================================= */

async function loadCurrentShift() {

    const response =
        await staffApiRequest(
            "get_current_shift.php"
        );


    if (!response) {
        return;
    }


    let data;


    try {

        data = await response.json();

    }

    catch (error) {

        console.error(
            "Invalid shift API response:",
            error
        );

        showShiftError(
            "Unable to check shift status."
        );

        return;

    }


    if (!data.success) {

        console.error(
            "Current Shift API:",
            data.message
        );

        showShiftError(
            data.message ||
            "Unable to check shift status."
        );

        return;

    }


    if (data.has_active_shift) {

        displayActiveShift(
            data.shift
        );

    }

    else {

        displayNoActiveShift();

    }

}


/* =========================================================
   DISPLAY ACTIVE SHIFT
========================================================= */

function displayActiveShift(shift) {

    const shiftCard =
        document.getElementById(
            "shiftCard"
        );

    const shiftStatusIcon =
        document.getElementById(
            "shiftStatusIcon"
        );

    const shiftStatusLabel =
        document.getElementById(
            "shiftStatusLabel"
        );

    const shiftCashierName =
        document.getElementById(
            "shiftCashierName"
        );

    const shiftDetails =
        document.getElementById(
            "shiftDetails"
        );

    const startButton =
        document.getElementById(
            "startShiftButton"
        );

    const endButton =
        document.getElementById(
            "endShiftButton"
        );


    if (shiftCard) {

        shiftCard.classList.remove(
            "shift-inactive",
            "shift-error"
        );

        shiftCard.classList.add(
            "shift-active"
        );

    }


    if (shiftStatusIcon) {

        shiftStatusIcon.textContent =
            "✓";

    }


    if (shiftStatusLabel) {

        shiftStatusLabel.textContent =
            "SHIFT ACTIVE";

    }


    if (shiftCashierName) {

        shiftCashierName.textContent =
            shift.cashier_name ||
            "Staff";

    }


    if (shiftDetails) {

        shiftDetails.textContent =
            "Shift #" +
            shift.shift_id +
            " • Started " +
            formatShiftTime(
                shift.started_at
            );

    }


    if (startButton) {

        startButton.hidden = true;

    }


    if (endButton) {

        endButton.hidden = false;

    }

}


/* =========================================================
   DISPLAY NO ACTIVE SHIFT
========================================================= */

function displayNoActiveShift() {

    const shiftCard =
        document.getElementById(
            "shiftCard"
        );

    const shiftStatusIcon =
        document.getElementById(
            "shiftStatusIcon"
        );

    const shiftStatusLabel =
        document.getElementById(
            "shiftStatusLabel"
        );

    const shiftCashierName =
        document.getElementById(
            "shiftCashierName"
        );

    const shiftDetails =
        document.getElementById(
            "shiftDetails"
        );

    const startButton =
        document.getElementById(
            "startShiftButton"
        );

    const endButton =
        document.getElementById(
            "endShiftButton"
        );


    if (shiftCard) {

        shiftCard.classList.remove(
            "shift-active",
            "shift-error"
        );

        shiftCard.classList.add(
            "shift-inactive"
        );

    }


    if (shiftStatusIcon) {

        shiftStatusIcon.textContent =
            "!";

    }


    if (shiftStatusLabel) {

        shiftStatusLabel.textContent =
            "NO ACTIVE SHIFT";

    }


    if (shiftCashierName) {

        shiftCashierName.textContent =
            "You are currently off duty.";

    }


    if (shiftDetails) {

        shiftDetails.textContent =
            "Start your shift before processing cashier transactions.";

    }


    if (startButton) {

        startButton.hidden = false;

    }


    if (endButton) {

        endButton.hidden = true;

    }

}


/* =========================================================
   SHIFT ERROR
========================================================= */

function showShiftError(message) {

    const shiftCard =
        document.getElementById(
            "shiftCard"
        );

    const shiftStatusIcon =
        document.getElementById(
            "shiftStatusIcon"
        );

    const shiftStatusLabel =
        document.getElementById(
            "shiftStatusLabel"
        );

    const shiftCashierName =
        document.getElementById(
            "shiftCashierName"
        );

    const shiftDetails =
        document.getElementById(
            "shiftDetails"
        );


    if (shiftCard) {

        shiftCard.classList.remove(
            "shift-active",
            "shift-inactive"
        );

        shiftCard.classList.add(
            "shift-error"
        );

    }


    if (shiftStatusIcon) {

        shiftStatusIcon.textContent =
            "!";

    }


    if (shiftStatusLabel) {

        shiftStatusLabel.textContent =
            "SHIFT STATUS ERROR";

    }


    if (shiftCashierName) {

        shiftCashierName.textContent =
            "Unable to determine shift.";

    }


    if (shiftDetails) {

        shiftDetails.textContent =
            message;

    }

}


/* =========================================================
   START SHIFT
========================================================= */

async function startShift() {

    const startButton =
        document.getElementById(
            "startShiftButton"
        );


    if (startButton) {

        startButton.disabled = true;

        startButton.textContent =
            "Starting...";

    }


    const response =
        await staffApiRequest(
            "start_shift.php",
            {
                method: "POST",
                body: JSON.stringify({})
            }
        );


    if (!response) {

        if (startButton) {

            startButton.disabled = false;

            startButton.textContent =
                "Start Shift";

        }

        return;

    }


    let data;


    try {

        data = await response.json();

    }

    catch (error) {

        console.error(
            "Invalid start shift response:",
            error
        );

        showShiftError(
            "Unable to start shift."
        );

        if (startButton) {

            startButton.disabled = false;

            startButton.textContent =
                "Start Shift";

        }

        return;

    }


    if (!data.success) {

        console.error(
            "Start Shift API:",
            data.message
        );

        showShiftError(
            data.message ||
            "Unable to start shift."
        );

        if (startButton) {

            startButton.disabled = false;

            startButton.textContent =
                "Start Shift";

        }

        return;

    }


    displayActiveShift(
        data.shift
    );


    if (startButton) {

        startButton.disabled = false;

        startButton.textContent =
            "Start Shift";

    }

}


/* =========================================================
   END SHIFT MODAL
========================================================= */

function openEndShiftModal() {

    const modal =
        document.getElementById(
            "endShiftModal"
        );

    const confirmButton =
        document.getElementById(
            "confirmEndShiftButton"
        );


    if (!modal) {
        return;
    }


    modal.hidden = false;

    document.body.style.overflow =
        "hidden";


    if (confirmButton) {

        confirmButton.disabled = false;

        confirmButton.textContent =
            "End Shift";

    }

}


/* =========================================================
   CLOSE END SHIFT MODAL
========================================================= */

function closeEndShiftModal() {

    const modal =
        document.getElementById(
            "endShiftModal"
        );


    if (!modal) {
        return;
    }


    modal.hidden = true;

    document.body.style.overflow =
        "";

}


/* =========================================================
   PROCESS END SHIFT
========================================================= */

async function processEndShift() {

    const confirmButton =
        document.getElementById(
            "confirmEndShiftButton"
        );


    if (confirmButton) {

        confirmButton.disabled = true;

        confirmButton.textContent =
            "Ending...";

    }


    const response =
        await staffApiRequest(
            "end_shift.php",
            {
                method: "PUT"
            }
        );


    if (!response) {

        if (confirmButton) {

            confirmButton.disabled = false;

            confirmButton.textContent =
                "End Shift";

        }

        return;

    }


    let data;


    try {

        data = await response.json();

    }

    catch (error) {

        console.error(
            "Invalid end shift response:",
            error
        );

        showShiftError(
            "Unable to end shift."
        );

        if (confirmButton) {

            confirmButton.disabled = false;

            confirmButton.textContent =
                "End Shift";

        }

        return;

    }


    if (!data.success) {

        console.error(
            "End Shift API:",
            data.message
        );

        showShiftError(
            data.message ||
            "Unable to end shift."
        );

        if (confirmButton) {

            confirmButton.disabled = false;

            confirmButton.textContent =
                "End Shift";

        }

        return;

    }


    closeEndShiftModal();


    displayNoActiveShift();


    if (confirmButton) {

        confirmButton.disabled = false;

        confirmButton.textContent =
            "End Shift";

    }

}


/* =========================================================
   END SHIFT
========================================================= */

function endShift() {

    openEndShiftModal();

}


/* =========================================================
   SHIFT TIME
========================================================= */

function formatShiftTime(value) {

    if (!value) {
        return "Unknown time";
    }


    const parsed =
        new Date(
            String(value).replace(" ", "T")
        );


    if (isNaN(parsed.getTime())) {

        return escapeHtml(value);

    }


    return parsed.toLocaleString(
        "en-PH",
        {
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "2-digit"
        }
    );

}


/* =========================================================
   SHIFT EVENTS
========================================================= */

function setupShiftActions() {

    const startButton =
        document.getElementById(
            "startShiftButton"
        );

    const endButton =
        document.getElementById(
            "endShiftButton"
        );

    const cancelButton =
        document.getElementById(
            "cancelEndShiftButton"
        );

    const confirmButton =
        document.getElementById(
            "confirmEndShiftButton"
        );

    const modal =
        document.getElementById(
            "endShiftModal"
        );


    if (startButton) {

        startButton.addEventListener(
            "click",
            startShift
        );

    }


    if (endButton) {

        endButton.addEventListener(
            "click",
            endShift
        );

    }


    if (cancelButton) {

        cancelButton.addEventListener(
            "click",
            closeEndShiftModal
        );

    }


    if (confirmButton) {

        confirmButton.addEventListener(
            "click",
            processEndShift
        );

    }


    /*
     * Clicking the dark overlay closes the modal.
     * Clicking inside the modal does not.
     */

    if (modal) {

        modal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === modal
                ) {

                    closeEndShiftModal();

                }

            }
        );

    }


    /*
     * Escape key closes the modal.
     */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape" &&
                modal &&
                !modal.hidden
            ) {

                closeEndShiftModal();

            }

        }
    );

}


/* =========================================================
   DASHBOARD DATA
========================================================= */

function updateDashboard(orders) {

    /*
     * Only actionable unpaid orders belong
     * in Awaiting Payment.
     */

    const awaitingPayment =
        orders.filter(order => {

            const orderStatus =
                normalizeStatus(
                    order.order_status
                );

            const paymentStatus =
                normalizeStatus(
                    order.payment_status
                );


            return (
                orderStatus ===
                    "AWAITING_PAYMENT"
                &&
                (
                    paymentStatus ===
                        "UNPAID"
                    ||
                    paymentStatus === ""
                    ||
                    paymentStatus === "NULL"
                )
            );

        });


    /*
     * PAID orders are waiting for staff
     * to begin preparation.
     */

    const paid =
        orders.filter(order => {

            const orderStatus =
                normalizeStatus(
                    order.order_status
                );

            const paymentStatus =
                normalizeStatus(
                    order.payment_status
                );


            return (
                orderStatus === "PAID"
                &&
                paymentStatus === "PAID"
            );

        });


    const preparing =
        orders.filter(order => {

            return normalizeStatus(
                order.order_status
            ) === "PREPARING";

        });


    const ready =
        orders.filter(order => {

            return normalizeStatus(
                order.order_status
            ) === "READY";

        });


    const completed =
        orders.filter(order => {

            return normalizeStatus(
                order.order_status
            ) === "COMPLETED";

        });


    /* =====================================================
       STATISTICS
    ===================================================== */

    updateStat(
        "awaitingPaymentCount",
        awaitingPayment.length
    );


    updateStat(
        "preparingCount",
        preparing.length
    );


    updateStat(
        "readyCount",
        ready.length
    );


    updateStat(
        "completedCount",
        completed.length
    );


    /* =====================================================
       QUEUE COUNTS
    ===================================================== */

    updateStat(
        "awaitingPaymentQueueCount",
        awaitingPayment.length
    );


    updateStat(
        "preparingQueueCount",
        preparing.length
    );


    updateStat(
        "readyQueueCount",
        ready.length
    );


    /* =====================================================
       QUEUE DISPLAY
    ===================================================== */

    renderQueue(
        "awaitingPaymentQueue",
        awaitingPayment,
        "No orders awaiting payment."
    );


    renderQueue(
        "preparingQueue",
        preparing,
        "No orders currently preparing."
    );


    renderQueue(
        "readyQueue",
        ready,
        "No orders ready for pickup."
    );

}


/* =========================================================
   NORMALIZE STATUS
========================================================= */

function normalizeStatus(value) {

    if (
        value === null ||
        value === undefined
    ) {

        return "";

    }


    return String(value)
        .trim()
        .toUpperCase();

}


/* =========================================================
   UPDATE STAT
========================================================= */

function updateStat(
    elementId,
    value
) {

    const element =
        document.getElementById(
            elementId
        );


    if (element) {

        element.textContent =
            value;

    }

}


/* =========================================================
   RENDER QUEUE
========================================================= */

function renderQueue(
    elementId,
    orders,
    emptyMessage
) {

    const container =
        document.getElementById(
            elementId
        );


    if (!container) {
        return;
    }


    if (!orders.length) {

        container.className =
            "queue-empty";

        container.textContent =
            emptyMessage;

        return;

    }


    container.className =
        "queue-orders";


    /*
     * Show the newest five orders in each
     * dashboard queue.
     */

    container.innerHTML =
        orders
            .slice(0, 5)
            .map(order =>
                createQueueOrder(order)
            )
            .join("");

}


/* =========================================================
   CREATE QUEUE ORDER CARD
========================================================= */

function createQueueOrder(order) {

    const orderId =
        escapeHtml(
            order.order_id
        );


    const customerName =
        escapeHtml(
            order.customer?.full_name ||
            "Customer"
        );


    const total =
        formatCurrency(
            order.total_amount
        );


    const itemSummary =
        getItemSummary(
            order.items
        );


    const orderTime =
        formatOrderTime(
            order.order_date
        );


    const paymentMethod =
        escapeHtml(
            order.payment_method ||
            order.order_source ||
            ""
        );


    return `
        <article class="order-card">

            <div class="order-card-header">

                <div>

                    <div class="order-number">
                        #${orderId}
                    </div>

                    <div class="order-customer">
                        ${customerName}
                    </div>

                    <div class="order-items">
                        ${itemSummary}
                    </div>

                </div>


                <div class="order-total">
                    ${total}
                </div>

            </div>


            <div class="order-card-footer">

                <div class="order-payment">
                    ${paymentMethod}
                </div>

                <div class="order-time">
                    ${orderTime}
                </div>

            </div>

        </article>
    `;

}


/* =========================================================
   ITEM SUMMARY
========================================================= */

function getItemSummary(items) {

    if (
        !Array.isArray(items) ||
        items.length === 0
    ) {

        return "No items";

    }


    return items
        .map(item => {

            const quantity =
                escapeHtml(
                    item.quantity
                );

            const name =
                escapeHtml(
                    item.product_name ||
                    "Product"
                );


            return `
                <div class="order-item">
                    ${quantity} × ${name}
                </div>
            `;

        })
        .join("");

}


/* =========================================================
   CURRENCY
========================================================= */

function formatCurrency(value) {

    const number =
        Number(value);


    if (isNaN(number)) {

        return "₱0.00";

    }


    return "₱" +
        number.toLocaleString(
            "en-PH",
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        );

}


/* =========================================================
   ORDER TIME
========================================================= */

function formatOrderTime(value) {

    if (!value) {
        return "Unknown time";
    }


    const parsed =
        new Date(
            String(value).replace(" ", "T")
        );


    if (isNaN(parsed.getTime())) {

        return escapeHtml(value);

    }


    return parsed.toLocaleString(
        "en-PH",
        {
            month: "short",
            day: "numeric",
            hour: "numeric",
            minute: "2-digit"
        }
    );

}


/* =========================================================
   HTML ESCAPE
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


/* =========================================================
   DASHBOARD ERROR
========================================================= */

function showDashboardError() {

    const systemStatus =
        document.getElementById(
            "systemStatus"
        );


    if (systemStatus) {

        systemStatus.textContent =
            "Unable to load live order data.";

    }

}


/* =========================================================
   INITIALIZE
========================================================= */

async function initializeDashboard() {

    if (!checkStaffSession()) {
        return;
    }


    displayStaffInfo();

    displayDate();

    setupShiftActions();

    await loadCurrentShift();

    await loadStaffOrders();

}


/* =========================================================
   START
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    initializeDashboard
);