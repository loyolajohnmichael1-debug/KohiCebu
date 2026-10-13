document.addEventListener("DOMContentLoaded", function () {
    initializeCheckout();
});


/* =========================================================
   CHECKOUT STATE
   ========================================================= */

let checkoutCart = [];

let selectedOrderType = "PICKUP";

let selectedPaymentMethod = "CASHIER";


/* =========================================================
   INITIALIZE
   ========================================================= */

function initializeCheckout() {

    setupOptionButtons();

    setupRetryButton();

    setupPlaceOrderButton();

    loadCheckoutCart();

}


/* =========================================================
   LOAD CART
   ========================================================= */

function loadCheckoutCart() {

    const loading =
        document.getElementById(
            "checkoutLoading"
        );

    const empty =
        document.getElementById(
            "checkoutEmpty"
        );

    const layout =
        document.getElementById(
            "checkoutLayout"
        );

    const error =
        document.getElementById(
            "checkoutError"
        );


    hideElement(error);

    hideElement(layout);

    showElement(loading);


    try {

        const storedCart =
            localStorage.getItem(
                "kohiCebuCart"
            );


        if (!storedCart) {

            showEmptyCheckout();

            return;

        }


        const parsedCart =
            JSON.parse(
                storedCart
            );


        if (
            !Array.isArray(parsedCart) ||
            parsedCart.length === 0
        ) {

            showEmptyCheckout();

            return;

        }


    checkoutCart =
        parsedCart.filter(
                function (item) {

                return (
                        item &&
                    Number.isInteger(Number(item.product_id)) &&
                    Number(item.product_id) > 0 &&
                    Number.isInteger(Number(item.quantity)) &&
                    Number(item.quantity) > 0
                    );

                }
            );


        if (
            checkoutCart.length === 0
        ) {

            showEmptyCheckout();

            return;

        }


        renderCheckout();


        hideElement(loading);

        hideElement(empty);

        showElement(layout);


    } catch (errorObject) {

        console.error(
            "KohiCebu checkout:",
            errorObject
        );


        hideElement(loading);

        hideElement(layout);

        hideElement(empty);

        showElement(error);

    }

}


/* =========================================================
   RENDER CHECKOUT
   ========================================================= */

function renderCheckout() {

    renderSummaryItems();

    updateSummaryTotals();

}


/* =========================================================
   SUMMARY ITEMS
   ========================================================= */

function renderSummaryItems() {

    const container =
        document.getElementById(
            "checkoutSummaryItems"
        );


    if (!container) {
        return;
    }


    container.innerHTML = "";


    checkoutCart.forEach(
        function (item) {

            const productName =
                item.product_name ||
                "Product";


            const quantity =
                Number(
                    item.quantity
                ) || 1;


            const price =
                Number(
                    item.price
                ) || 0;


            const total =
                price * quantity;


            const imageUrl =
                resolveProductImage(
                    item.image_url
                );


            const itemElement =
                document.createElement(
                    "div"
                );


            itemElement.className =
                "checkout-summary__item";


            /* -------------------------------------------------
               PRODUCT IMAGE
               ------------------------------------------------- */

            const imageElement =
                document.createElement(
                    "div"
                );


            imageElement.className =
                "checkout-summary__item-image";


            if (imageUrl) {

                const image =
                    document.createElement(
                        "img"
                    );


                image.src =
                    imageUrl;


                image.alt =
                    productName;


                imageElement.appendChild(
                    image
                );


            } else {

                imageElement.innerHTML = `
                    <div class="checkout-summary__item-placeholder">
                        KOHI CEBU
                    </div>
                `;

            }


            /* -------------------------------------------------
               PRODUCT INFORMATION
               ------------------------------------------------- */

            const infoElement =
                document.createElement(
                    "div"
                );


            infoElement.className =
                "checkout-summary__item-info";


            const nameElement =
                document.createElement(
                    "h3"
                );


            nameElement.textContent =
                productName;


            const quantityElement =
                document.createElement(
                    "p"
                );


            quantityElement.textContent =
                `Quantity: ${quantity}`;


            infoElement.appendChild(
                nameElement
            );


            infoElement.appendChild(
                quantityElement
            );


            /* -------------------------------------------------
               REMOVE BUTTON
               ------------------------------------------------- */

            const removeButton =
                document.createElement(
                    "button"
                );


            removeButton.type =
                "button";


            removeButton.className =
                "checkout-summary__item-remove";


            removeButton.textContent =
                "Remove";


            removeButton.setAttribute(
                "aria-label",
                `Remove ${productName} from your order`
            );


            removeButton.addEventListener(
                "click",
                function () {

                    removeCheckoutItem(
                        item.product_id
                    );

                }
            );


            infoElement.appendChild(
                removeButton
            );


            /* -------------------------------------------------
               ITEM TOTAL
               ------------------------------------------------- */

            const totalElement =
                document.createElement(
                    "div"
                );


            totalElement.className =
                "checkout-summary__item-total";


            totalElement.textContent =
                formatPrice(
                    total
                );


            /* -------------------------------------------------
               APPEND
               ------------------------------------------------- */

            itemElement.appendChild(
                imageElement
            );


            itemElement.appendChild(
                infoElement
            );


            itemElement.appendChild(
                totalElement
            );


            container.appendChild(
                itemElement
            );

        }
    );

}


/* =========================================================
   REMOVE CHECKOUT ITEM
   ========================================================= */

function removeCheckoutItem(
    productId
) {

    const normalizedProductId =
        Number(productId);


    if (
        !Number.isFinite(
            normalizedProductId
        )
    ) {

        return;

    }


    checkoutCart =
        checkoutCart.filter(
            function (item) {

                return (
                    Number(item.product_id) !==
                    normalizedProductId
                );

            }
        );

    localStorage.setItem(
        "kohiCebuCart",
        JSON.stringify(checkoutCart)
    );

    if (checkoutCart.length === 0) {
        showEmptyCheckout();
        return;
    }


    localStorage.setItem(
        "kohiCebuCart",
        JSON.stringify(
            checkoutCart
        )
    );


    if (
        checkoutCart.length === 0
    ) {

        showEmptyCheckout();

        return;

    }


    renderCheckout();

}


/* =========================================================
   SUMMARY TOTALS
   ========================================================= */

function updateSummaryTotals() {

    const itemCountElement =
        document.getElementById(
            "checkoutItemCount"
        );


    const subtotalElement =
        document.getElementById(
            "checkoutSubtotal"
        );


    const totalElement =
        document.getElementById(
            "checkoutTotal"
        );


    let itemCount = 0;

    let subtotal = 0;


    checkoutCart.forEach(
        function (item) {

            const quantity =
                Number(
                    item.quantity
                ) || 0;


            const price =
                Number(
                    item.price
                ) || 0;


            itemCount +=
                quantity;


            subtotal +=
                price * quantity;

        }
    );


    if (itemCountElement) {

        itemCountElement.textContent =
            itemCount;

    }


    if (subtotalElement) {

        subtotalElement.textContent =
            formatPrice(
                subtotal
            );

    }


    if (totalElement) {

        totalElement.textContent =
            formatPrice(
                subtotal
            );

    }

}


/* =========================================================
   ORDER TYPE + PAYMENT OPTIONS
   ========================================================= */

function setupOptionButtons() {


    /* -----------------------------------------------------
       ORDER TYPE
       ----------------------------------------------------- */

    const orderTypeButtons =
        document.querySelectorAll(
            "[data-order-type]"
        );


    orderTypeButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    orderTypeButtons.forEach(
                        function (item) {

                            item.classList.remove(
                                "is-selected"
                            );

                        }
                    );


                    button.classList.add(
                        "is-selected"
                    );


                    selectedOrderType =
                        button.dataset.orderType;

                }
            );

        }
    );


    /* -----------------------------------------------------
       PAYMENT METHOD
       ----------------------------------------------------- */

    const paymentButtons =
        document.querySelectorAll(
            "[data-payment-method]"
        );


    paymentButtons.forEach(
        function (button) {

            button.addEventListener(
                "click",
                function () {

                    paymentButtons.forEach(
                        function (item) {

                            item.classList.remove(
                                "is-selected"
                            );

                        }
                    );


                    button.classList.add(
                        "is-selected"
                    );


                    selectedPaymentMethod =
                        button.dataset.paymentMethod;

                }
            );

        }
    );

}


/* =========================================================
   PLACE ORDER BUTTON
   ========================================================= */

function setupPlaceOrderButton() {

    const button =
        document.getElementById(
            "placeOrderButton"
        );


    if (!button) {
        return;
    }


    button.addEventListener(
        "click",
        async function () {

            if (
                !validateCheckout()
            ) {

                return;

            }


            button.disabled = true;
            const originalLabel = button.textContent;
            button.textContent = "Placing order…";

            try {
                const response = await fetch("api/checkout.php", {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json"
                    },
                    credentials: "same-origin",
                    body: JSON.stringify({
                        items: checkoutCart.map(item => ({
                            product_id: Number(item.product_id),
                            quantity: Number(item.quantity)
                        })),
                        payment_method: selectedPaymentMethod
                    })
                });

                const result = await response.json();
                if (response.status === 401) {
                    window.location.href = "login.php?redirect=" + encodeURIComponent("checkout.php");
                    return;
                }
                if (!response.ok || !result.success) {
                    if (result.order_created) {
                        localStorage.removeItem("kohiCebuCart");
                        alert(result.message || "Your order was created.");
                        window.location.href = "orders.php";
                        return;
                    }
                    throw new Error(result.message || "We could not place your order.");
                }

                localStorage.removeItem("kohiCebuCart");

                if (result.payment_url) {
                    const paymentUrl = new URL(result.payment_url);
                    if (paymentUrl.protocol !== "https:") {
                        throw new Error("The payment service returned an invalid checkout link.");
                    }
                    window.location.assign(paymentUrl.href);
                    return;
                }

                alert(result.message || "Your order was placed successfully.");
                window.location.href = "orders.php";
            } catch (error) {
                alert(error.message || "We could not place your order. Please try again.");
                button.disabled = false;
                button.textContent = originalLabel;
            }

        }
    );

}


/* =========================================================
   CHECKOUT VALIDATION
   ========================================================= */

function validateCheckout() {

    /*
     * The customer is already authenticated.
     *
     * We no longer validate manual customer
     * information fields because those fields
     * no longer exist on the checkout page.
     */


    if (
        !Array.isArray(checkoutCart) ||
        checkoutCart.length === 0
    ) {

        showEmptyCheckout();

        return false;

    }


    const layout =
        document.getElementById(
            "checkoutLayout"
        );


    if (!layout) {

        return false;

    }


    const customerName =
        (
            layout.dataset.customerName ||
            ""
        ).trim();


    const customerEmail =
        (
            layout.dataset.customerEmail ||
            ""
        ).trim();


    /*
     * The account name is required because
     * the order needs an authenticated customer.
     */

    if (!customerName) {

        alert(
            "We couldn't load your account information. Please log in again."
        );

        return false;

    }


    /*
     * Email is checked only when the account
     * actually provides one.
     */

    if (
        customerEmail &&
        !isValidEmail(customerEmail)
    ) {

        alert(
            "Your account email appears to be invalid. Please update your account information."
        );

        return false;

    }


    return true;

}


/* =========================================================
   EMAIL VALIDATION
   ========================================================= */

function isValidEmail(
    email
) {

    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        .test(email);

}


/* =========================================================
   PHONE VALIDATION
   ========================================================= */

function isValidPhone(
    phone
) {

    const cleaned =
        phone.replace(
            /[\s-]/g,
            ""
        );


    return /^(09\d{9}|\+639\d{9})$/
        .test(cleaned);

}


/* =========================================================
   VALIDATION UI
   ========================================================= */

function showFieldError(
    elementId,
    message
) {

    const element =
        document.getElementById(
            elementId
        );


    if (element) {

        element.textContent =
            message;

    }

}


function clearValidation() {

    const errors =
        document.querySelectorAll(
            ".checkout-field__error"
        );


    errors.forEach(
        function (element) {

            element.textContent =
                "";

        }
    );

}


/* =========================================================
   EMPTY CHECKOUT
   ========================================================= */

function showEmptyCheckout() {

    const loading =
        document.getElementById(
            "checkoutLoading"
        );


    const empty =
        document.getElementById(
            "checkoutEmpty"
        );


    const layout =
        document.getElementById(
            "checkoutLayout"
        );


    hideElement(loading);

    hideElement(layout);

    showElement(empty);

}


/* =========================================================
   RETRY
   ========================================================= */

function setupRetryButton() {

    const button =
        document.getElementById(
            "retryCheckout"
        );


    if (!button) {
        return;
    }


    button.addEventListener(
        "click",
        function () {

            loadCheckoutCart();

        }
    );

}


/* =========================================================
   IMAGE URL
   ========================================================= */

function resolveProductImage(
    imageUrl
) {

    if (!imageUrl) {
        return "";
    }


    const value =
        String(
            imageUrl
        ).trim();


    if (!value) {
        return "";
    }


    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("data:")
    ) {

        return value;

    }


    if (
        value.startsWith("/")
    ) {

        return value;

    }


    return "../" +
        value.replace(
            /^\.?\//,
            ""
        );

}


/* =========================================================
   PRICE FORMAT
   ========================================================= */

function formatPrice(
    price
) {

    const value =
        Number(
            price
        );


    if (
        !Number.isFinite(value)
    ) {

        return "₱0.00";

    }


    return new Intl.NumberFormat(
        "en-PH",
        {
            style: "currency",
            currency: "PHP"
        }
    ).format(value);

}


/* =========================================================
   UI HELPERS
   ========================================================= */

function showElement(
    element
) {

    if (element) {

        element.hidden =
            false;

    }

}


function hideElement(
    element
) {

    if (element) {

        element.hidden =
            true;

    }

}
