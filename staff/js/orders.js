const API_BASE = "../api/";

/* =========================================================
   STATE
========================================================= */

let allOrders = [];
let currentFilter = "ALL";


/* =========================================================
   SESSION
========================================================= */

function getStaffSession() {

    return {
        token: sessionStorage.getItem("token"),
        role: sessionStorage.getItem("role"),
        fullName: sessionStorage.getItem("full_name"),
        expiresAt: sessionStorage.getItem("token_expiry")
    };

}


function redirectToLogin() {

    sessionStorage.clear();

    window.location.href = "login.php";

}


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
   CUSTOM MODAL SYSTEM
========================================================= */

function createModalStyles() {

    if (
        document.getElementById(
            "kohicebuModalStyles"
        )
    ) {
        return;
    }

    const style =
        document.createElement("style");

    style.id =
        "kohicebuModalStyles";

    style.textContent = `

        /* =====================================================
           KOHICEBU MODAL OVERLAY
        ===================================================== */

        .kohicebu-modal-overlay {

            position: fixed;

            inset: 0;

            z-index: 99999;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 24px;

            background:
                rgba(18, 18, 18, 0.58);

            backdrop-filter:
                blur(4px);

            -webkit-backdrop-filter:
                blur(4px);

            opacity: 0;

            visibility: hidden;

            transition:
                opacity 0.18s ease,
                visibility 0.18s ease;

        }


        .kohicebu-modal-overlay.show {

            opacity: 1;

            visibility: visible;

        }


        /* =====================================================
           MODAL
        ===================================================== */

        .kohicebu-modal {

            width: 100%;

            max-width: 460px;

            background: #ffffff;

            border-radius: 18px;

            box-shadow:
                0 20px 60px rgba(0, 0, 0, 0.25);

            overflow: hidden;

            transform:
                translateY(12px)
                scale(0.97);

            transition:
                transform 0.18s ease;

        }


        .kohicebu-modal-overlay.show
        .kohicebu-modal {

            transform:
                translateY(0)
                scale(1);

        }


        /* =====================================================
           MODAL HEADER
        ===================================================== */

        .kohicebu-modal-header {

            display: flex;

            align-items: center;

            gap: 14px;

            padding: 22px 24px 14px;

        }


        .kohicebu-modal-icon {

            width: 42px;

            height: 42px;

            min-width: 42px;

            display: flex;

            align-items: center;

            justify-content: center;

            border-radius: 50%;

            font-size: 20px;

            font-weight: 700;

        }


        .kohicebu-modal-icon.confirm {

            background: #fff3df;

            color: #b87500;

        }


        .kohicebu-modal-icon.success {

            background: #e9f7e5;

            color: #3d7a2f;

        }


        .kohicebu-modal-icon.error {

            background: #fdeaea;

            color: #b3261e;

        }


        .kohicebu-modal-icon.warning {

            background: #fff3df;

            color: #b87500;

        }


        .kohicebu-modal-icon.info {

            background: #edf4ff;

            color: #315f9b;

        }


        .kohicebu-modal-title {

            margin: 0;

            color: #171717;

            font-size: 19px;

            font-weight: 700;

            line-height: 1.3;

        }


        /* =====================================================
           MODAL BODY
        ===================================================== */

        .kohicebu-modal-body {

            padding:
                4px 24px 22px;

        }


        .kohicebu-modal-message {

            margin: 0;

            color: #555555;

            font-size: 14px;

            line-height: 1.65;

            white-space: pre-line;

        }


        /* =====================================================
           MODAL FOOTER
        ===================================================== */

        .kohicebu-modal-footer {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            padding:
                16px 24px 22px;

            border-top:
                1px solid #eeeeee;

        }


        .kohicebu-modal-button {

            min-height: 42px;

            padding:
                0 18px;

            border: none;

            border-radius: 10px;

            font-family: inherit;

            font-size: 13px;

            font-weight: 700;

            cursor: pointer;

            transition:
                transform 0.12s ease,
                opacity 0.12s ease,
                background 0.12s ease;

        }


        .kohicebu-modal-button:hover {

            opacity: 0.9;

        }


        .kohicebu-modal-button:active {

            transform:
                scale(0.97);

        }


        .kohicebu-modal-button.cancel {

            background: #f1f1f1;

            color: #444444;

        }


        .kohicebu-modal-button.primary {

            background: #d4a373;

            color: #ffffff;

        }


        .kohicebu-modal-button.success {

            background: #4f8f3f;

            color: #ffffff;

        }


        .kohicebu-modal-button.danger {

            background: #c62828;

            color: #ffffff;

        }


        .kohicebu-modal-button.warning {

            background: #b87500;

            color: #ffffff;

        }


        /* =====================================================
           VIEW ORDER DETAILS
        ===================================================== */

        .kohicebu-order-details {

            display: flex;

            flex-direction: column;

            gap: 14px;

        }


        .kohicebu-detail-row {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding-bottom: 10px;

            border-bottom:
                1px solid #eeeeee;

        }


        .kohicebu-detail-label {

            color: #777777;

            font-size: 12px;

            font-weight: 600;

        }


        .kohicebu-detail-value {

            color: #222222;

            font-size: 13px;

            font-weight: 700;

            text-align: right;

        }


        .kohicebu-detail-items {

            margin-top: 4px;

            padding: 14px;

            background: #f8f9fa;

            border-radius: 12px;

        }


        .kohicebu-detail-item {

            display: flex;

            justify-content: space-between;

            gap: 12px;

            padding: 8px 0;

            border-bottom:
                1px solid #e7e7e7;

            font-size: 13px;

        }


        .kohicebu-detail-item:last-child {

            border-bottom: none;

        }


        .kohicebu-detail-item-name {

            color: #333333;

        }


        .kohicebu-detail-item-qty {

            color: #777777;

            white-space: nowrap;

        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 520px) {

            .kohicebu-modal-overlay {

                padding: 14px;

            }


            .kohicebu-modal {

                max-width: 100%;

                border-radius: 16px;

            }


            .kohicebu-modal-footer {

                flex-direction: column-reverse;

            }


            .kohicebu-modal-button {

                width: 100%;

            }

        }

    `;

    document.head.appendChild(style);

}


/* =========================================================
   MODAL HELPERS
========================================================= */

function getModalIcon(type) {

    switch (type) {

        case "success":
            return "✓";

        case "error":
            return "×";

        case "warning":
            return "!";

        case "info":
            return "i";

        case "confirm":
        default:
            return "?";

    }

}


function showConfirmModal(
    title,
    message,
    confirmText = "Confirm",
    cancelText = "Cancel",
    type = "confirm"
) {

    return new Promise(resolve => {

        createModalStyles();

        const existing =
            document.getElementById(
                "kohicebuModalOverlay"
            );

        if (existing) {
            existing.remove();
        }


        const overlay =
            document.createElement("div");

        overlay.id =
            "kohicebuModalOverlay";

        overlay.className =
            "kohicebu-modal-overlay";


        overlay.innerHTML = `

            <div
                class="kohicebu-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="kohicebu-modal-header">

                    <div
                        class="kohicebu-modal-icon ${type}"
                    >
                        ${getModalIcon(type)}
                    </div>

                    <h3 class="kohicebu-modal-title">
                        ${escapeHtml(title)}
                    </h3>

                </div>


                <div class="kohicebu-modal-body">

                    <p class="kohicebu-modal-message">
                        ${escapeHtml(message)}
                    </p>

                </div>


                <div class="kohicebu-modal-footer">

                    <button
                        type="button"
                        class="kohicebu-modal-button cancel"
                        id="kohicebuModalCancel"
                    >
                        ${escapeHtml(cancelText)}
                    </button>


                    <button
                        type="button"
                        class="kohicebu-modal-button ${
                            type === "warning"
                                ? "warning"
                                : "primary"
                        }"
                        id="kohicebuModalConfirm"
                    >
                        ${escapeHtml(confirmText)}
                    </button>

                </div>

            </div>

        `;


        document.body.appendChild(
            overlay
        );


        const cancelButton =
            document.getElementById(
                "kohicebuModalCancel"
            );

        const confirmButton =
            document.getElementById(
                "kohicebuModalConfirm"
            );


        function close(result) {

            overlay.classList.remove(
                "show"
            );

            setTimeout(
                () => {

                    if (overlay.parentNode) {
                        overlay.remove();
                    }

                },
                180
            );

            resolve(result);

        }


        cancelButton.addEventListener(
            "click",
            () => close(false)
        );


        confirmButton.addEventListener(
            "click",
            () => close(true)
        );


        overlay.addEventListener(
            "click",
            event => {

                if (
                    event.target ===
                    overlay
                ) {
                    close(false);
                }

            }
        );


        document.addEventListener(
            "keydown",
            function escapeHandler(event) {

                if (
                    event.key === "Escape"
                ) {

                    document.removeEventListener(
                        "keydown",
                        escapeHandler
                    );

                    close(false);

                }

            }
        );


        requestAnimationFrame(
            () => {

                overlay.classList.add(
                    "show"
                );

            }
        );

    });

}


function showInfoModal(
    title,
    message,
    buttonText = "OK",
    type = "info"
) {

    return new Promise(resolve => {

        createModalStyles();

        const existing =
            document.getElementById(
                "kohicebuModalOverlay"
            );

        if (existing) {
            existing.remove();
        }


        const overlay =
            document.createElement("div");

        overlay.id =
            "kohicebuModalOverlay";

        overlay.className =
            "kohicebu-modal-overlay";


        overlay.innerHTML = `

            <div
                class="kohicebu-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="kohicebu-modal-header">

                    <div
                        class="kohicebu-modal-icon ${type}"
                    >
                        ${getModalIcon(type)}
                    </div>

                    <h3 class="kohicebu-modal-title">
                        ${escapeHtml(title)}
                    </h3>

                </div>


                <div class="kohicebu-modal-body">

                    <p class="kohicebu-modal-message">
                        ${escapeHtml(message)}
                    </p>

                </div>


                <div class="kohicebu-modal-footer">

                    <button
                        type="button"
                        class="kohicebu-modal-button primary"
                        id="kohicebuModalOk"
                    >
                        ${escapeHtml(buttonText)}
                    </button>

                </div>

            </div>

        `;


        document.body.appendChild(
            overlay
        );


        const okButton =
            document.getElementById(
                "kohicebuModalOk"
            );


        function close() {

            overlay.classList.remove(
                "show"
            );

            setTimeout(
                () => {

                    if (overlay.parentNode) {
                        overlay.remove();
                    }

                },
                180
            );

            resolve();

        }


        okButton.addEventListener(
            "click",
            close
        );


        overlay.addEventListener(
            "click",
            event => {

                if (
                    event.target ===
                    overlay
                ) {
                    close();
                }

            }
        );


        requestAnimationFrame(
            () => {

                overlay.classList.add(
                    "show"
                );

            }
        );

    });

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
        ...(options.headers || {})
    };

    if (!headers["Content-Type"]) {

        headers["Content-Type"] =
            "application/json";

    }

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

            await showInfoModal(
                "Permission Denied",
                "You do not have permission to perform this action.",
                "OK",
                "error"
            );

            return null;

        }

        return response;

    }

    catch (error) {

        console.error(
            "API connection error:",
            error
        );

        await showInfoModal(
            "Connection Error",
            "Unable to connect to the server. Please check your connection and try again.",
            "OK",
            "error"
        );

        showSystemStatus(
            "Unable to connect to the server."
        );

        return null;

    }

}


/* =========================================================
   LOAD ORDERS
========================================================= */

async function loadOrders() {

    showSystemStatus(
        "Loading live order data..."
    );

    const container =
        document.getElementById(
            "ordersGrid"
        );

    if (container) {

        container.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading order queue...</p>
            </div>
        `;

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

    console.log(
        "Staff orders API:",
        data
    );

    if (!data.success) {

        console.error(
            data.message
        );

        showDashboardError();

        return;

    }

    allOrders =
        Array.isArray(data.orders)
            ? data.orders
            : [];

    updateSummary();

    renderOrders();

    showSystemStatus(
        "Live order data connected."
    );

}


/* =========================================================
   SUMMARY
========================================================= */

function updateSummary() {

    const awaitingPayment =
        allOrders.filter(order => {

            return (
                normalizeStatus(
                    order.order_status
                ) === "AWAITING_PAYMENT"

                &&

                isActionableUnpaidOrder(
                    order
                )

            );

        });


    const preparing =
        allOrders.filter(order => {

            return normalizeStatus(
                order.order_status
            ) === "PREPARING";

        });


    const ready =
        allOrders.filter(order => {

            return normalizeStatus(
                order.order_status
            ) === "READY";

        });


    const active =
        allOrders.filter(order => {

            const status =
                normalizeStatus(
                    order.order_status
                );

            return (
                status === "AWAITING_PAYMENT" ||
                status === "PAID" ||
                status === "PREPARING" ||
                status === "READY"
            );

        });


    setText(
        "activeOrdersCount",
        active.length
    );

    setText(
        "awaitingPaymentCount",
        awaitingPayment.length
    );

    setText(
        "preparingCount",
        preparing.length
    );

    setText(
        "readyCount",
        ready.length
    );

}


/* =========================================================
   FILTERS
========================================================= */

function setupFilters() {

    const buttons =
        document.querySelectorAll(
            ".filter-button, .filter-tab"
        );

    buttons.forEach(button => {

        button.addEventListener(
            "click",
            function () {

                buttons.forEach(item => {

                    item.classList.remove(
                        "active"
                    );

                });

                this.classList.add(
                    "active"
                );

                currentFilter =
                    normalizeStatus(
                        this.dataset.filter
                    );

                renderOrders();

            }
        );

    });

}


/* =========================================================
   FILTER ORDERS
========================================================= */

function getFilteredOrders() {

    if (currentFilter === "ALL") {

        return allOrders.filter(
            order =>
                !isHistoricalPaymentFailure(
                    order
                )
        );

    }

    return allOrders.filter(order => {

        const status =
            normalizeStatus(
                order.order_status
            );

        if (
            currentFilter ===
            "AWAITING_PAYMENT"
        ) {

            return (
                status ===
                    "AWAITING_PAYMENT"

                &&

                isActionableUnpaidOrder(
                    order
                )

            );

        }

        return status === currentFilter;

    });

}


/* =========================================================
   RENDER ORDERS
========================================================= */

function renderOrders() {

    const container =
        document.getElementById(
            "ordersGrid"
        );

    const resultText =
        document.getElementById(
            "orderCount"
        );

    if (!container) {

        console.error(
            "ordersGrid element was not found."
        );

        return;

    }

    const orders =
        getFilteredOrders();


    if (resultText) {

        resultText.textContent =
            `${orders.length} order${
                orders.length === 1
                    ? ""
                    : "s"
            }`;

    }


    if (!orders.length) {

        container.innerHTML = `
            <div class="empty-state">

                <div class="empty-state-title">
                    No orders found
                </div>

                <div class="empty-state-description">
                    There are currently no orders
                    matching this status.
                </div>

            </div>
        `;

        return;

    }


    container.innerHTML =
        orders
            .map(order =>
                createOrderCard(order)
            )
            .join("");

}


/* =========================================================
   CREATE ORDER CARD
========================================================= */

function createOrderCard(order) {

    const status =
        normalizeStatus(
            order.order_status
        );


    const paymentStatus =
        normalizeStatus(
            order.payment_status
        );


    const orderId =
        Number(order.order_id);


    const customerName =
        escapeHtml(
            order.customer?.full_name ||
            "Customer"
        );
        
    const cashierName =
        escapeHtml(
            order.cashier?.full_name ||
             ""
        );

    const shiftId =
    order.shift_id !== null &&
    order.shift_id !== undefined
        ? Number(order.shift_id)
        : null;
    


    const total =
        formatCurrency(
            order.total_amount
        );


    const items =
        createItemsHtml(
            order.items
        );


    const orderTime =
        formatOrderTime(
            order.order_date
        );


    const statusClass =
        getStatusClass(
            status
        );


    const paymentLabel =
        paymentStatus ||
        "NO PAYMENT DATA";


    const source =
        formatSource(
            order.order_source
        );


    const paymentMethod =
        formatPaymentMethod(
            order.payment_method
        );


    return `
        <article
            class="order-card"
            data-order-id="${orderId}"
        >

            <div class="order-header">

                <div>

                    <div class="order-number">
                        ORDER #${orderId}
                    </div>

                    <div class="customer-name">
                        ${customerName}
                    </div>

                </div>


                <div class="order-total">
                    ${total}
                </div>

            </div>


            <div class="order-items">

                ${items}

            </div>


            <div class="order-meta">

                <span
                    class="status-badge ${statusClass}"
                >
                    ${formatStatus(status)}
                </span>


                <span class="meta-badge">
                    ${paymentLabel}
                </span>
                ${
                 cashierName
                    ? `
                 <span class="meta-badge">
                Cashier: ${cashierName}
                   </span>
                     `
                   : ""
               }

               ${
               shiftId !== null
            ? `
            <span class="meta-badge">
                Shift: #${shiftId}
            </span>
           `
        : ""
}


                ${
                    paymentMethod
                        ? `
                            <span class="meta-badge">
                                ${paymentMethod}
                            </span>
                        `
                        : ""
                }


                ${
                    source
                        ? `
                            <span class="meta-badge">
                                ${source}
                            </span>
                        `
                        : ""
                }

            </div>


            <div class="order-time">
                ${orderTime}
            </div>


            <div class="order-actions">

                <button
                    type="button"
                    class="order-action order-action-secondary"
                    onclick="viewOrder(${orderId})"
                >
                    View Order
                </button>

                ${createActionButton(order)}

            </div>

        </article>
    `;

}


/* =========================================================
   ORDER ITEMS
========================================================= */

function createItemsHtml(items) {

    if (
        !Array.isArray(items) ||
        items.length === 0
    ) {

        return `
            <div class="order-item">

                <span class="order-item-name">
                    No items
                </span>

            </div>
        `;

    }


    return items
        .map(item => {

            const name =
                escapeHtml(
                    item.product_name ||
                    "Product"
                );


            const quantity =
                Number(item.quantity) || 0;


            const unitPrice =
                formatCurrency(
                    item.unit_price
                );


            return `
                <div class="order-item">

                    <span class="order-item-name">
                        ${name}
                    </span>

                    <span class="order-item-quantity">
                        × ${quantity}
                        &nbsp; ${unitPrice}
                    </span>

                </div>
            `;

        })
        .join("");

}


/* =========================================================
   ACTION BUTTON
========================================================= */

/* =========================================================
   ACTION BUTTON
========================================================= */

function createActionButton(order) {

    const status =
        normalizeStatus(
            order.order_status
        );


    const paymentStatus =
        normalizeStatus(
            order.payment_status
        );


    const paymentMethod =
        normalizeStatus(
            order.payment_method
        );


    const orderId =
        Number(order.order_id);


    /*
     * AWAITING PAYMENT / UNPAID
     *
     * Staff can:
     * - Edit the order
     * - Cancel the order
     * - Confirm cash payment
     */

    if (
        status === "AWAITING_PAYMENT"
        &&
        (
            paymentStatus === "UNPAID" ||
            paymentStatus === "" ||
            paymentStatus === "NULL"
        )
        &&
        paymentMethod === "CASHIER"
    ) {

        return `
            <button
                type="button"
                class="order-action order-action-secondary"
                onclick="showEditOrderModal(${orderId})"
            >
                Edit Order
            </button>

            <button
                type="button"
                class="order-action order-action-danger"
                onclick="showCancellationReasonModal(${orderId})"
            >
                Cancel Order
            </button>

            <button
                type="button"
                class="order-action order-action-primary"
                onclick="confirmCashPayment(${orderId})"
            >
                Confirm Payment
            </button>
        `;

    }


    /*
     * PAID
     */

    if (
        status === "PAID"
        &&
        paymentStatus === "PAID"
    ) {

        return `
            <button
                type="button"
                class="order-action order-action-primary"
                onclick="startPreparing(${orderId})"
            >
                Start Preparing
            </button>
        `;

    }


    /*
     * PREPARING
     */

    if (
        status === "PREPARING"
    ) {

        return `
            <button
                type="button"
                class="order-action order-action-primary"
                onclick="markReady(${orderId})"
            >
                Mark Ready
            </button>
        `;

    }


    /*
     * READY
     */

    if (
        status === "READY"
    ) {

        return `
            <button
                type="button"
                class="order-action order-action-primary"
                onclick="markCompleted(${orderId})"
            >
                Complete
            </button>
        `;

    }


    return "";

}

/* =========================================================
   CANCEL ORDER
========================================================= */

async function cancelOrder(orderId) {

    const order =
        allOrders.find(
            item =>
                Number(item.order_id) ===
                Number(orderId)
        );


    if (!order) {

        await showInfoModal(
            "Order Not Found",
            "The order information could not be found.",
            "OK",
            "error"
        );

        return;

    }


    const status =
        normalizeStatus(
            order.order_status
        );


    const paymentStatus =
        normalizeStatus(
            order.payment_status
        );


    /*
     * UI-SIDE VALIDATION
     *
     * The backend performs the authoritative validation.
     */

    const cancellableStatuses = [
        "AWAITING_PAYMENT",
        "PENDING",
        "CONFIRMED"
    ];


    if (
        !cancellableStatuses.includes(status)
        ||
        !(
            paymentStatus === "UNPAID" ||
            paymentStatus === "" ||
            paymentStatus === "NULL"
        )
    ) {

        await showInfoModal(
            "Cancellation Not Allowed",
            `Order #${orderId} cannot be cancelled from its current payment or order status.`,
            "OK",
            "error"
        );

        return;

    }


    /*
     * SHOW REASON MODAL
     */

    const reason =
        await showCancellationReasonModal(
            orderId
        );


    if (reason === null) {
        return;
    }


    const trimmedReason =
        reason.trim();


    if (trimmedReason === "") {

        await showInfoModal(
            "Reason Required",
            "Please provide a reason for cancelling the order.",
            "OK",
            "warning"
        );

        return;

    }


    showSystemStatus(
        `Cancelling Order #${orderId}...`
    );


    /*
     * SEND CANCELLATION REQUEST
     */

    const response =
        await staffApiRequest(
            "cancel_order.php",
            {
                method: "POST",

                body: JSON.stringify({
                    order_id: orderId,
                    reason: trimmedReason
                })
            }
        );


    if (!response) {
        return;
    }


    /*
     * READ RESPONSE
     */

    let data;

    try {

        data =
            await response.json();

    }

    catch (error) {

        console.error(
            "Invalid cancellation response:",
            error
        );


        await showInfoModal(
            "Cancellation Error",
            "The server returned an invalid response. Please try again.",
            "OK",
            "error"
        );


        showSystemStatus(
            "Invalid response from cancellation server."
        );

        return;

    }


    /*
     * SERVER REJECTED CANCELLATION
     */

    if (!data.success) {

        await showInfoModal(
            "Cancellation Failed",
            data.message ||
                `Unable to cancel Order #${orderId}.`,
            "OK",
            "error"
        );


        showSystemStatus(
            "Order cancellation failed."
        );

        return;

    }


    /*
     * SUCCESS
     */

    await showInfoModal(
        "Order Cancelled",
        `Order #${orderId} has been cancelled successfully.`,
        "OK",
        "success"
    );


    /*
     * REFRESH LIVE ORDER DATA
     */

    await loadOrders();

}


/* =========================================================
   CANCELLATION REASON MODAL
========================================================= */

function showCancellationReasonModal(orderId) {

    return new Promise(resolve => {

        createModalStyles();


        const existing =
            document.getElementById(
                "kohicebuModalOverlay"
            );


        if (existing) {
            existing.remove();
        }


        const overlay =
            document.createElement("div");


        overlay.id =
            "kohicebuModalOverlay";


        overlay.className =
            "kohicebu-modal-overlay";


        overlay.innerHTML = `

            <div
                class="kohicebu-modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="kohicebuCancelTitle"
            >

                <div class="kohicebu-modal-header">

                    <div
                        class="kohicebu-modal-icon warning"
                    >
                        !
                    </div>


                    <h3
                        class="kohicebu-modal-title"
                        id="kohicebuCancelTitle"
                    >
                        Cancel Order #${Number(orderId)}
                    </h3>

                </div>


                <div class="kohicebu-modal-body">

                    <p
                        class="kohicebu-modal-message"
                    >
                        Please provide a reason for cancelling this order.
                        This reason will be recorded in the order audit log.
                    </p>


                    <div
                        style="
                            margin-top: 16px;
                        "
                    >

                        <label
                            for="kohicebuCancellationReason"
                            style="
                                display: block;
                                margin-bottom: 7px;
                                color: #555555;
                                font-size: 12px;
                                font-weight: 700;
                            "
                        >
                            Cancellation Reason
                        </label>


                        <textarea
                            id="kohicebuCancellationReason"
                            maxlength="1000"
                            rows="4"
                            placeholder="Example: Customer requested cancellation"
                            style="
                                width: 100%;
                                box-sizing: border-box;
                                padding: 12px;
                                border: 1px solid #d8d8d8;
                                border-radius: 10px;
                                background: #ffffff;
                                color: #222222;
                                font-family: inherit;
                                font-size: 13px;
                                line-height: 1.5;
                                resize: vertical;
                                outline: none;
                            "
                        ></textarea>


                        <div
                            style="
                                margin-top: 6px;
                                color: #888888;
                                font-size: 11px;
                            "
                        >
                            Maximum 1000 characters.
                        </div>

                    </div>

                </div>


                <div class="kohicebu-modal-footer">

                    <button
                        type="button"
                        class="kohicebu-modal-button cancel"
                        id="kohicebuCancellationCancel"
                    >
                        Keep Order
                    </button>


                    <button
                        type="button"
                        class="kohicebu-modal-button danger"
                        id="kohicebuCancellationConfirm"
                    >
                        Cancel Order
                    </button>

                </div>

            </div>

        `;


        document.body.appendChild(
            overlay
        );


        const reasonInput =
            document.getElementById(
                "kohicebuCancellationReason"
            );


        const cancelButton =
            document.getElementById(
                "kohicebuCancellationCancel"
            );


        const confirmButton =
            document.getElementById(
                "kohicebuCancellationConfirm"
            );


        let resolved = false;


        function close(value) {

            if (resolved) {
                return;
            }


            resolved = true;


            overlay.classList.remove(
                "show"
            );


            setTimeout(
                () => {

                    if (
                        overlay.parentNode
                    ) {

                        overlay.remove();

                    }

                },
                180
            );


            resolve(value);

        }


        cancelButton.addEventListener(
            "click",
            () => {

                close(null);

            }
        );


        confirmButton.addEventListener(
            "click",
            () => {

                const reason =
                    reasonInput
                        ? reasonInput.value
                        : "";

                if (
                    reason.trim() === ""
                ) {

                    reasonInput.focus();

                    reasonInput.style.borderColor =
                        "#c62828";

                    return;

                }


                close(reason);

            }
        );


        overlay.addEventListener(
            "click",
            event => {

                if (
                    event.target ===
                    overlay
                ) {

                    close(null);

                }

            }
        );


        function escapeHandler(event) {

            if (
                event.key === "Escape"
            ) {

                document.removeEventListener(
                    "keydown",
                    escapeHandler
                );


                close(null);

            }

        }


        document.addEventListener(
            "keydown",
            escapeHandler
        );


        /*
         * Remove the keydown listener when the modal
         * is closed through the buttons/background.
         */

        const originalClose =
            close;


        requestAnimationFrame(
            () => {

                overlay.classList.add(
                    "show"
                );


                if (reasonInput) {

                    reasonInput.focus();

                }

            }
        );

    });

}


/* =========================================================
   EDIT ORDER
========================================================= */

async function showEditOrderModal(orderId) {

    const order =
        allOrders.find(
            item =>
                Number(item.order_id) ===
                Number(orderId)
        );

    if (!order) {

        await showInfoModal(
            "Order Not Found",
            "The order information could not be found.",
            "OK",
            "error"
        );

        return;
    }


    const status =
        normalizeStatus(
            order.order_status
        );

    const paymentStatus =
        normalizeStatus(
            order.payment_status
        );


    /*
     * Front-end safety check.
     * The backend performs the authoritative validation.
     */

    if (
        status !== "AWAITING_PAYMENT" ||
        !(
            paymentStatus === "UNPAID" ||
            paymentStatus === "" ||
            paymentStatus === "NULL"
        )
    ) {

        await showInfoModal(
            "Order Cannot Be Edited",
            "Only unpaid orders awaiting payment can be edited.",
            "OK",
            "warning"
        );

        return;
    }


    const items =
        Array.isArray(order.items)
            ? order.items
            : [];


    if (!items.length) {

        await showInfoModal(
            "No Order Items",
            "This order does not contain any editable items.",
            "OK",
            "error"
        );

        return;
    }


    createModalStyles();


    const existing =
        document.getElementById(
            "kohicebuModalOverlay"
        );

    if (existing) {
        existing.remove();
    }


    const overlay =
        document.createElement("div");

    overlay.id =
        "kohicebuModalOverlay";

    overlay.className =
        "kohicebu-modal-overlay";


    const itemHtml =
        items
            .map((item, index) => {

                const productId =
                    Number(item.product_id);

                const productName =
                    escapeHtml(
                        item.product_name ||
                        "Product"
                    );

                const quantity =
                    Math.max(
                        1,
                        Number(item.quantity) || 1
                    );

                const unitPrice =
                    formatCurrency(
                        item.unit_price
                    );

                return `
                    <div
                        class="edit-order-item"
                        data-product-id="${productId}"
                        style="
                            padding: 14px 0;
                            border-bottom: 1px solid #eeeeee;
                        "
                    >

                        <div
                            style="
                                display: flex;
                                justify-content: space-between;
                                gap: 12px;
                                margin-bottom: 10px;
                            "
                        >

                            <div>
                                <div
                                    style="
                                        color: #222;
                                        font-size: 14px;
                                        font-weight: 700;
                                    "
                                >
                                    ${productName}
                                </div>

                                <div
                                    style="
                                        margin-top: 3px;
                                        color: #888;
                                        font-size: 12px;
                                    "
                                >
                                    ${unitPrice} each
                                </div>
                            </div>

                        </div>


                        <div
                            style="
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                gap: 12px;
                            "
                        >

                            <span
                                style="
                                    color: #777;
                                    font-size: 12px;
                                    font-weight: 600;
                                "
                            >
                                Quantity
                            </span>


                            <div
                                style="
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
                                "
                            >

                                <button
                                    type="button"
                                    class="edit-quantity-button"
                                    data-action="decrease"
                                    data-index="${index}"
                                    style="
                                        width: 34px;
                                        height: 34px;
                                        border: 1px solid #dddddd;
                                        border-radius: 9px;
                                        background: #f7f7f7;
                                        color: #333;
                                        font-size: 18px;
                                        font-weight: 700;
                                        cursor: pointer;
                                    "
                                >
                                    −
                                </button>


                                <input
                                    type="number"
                                    class="edit-quantity-input"
                                    data-index="${index}"
                                    min="1"
                                    max="1000"
                                    value="${quantity}"
                                    style="
                                        width: 64px;
                                        height: 34px;
                                        padding: 0 8px;
                                        border: 1px solid #dddddd;
                                        border-radius: 9px;
                                        text-align: center;
                                        font-family: inherit;
                                        font-size: 13px;
                                        font-weight: 700;
                                        box-sizing: border-box;
                                    "
                                />


                                <button
                                    type="button"
                                    class="edit-quantity-button"
                                    data-action="increase"
                                    data-index="${index}"
                                    style="
                                        width: 34px;
                                        height: 34px;
                                        border: 1px solid #dddddd;
                                        border-radius: 9px;
                                        background: #f7f7f7;
                                        color: #333;
                                        font-size: 18px;
                                        font-weight: 700;
                                        cursor: pointer;
                                    "
                                >
                                    +
                                </button>

                            </div>

                        </div>

                    </div>
                `;

            })
            .join("");


    overlay.innerHTML = `

        <div
            class="kohicebu-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="editOrderModalTitle"
        >

            <div class="kohicebu-modal-header">

                <div
                    class="kohicebu-modal-icon info"
                >
                    ✎
                </div>

                <h3
                    class="kohicebu-modal-title"
                    id="editOrderModalTitle"
                >
                    Edit Order #${Number(order.order_id)}
                </h3>

            </div>


            <div class="kohicebu-modal-body">

                <div
                    style="
                        max-height: 300px;
                        overflow-y: auto;
                        padding-right: 4px;
                    "
                    id="editOrderItems"
                >
                    ${itemHtml}
                </div>


                <div
                    style="
                        margin-top: 18px;
                    "
                >

                    <label
                        for="editOrderReason"
                        style="
                            display: block;
                            margin-bottom: 7px;
                            color: #555;
                            font-size: 12px;
                            font-weight: 700;
                        "
                    >
                        Reason for Edit
                    </label>


                    <textarea
                        id="editOrderReason"
                        maxlength="1000"
                        rows="4"
                        placeholder="Enter the reason for changing this order..."
                        style="
                            width: 100%;
                            min-height: 96px;
                            padding: 11px 12px;
                            border: 1px solid #dddddd;
                            border-radius: 10px;
                            resize: vertical;
                            box-sizing: border-box;
                            font-family: inherit;
                            font-size: 13px;
                            line-height: 1.5;
                            outline: none;
                        "
                    ></textarea>


                    <div
                        style="
                            margin-top: 5px;
                            color: #999;
                            font-size: 11px;
                        "
                    >
                        A reason is required for accountability.
                    </div>

                </div>

            </div>


            <div class="kohicebu-modal-footer">

                <button
                    type="button"
                    class="kohicebu-modal-button cancel"
                    id="editOrderCancelButton"
                >
                    Cancel
                </button>


                <button
                    type="button"
                    class="kohicebu-modal-button primary"
                    id="editOrderSaveButton"
                >
                    Save Changes
                </button>

            </div>

        </div>

    `;


    document.body.appendChild(
        overlay
    );


    const cancelButton =
        document.getElementById(
            "editOrderCancelButton"
        );

    const saveButton =
        document.getElementById(
            "editOrderSaveButton"
        );


    function close() {

        overlay.classList.remove(
            "show"
        );

        setTimeout(
            () => {

                if (overlay.parentNode) {
                    overlay.remove();
                }

            },
            180
        );

    }


    cancelButton.addEventListener(
        "click",
        close
    );


    overlay.addEventListener(
        "click",
        event => {

            if (
                event.target ===
                overlay
            ) {
                close();
            }

        }
    );


    /*
     * Quantity buttons
     */

    overlay
        .querySelectorAll(
            ".edit-quantity-button"
        )
        .forEach(button => {

            button.addEventListener(
                "click",
                () => {

                    const index =
                        Number(
                            button.dataset.index
                        );

                    const action =
                        button.dataset.action;

                    const input =
                        overlay.querySelector(
                            `.edit-quantity-input[data-index="${index}"]`
                        );

                    if (!input) {
                        return;
                    }

                    let quantity =
                        Number(
                            input.value
                        ) || 1;


                    if (
                        action ===
                        "increase"
                    ) {

                        quantity =
                            Math.min(
                                1000,
                                quantity + 1
                            );

                    }

                    else if (
                        action ===
                        "decrease"
                    ) {

                        quantity =
                            Math.max(
                                1,
                                quantity - 1
                            );

                    }


                    input.value =
                        quantity;

                }
            );

        });


    /*
     * Save
     */

    saveButton.addEventListener(
        "click",
        async () => {

            const reasonInput =
                document.getElementById(
                    "editOrderReason"
                );

            const reason =
                reasonInput
                    ? reasonInput.value.trim()
                    : "";


            if (!reason) {

                await showInfoModal(
                    "Reason Required",
                    "Please enter a reason for editing this order.",
                    "OK",
                    "warning"
                );

                if (reasonInput) {
                    reasonInput.focus();
                }

                return;
            }


            if (reason.length > 1000) {

                await showInfoModal(
                    "Reason Too Long",
                    "The edit reason cannot exceed 1000 characters.",
                    "OK",
                    "warning"
                );

                return;
            }


            const quantityInputs =
                overlay.querySelectorAll(
                    ".edit-quantity-input"
                );


            const editedItems = [];


            quantityInputs.forEach(
                (input, index) => {

                    const item =
                        items[index];

                    if (!item) {
                        return;
                    }


                    let quantity =
                        Number(
                            input.value
                        );


                    if (
                        !Number.isInteger(
                            quantity
                        ) ||
                        quantity < 1
                    ) {

                        quantity = 1;

                    }


                    quantity =
                        Math.min(
                            1000,
                            quantity
                        );


                    editedItems.push({
                        product_id:
                            Number(
                                item.product_id
                            ),
                        quantity:
                            quantity
                    });

                }
            );


            if (!editedItems.length) {

                await showInfoModal(
                    "Invalid Order",
                    "At least one order item is required.",
                    "OK",
                    "error"
                );

                return;
            }


            const confirmed =
                await showConfirmModal(
                    "Save Order Changes",
                    `Save the changes made to Order #${Number(order.order_id)}?`,
                    "Save Changes",
                    "Cancel",
                    "confirm"
                );


            if (!confirmed) {
                return;
            }


            saveButton.disabled = true;
            saveButton.textContent =
                "Saving...";


            showSystemStatus(
                `Updating Order #${Number(order.order_id)}...`
            );


            const response =
                await staffApiRequest(
                    "edit_order.php",
                    {
                        method: "POST",
                        body:
                            JSON.stringify({
                                order_id:
                                    Number(
                                        order.order_id
                                    ),
                                items:
                                    editedItems,
                                reason:
                                    reason
                            })
                    }
                );


            if (!response) {

                saveButton.disabled = false;
                saveButton.textContent =
                    "Save Changes";

                return;
            }


            let data;

            try {

                data =
                    await response.json();

            }

            catch (error) {

                console.error(
                    "Invalid edit response:",
                    error
                );

                saveButton.disabled = false;
                saveButton.textContent =
                    "Save Changes";

                await showInfoModal(
                    "Edit Error",
                    "The server returned an invalid response. Please try again.",
                    "OK",
                    "error"
                );

                return;
            }


            if (!data.success) {

                saveButton.disabled = false;
                saveButton.textContent =
                    "Save Changes";

                await showInfoModal(
                    "Order Edit Failed",
                    data.message ||
                    "Unable to edit the order.",
                    "OK",
                    "error"
                );

                return;
            }


            close();


            await showInfoModal(
                "Order Updated",
                `Order #${Number(order.order_id)} has been updated successfully.`,
                "OK",
                "success"
            );


            await loadOrders();

        }
    );


    requestAnimationFrame(
        () => {

            overlay.classList.add(
                "show"
            );

        }
    );

}


/* =========================================================
   CANCEL ORDER
========================================================= */

async function showCancellationReasonModal(orderId) {

    const order =
        allOrders.find(
            item =>
                Number(item.order_id) ===
                Number(orderId)
        );

    if (!order) {

        await showInfoModal(
            "Order Not Found",
            "The order information could not be found.",
            "OK",
            "error"
        );

        return;
    }


    const status =
        normalizeStatus(
            order.order_status
        );

    const paymentStatus =
        normalizeStatus(
            order.payment_status
        );


    /*
     * Front-end safety check.
     * The backend performs the authoritative validation.
     */

    if (
        status !== "AWAITING_PAYMENT" ||
        !(
            paymentStatus === "UNPAID" ||
            paymentStatus === "" ||
            paymentStatus === "NULL"
        )
    ) {

        await showInfoModal(
            "Order Cannot Be Cancelled",
            "Only unpaid orders awaiting payment can be cancelled.",
            "OK",
            "warning"
        );

        return;
    }


    createModalStyles();


    const existing =
        document.getElementById(
            "kohicebuModalOverlay"
        );

    if (existing) {
        existing.remove();
    }


    const overlay =
        document.createElement("div");

    overlay.id =
        "kohicebuModalOverlay";

    overlay.className =
        "kohicebu-modal-overlay";


    overlay.innerHTML = `

        <div
            class="kohicebu-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="cancelOrderModalTitle"
        >

            <div class="kohicebu-modal-header">

                <div
                    class="kohicebu-modal-icon warning"
                >
                    !
                </div>

                <h3
                    class="kohicebu-modal-title"
                    id="cancelOrderModalTitle"
                >
                    Cancel Order #${Number(orderId)}
                </h3>

            </div>


            <div class="kohicebu-modal-body">

                <p
                    class="kohicebu-modal-message"
                >
                    This will cancel the unpaid order
                    and release its remaining inventory reservation.
                </p>


                <div
                    style="
                        margin-top: 18px;
                    "
                >

                    <label
                        for="cancelOrderReason"
                        style="
                            display: block;
                            margin-bottom: 7px;
                            color: #555;
                            font-size: 12px;
                            font-weight: 700;
                        "
                    >
                        Reason for Cancellation
                    </label>


                    <textarea
                        id="cancelOrderReason"
                        maxlength="1000"
                        rows="4"
                        placeholder="Enter the reason for cancelling this order..."
                        style="
                            width: 100%;
                            min-height: 96px;
                            padding: 11px 12px;
                            border: 1px solid #dddddd;
                            border-radius: 10px;
                            resize: vertical;
                            box-sizing: border-box;
                            font-family: inherit;
                            font-size: 13px;
                            line-height: 1.5;
                            outline: none;
                        "
                    ></textarea>


                    <div
                        style="
                            margin-top: 5px;
                            color: #999;
                            font-size: 11px;
                        "
                    >
                        A reason is required for accountability.
                    </div>

                </div>

            </div>


            <div class="kohicebu-modal-footer">

                <button
                    type="button"
                    class="kohicebu-modal-button cancel"
                    id="cancelOrderCancelButton"
                >
                    Keep Order
                </button>


                <button
                    type="button"
                    class="kohicebu-modal-button danger"
                    id="cancelOrderConfirmButton"
                >
                    Cancel Order
                </button>

            </div>

        </div>

    `;


    document.body.appendChild(
        overlay
    );


    const reasonInput =
        document.getElementById(
            "cancelOrderReason"
        );

    const cancelButton =
        document.getElementById(
            "cancelOrderCancelButton"
        );

    const confirmButton =
        document.getElementById(
            "cancelOrderConfirmButton"
        );


    function close() {

        overlay.classList.remove(
            "show"
        );

        setTimeout(
            () => {

                if (overlay.parentNode) {
                    overlay.remove();
                }

            },
            180
        );

    }


    cancelButton.addEventListener(
        "click",
        close
    );


    overlay.addEventListener(
        "click",
        event => {

            if (
                event.target ===
                overlay
            ) {

                close();

            }

        }
    );


    /*
     * Confirm cancellation
     */

    confirmButton.addEventListener(
        "click",
        async () => {

            const reason =
                reasonInput
                    ? reasonInput.value.trim()
                    : "";


            if (!reason) {

                await showInfoModal(
                    "Reason Required",
                    "Please enter a reason for cancelling this order.",
                    "OK",
                    "warning"
                );

                if (reasonInput) {
                    reasonInput.focus();
                }

                return;
            }


            if (reason.length > 1000) {

                await showInfoModal(
                    "Reason Too Long",
                    "The cancellation reason cannot exceed 1000 characters.",
                    "OK",
                    "warning"
                );

                return;
            }


            const confirmed =
                await showConfirmModal(
                    "Confirm Cancellation",
                    `Are you sure you want to cancel Order #${Number(orderId)}?`,
                    "Cancel Order",
                    "Keep Order",
                    "warning"
                );


            if (!confirmed) {
                return;
            }


            confirmButton.disabled = true;

            confirmButton.textContent =
                "Cancelling...";


            showSystemStatus(
                `Cancelling Order #${Number(orderId)}...`
            );


            const response =
                await staffApiRequest(
                    "cancel_order.php",
                    {
                        method: "POST",
                        body:
                            JSON.stringify({
                                order_id:
                                    Number(orderId),
                                reason:
                                    reason
                            })
                    }
                );


            if (!response) {

                confirmButton.disabled = false;

                confirmButton.textContent =
                    "Cancel Order";

                return;
            }


            let data;

            try {

                data =
                    await response.json();

            }

            catch (error) {

                console.error(
                    "Invalid cancellation response:",
                    error
                );

                confirmButton.disabled = false;

                confirmButton.textContent =
                    "Cancel Order";

                await showInfoModal(
                    "Cancellation Error",
                    "The server returned an invalid response. Please try again.",
                    "OK",
                    "error"
                );

                return;
            }


            if (!data.success) {

                confirmButton.disabled = false;

                confirmButton.textContent =
                    "Cancel Order";

                await showInfoModal(
                    "Cancellation Failed",
                    data.message ||
                    "Unable to cancel the order.",
                    "OK",
                    "error"
                );

                return;
            }


            close();


            await showInfoModal(
                "Order Cancelled",
                `Order #${Number(orderId)} has been cancelled successfully.`,
                "OK",
                "success"
            );


            await loadOrders();

        }
    );


    requestAnimationFrame(
        () => {

            overlay.classList.add(
                "show"
            );

        }
    );

}


/* =========================================================
   CONFIRM CASH PAYMENT
========================================================= */

async function confirmCashPayment(orderId) {

    const confirmed =
        await showConfirmModal(
            "Confirm Cash Payment",
            `Are you sure that Order #${orderId} has been paid in cash?`,
            "Confirm Payment",
            "Cancel",
            "confirm"
        );

    if (!confirmed) {
        return;
    }


    showSystemStatus(
        `Confirming cash payment for Order #${orderId}...`
    );


    const response =
        await staffApiRequest(
            "confirm_cash_payment.php",
            {
                method: "POST",
                body: JSON.stringify({
                    order_id: orderId
                })
            }
        );


    if (!response) {
        return;
    }


    let data;

    try {

        data =
            await response.json();

    }

    catch (error) {

        console.error(
            "Invalid payment response:",
            error
        );

        await showInfoModal(
            "Payment Error",
            "The server returned an invalid response. Please try again.",
            "OK",
            "error"
        );

        showSystemStatus(
            "Invalid response from payment server."
        );

        return;

    }


    if (!data.success) {

        await showInfoModal(
            "Payment Not Confirmed",
            data.message ||
            "Unable to confirm payment.",
            "OK",
            "error"
        );

        showSystemStatus(
            "Payment confirmation failed."
        );

        return;

    }


    await showInfoModal(
        "Payment Confirmed",
        `Order #${orderId} payment has been confirmed successfully.`,
        "OK",
        "success"
    );


    await loadOrders();

}


/* =========================================================
   UPDATE ORDER STATUS
========================================================= */

async function updateOrderStatus(
    orderId,
    newStatus,
    buttonText
) {

    const statusLabel =
        formatStatus(
            newStatus
        );


    const confirmed =
        await showConfirmModal(
            `${statusLabel} Order`,
            `Are you sure you want to change Order #${orderId} to ${statusLabel}?`,
            buttonText || statusLabel,
            "Cancel",
            "confirm"
        );

    if (!confirmed) {
        return;
    }


    showSystemStatus(
        `Updating Order #${orderId}...`
    );


    const response =
        await staffApiRequest(
            "update_order_status.php",
            {
                method: "POST",
                body: JSON.stringify({
                    order_id: orderId,
                    order_status: newStatus
                })
            }
        );


    if (!response) {
        return;
    }


    let data;

    try {

        data =
            await response.json();

    }

    catch (error) {

        console.error(
            "Invalid status response:",
            error
        );

        await showInfoModal(
            "Order Update Error",
            "The server returned an invalid response. Please try again.",
            "OK",
            "error"
        );

        showSystemStatus(
            "Invalid response from status server."
        );

        return;

    }


    if (!data.success) {

        await showInfoModal(
            "Order Update Failed",
            data.message ||
            `Unable to update Order #${orderId}.`,
            "OK",
            "error"
        );

        showSystemStatus(
            "Order status update failed."
        );

        return;

    }


    await showInfoModal(
        "Order Updated",
        `Order #${orderId} is now ${statusLabel}.`,
        "OK",
        "success"
    );


    await loadOrders();

}


/* =========================================================
   START PREPARING
========================================================= */

async function startPreparing(orderId) {

    await updateOrderStatus(
        orderId,
        "Preparing",
        "Start Preparing"
    );

}


/* =========================================================
   MARK READY
========================================================= */

async function markReady(orderId) {

    await updateOrderStatus(
        orderId,
        "Ready",
        "Mark Ready"
    );

}


/* =========================================================
   MARK COMPLETED
========================================================= */

async function markCompleted(orderId) {

    await updateOrderStatus(
        orderId,
        "Completed",
        "Complete"
    );

}


/* =========================================================
   VIEW ORDER
========================================================= */

async function viewOrder(orderId) {

    const order =
        allOrders.find(
            item =>
                Number(item.order_id) ===
                Number(orderId)
        );


    if (!order) {

        await showInfoModal(
            "Order Not Found",
            "The order information could not be found.",
            "OK",
            "error"
        );

        return;

    }


    const customer =
        order.customer?.full_name ||
        "Customer";


    const email =
        order.customer?.email ||
        "Not available";


    const phone =
        order.customer?.phone ||
        "Not available";
        
    const cashier =
         order.cashier?.full_name ||
         "Not assigned";

    const shift =
         order.shift_id !== null &&
         order.shift_id !== undefined
          ? `#${Number(order.shift_id)}`
          : "Not assigned";    


    const items =
        Array.isArray(order.items)
            ? order.items
            : [];


    let itemHtml = "";


    if (items.length) {

        itemHtml =
            items
                .map(item => {

                    const itemName =
                        escapeHtml(
                            item.product_name ||
                            "Product"
                        );

                    const quantity =
                        Number(
                            item.quantity
                        ) || 0;

                    const price =
                        formatCurrency(
                            item.unit_price
                        );

                    return `
                        <div
                            class="kohicebu-detail-item"
                        >

                            <span
                                class="kohicebu-detail-item-name"
                            >
                                ${itemName}
                            </span>

                            <span
                                class="kohicebu-detail-item-qty"
                            >
                                × ${quantity}
                                &nbsp; ${price}
                            </span>

                        </div>
                    `;

                })
                .join("");

    }

    else {

        itemHtml = `
            <div class="kohicebu-detail-item">
                <span class="kohicebu-detail-item-name">
                    No items
                </span>
            </div>
        `;

    }


    await showOrderDetailsModal(
        order,
        customer,
        email,
        phone,
        cashier,
        shift,
        itemHtml
    );

}


/* =========================================================
   ORDER DETAILS MODAL
========================================================= */

function showOrderDetailsModal(
    order,
    customer,
    email,
    phone,
    cashier,
    shift,
    itemHtml
) {

    return new Promise(resolve => {

        createModalStyles();


        const existing =
            document.getElementById(
                "kohicebuModalOverlay"
            );

        if (existing) {
            existing.remove();
        }


        const overlay =
            document.createElement("div");

        overlay.id =
            "kohicebuModalOverlay";

        overlay.className =
            "kohicebu-modal-overlay";


        const status =
            formatStatus(
                normalizeStatus(
                    order.order_status
                )
            );


        const payment =
            formatStatus(
                normalizeStatus(
                    order.payment_status
                )
            );


        overlay.innerHTML = `

            <div
                class="kohicebu-modal"
                role="dialog"
                aria-modal="true"
            >

                <div class="kohicebu-modal-header">

                    <div
                        class="kohicebu-modal-icon info"
                    >
                        #
                    </div>

                    <h3 class="kohicebu-modal-title">
                        Order #${Number(order.order_id)}
                    </h3>

                </div>


                <div class="kohicebu-modal-body">

                    <div
                        class="kohicebu-order-details"
                    >

                        <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Customer
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${escapeHtml(customer)}
                            </span>

                        </div>


                        <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Email
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${escapeHtml(email)}
                            </span>

                        </div>


                                                <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Phone
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${escapeHtml(phone)}
                            </span>

                        </div>


                        <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Cashier
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${escapeHtml(cashier)}
                            </span>

                        </div>


                        <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Shift
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${escapeHtml(shift)}
                            </span>

                        </div>


                        <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Status
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${escapeHtml(status)}
                            </span>

                        </div>


                        <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Payment
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${escapeHtml(payment)}
                            </span>

                        </div>


                        <div
                            class="kohicebu-detail-row"
                        >

                            <span
                                class="kohicebu-detail-label"
                            >
                                Total
                            </span>

                            <span
                                class="kohicebu-detail-value"
                            >
                                ${formatCurrency(
                                    order.total_amount
                                )}
                            </span>

                        </div>


                        <div>

                            <div
                                class="kohicebu-detail-label"
                                style="margin-bottom: 8px;"
                            >
                                Order Items
                            </div>

                            <div
                                class="kohicebu-detail-items"
                            >
                                ${itemHtml}
                            </div>

                        </div>

                    </div>

                </div>


                <div class="kohicebu-modal-footer">

                    <button
                        type="button"
                        class="kohicebu-modal-button primary"
                        id="kohicebuModalClose"
                    >
                        Close
                    </button>

                </div>

            </div>

        `;


        document.body.appendChild(
            overlay
        );


        const closeButton =
            document.getElementById(
                "kohicebuModalClose"
            );


        function close() {

            overlay.classList.remove(
                "show"
            );

            setTimeout(
                () => {

                    if (overlay.parentNode) {
                        overlay.remove();
                    }

                },
                180
            );

            resolve();

        }


        closeButton.addEventListener(
            "click",
            close
        );


        overlay.addEventListener(
            "click",
            event => {

                if (
                    event.target ===
                    overlay
                ) {
                    close();
                }

            }
        );


        requestAnimationFrame(
            () => {

                overlay.classList.add(
                    "show"
                );

            }
        );

    });

}


/* =========================================================
   REFRESH
========================================================= */

function setupRefresh() {

    const button =
        document.getElementById(
            "refreshOrders"
        );

    if (!button) {
        return;
    }


    button.addEventListener(
        "click",
        async function () {

            button.disabled = true;

            const originalText =
                button.textContent;


            button.textContent =
                "↻ Refreshing...";


            await loadOrders();


            button.disabled = false;

            button.textContent =
                originalText ||
                "Refresh";

        }
    );

}


/* =========================================================
   HELPERS
========================================================= */

function isActionableUnpaidOrder(order) {

    const paymentStatus =
        normalizeStatus(
            order.payment_status
        );


    return (
        paymentStatus === "UNPAID" ||
        paymentStatus === "" ||
        paymentStatus === "NULL"
    );

}


function isHistoricalPaymentFailure(order) {

    const status =
        normalizeStatus(
            order.order_status
        );


    const paymentStatus =
        normalizeStatus(
            order.payment_status
        );


    return (
        status === "PAYMENT_FAILED" ||
        status === "PAYMENT_EXPIRED" ||
        status === "PAYMENT_CANCELLED" ||

        paymentStatus === "FAILED" ||
        paymentStatus === "EXPIRED" ||
        paymentStatus === "CANCELLED"
    );

}


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


function formatStatus(status) {

    if (!status) {
        return "UNKNOWN";
    }


    return status
        .replace(
            /_/g,
            " "
        );

}


function getStatusClass(status) {

    switch (status) {

        case "AWAITING_PAYMENT":
            return "status-awaiting";

        case "PAID":
            return "status-paid";

        case "PREPARING":
            return "status-preparing";

        case "READY":
            return "status-ready";

        case "COMPLETED":
            return "status-completed";

        case "CANCELLED":
        case "PAYMENT_FAILED":
        case "PAYMENT_EXPIRED":
        case "PAYMENT_CANCELLED":
            return "status-cancelled";

        default:
            return "";

    }

}


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


function formatOrderTime(value) {

    if (!value) {
        return "Unknown time";
    }


    const parsed =
        new Date(
            String(value).replace(
                " ",
                "T"
            )
        );


    if (
        isNaN(
            parsed.getTime()
        )
    ) {

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


function formatSource(value) {

    if (!value) {
        return "";
    }


    return String(value)
        .replace(
            /_/g,
            " "
        );

}


function formatPaymentMethod(value) {

    if (!value) {
        return "";
    }


    return String(value)
        .replace(
            /_/g,
            " "
        );

}


function setText(
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


function showSystemStatus(message) {

    const element =
        document.getElementById(
            "systemStatus"
        );


    if (element) {

        element.textContent =
            message;

    }

}


function showDashboardError() {

    const container =
        document.getElementById(
            "ordersGrid"
        );


    if (container) {

        container.innerHTML = `
            <div class="empty-state">

                <div class="empty-state-title">
                    Unable to load orders
                </div>

                <div class="empty-state-description">
                    Please check the server connection
                    and try refreshing the order queue.
                </div>

            </div>
        `;

    }


    showSystemStatus(
        "Unable to load live order data."
    );

}


function escapeHtml(value) {

    if (
        value === null ||
        value === undefined
    ) {

        return "";

    }


    return String(value)
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );

}


/* =========================================================
   INITIALIZE
========================================================= */

async function initializeOrdersPage() {

    if (!checkStaffSession()) {
        return;
    }


    displayStaffInfo();

    

    setupFilters();

    setupRefresh();

    await loadOrders();

}


/* =========================================================
   START
========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    initializeOrdersPage
);