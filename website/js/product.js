document.addEventListener("DOMContentLoaded", function () {
    loadProduct();
});


let currentProduct = null;
let currentQuantity = 1;


/* =========================================================
   LOAD PRODUCT
   ========================================================= */

async function loadProduct() {

    const productLoading =
        document.getElementById("productLoading");

    const productError =
        document.getElementById("productError");

    const productNotFound =
        document.getElementById("productNotFound");

    const productDetail =
        document.getElementById("productDetail");


    const productId =
        getProductId();


    if (!productId) {

        hideElement(productLoading);
        showElement(productNotFound);

        return;
    }


    try {

        showElement(productLoading);

        hideElement(productError);
        hideElement(productNotFound);
        hideElement(productDetail);


        const response =
            await fetch(
                "../api/get_products.php",
                {
                    method: "GET",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );


        if (!response.ok) {

            throw new Error(
                `HTTP error: ${response.status}`
            );

        }


        const data =
            await response.json();


        if (
            !data.success ||
            !Array.isArray(data.products)
        ) {

            throw new Error(
                "Invalid product response."
            );

        }


        const product =
            data.products.find(
                function (item) {

                    return Number(
                        item.product_id
                    ) === productId;

                }
            );


        if (!product) {

            hideElement(productLoading);
            showElement(productNotFound);

            return;
        }


        currentProduct =
            product;


        renderProduct(
            product
        );


        hideElement(
            productLoading
        );

        showElement(
            productDetail
        );


    } catch (error) {

        console.error(
            "KohiCebu product:",
            error
        );


        hideElement(
            productLoading
        );

        showElement(
            productError
        );

    }

}


/* =========================================================
   GET PRODUCT ID
   ========================================================= */

function getProductId() {

    const params =
        new URLSearchParams(
            window.location.search
        );


    const productId =
        Number(
            params.get("id")
        );


    if (
        !Number.isInteger(productId) ||
        productId <= 0
    ) {

        return null;

    }


    return productId;

}


/* =========================================================
   RENDER PRODUCT
   ========================================================= */

function renderProduct(
    product
) {

    const categoryElement =
        document.getElementById(
            "productCategory"
        );

    const nameElement =
        document.getElementById(
            "productName"
        );

    const priceElement =
        document.getElementById(
            "productPrice"
        );

    const descriptionElement =
        document.getElementById(
            "productDescription"
        );

    const imageElement =
        document.getElementById(
            "productImage"
        );

    const availabilityElement =
        document.getElementById(
            "productAvailability"
        );

    const quantityWrapper =
        document.getElementById(
            "productQuantityWrapper"
        );

    const addToCartButton =
        document.getElementById(
            "addToCartButton"
        );

    const unavailableElement =
        document.getElementById(
            "productUnavailable"
        );


    const productName =
        product.product_name || "";


    const category =
        formatCategoryName(
            product.category || ""
        );


    const description =
        product.description || "";


    const price =
        formatPrice(
            product.price
        );


    const canSell =
        product.can_sell === true;


    const maxQuantity =
        Number(
            product.max_sellable_quantity
        );


    /* =====================================================
       BASIC INFORMATION
       ===================================================== */

    categoryElement.textContent =
        category;


    nameElement.textContent =
        productName;


    priceElement.textContent =
        price;


    descriptionElement.textContent =
        description;


    /* =====================================================
       IMAGE
       ===================================================== */

    const imageUrl =
        resolveProductImage(
            product.image_url
        );


    if (imageUrl) {

        imageElement.innerHTML = `
            <img
                src="${escapeAttribute(imageUrl)}"
                alt="${escapeAttribute(productName)}"
            >
        `;

    } else {

        imageElement.innerHTML = `
            <div class="product-detail__placeholder">
                <span>KOHI CEBU</span>
            </div>
        `;

    }


    /* =====================================================
       AVAILABILITY
       ===================================================== */

    if (canSell) {

        availabilityElement.textContent =
            "AVAILABLE";

        availabilityElement.className =
            "product-detail__availability is-available";


        quantityWrapper.hidden =
            false;


        addToCartButton.disabled =
            false;


        addToCartButton.hidden =
            false;


        unavailableElement.hidden =
            true;


        setupQuantityControl(
            maxQuantity
        );


    } else {

        availabilityElement.textContent =
            "SOLD OUT";

        availabilityElement.className =
            "product-detail__availability is-sold-out";


        quantityWrapper.hidden =
            true;


        addToCartButton.disabled =
            true;


        addToCartButton.hidden =
            true;


        unavailableElement.hidden =
            false;

    }


    /* =====================================================
       ORDER NOW
       ===================================================== */

    addToCartButton.onclick =
        function () {

            openOrderDrawer(
                product
            );

        };

}


/* =========================================================
   PRODUCT QUANTITY
   ========================================================= */

function setupQuantityControl(
    maxQuantity
) {

    const decreaseButton =
        document.getElementById(
            "quantityDecrease"
        );

    const increaseButton =
        document.getElementById(
            "quantityIncrease"
        );

    const quantityValue =
        document.getElementById(
            "quantityValue"
        );


    const safeMax =
        Number.isInteger(maxQuantity) &&
        maxQuantity > 0
            ? maxQuantity
            : 1;


    currentQuantity = 1;


    quantityValue.textContent =
        currentQuantity;


    decreaseButton.onclick =
        function () {

            if (
                currentQuantity <= 1
            ) {

                return;

            }


            currentQuantity--;


            quantityValue.textContent =
                currentQuantity;

        };


    increaseButton.onclick =
        function () {

            if (
                currentQuantity >=
                safeMax
            ) {

                return;

            }


            currentQuantity++;


            quantityValue.textContent =
                currentQuantity;

        };

}


/* =========================================================
   ORDER DRAWER
   ========================================================= */

function openOrderDrawer(
    product
) {

    if (!product) {
        return;
    }


    currentProduct =
        product;


    const drawer =
        document.getElementById(
            "orderDrawer"
        );


    if (!drawer) {
        return;
    }


    populateOrderDrawer(
        product
    );


    drawer.classList.add(
        "is-open"
    );


    drawer.setAttribute(
        "aria-hidden",
        "false"
    );


    document.body.classList.add(
        "order-drawer-open"
    );

}


function closeOrderDrawer() {

    const drawer =
        document.getElementById(
            "orderDrawer"
        );


    if (!drawer) {
        return;
    }


    drawer.classList.remove(
        "is-open"
    );


    drawer.setAttribute(
        "aria-hidden",
        "true"
    );


    document.body.classList.remove(
        "order-drawer-open"
    );

}


/* =========================================================
   DRAWER CONTENT
   ========================================================= */

function populateOrderDrawer(
    product
) {

    const imageElement =
        document.getElementById(
            "drawerProductImage"
        );

    const categoryElement =
        document.getElementById(
            "drawerProductCategory"
        );

    const nameElement =
        document.getElementById(
            "drawerProductName"
        );

    const priceElement =
        document.getElementById(
            "drawerProductPrice"
        );


    const imageUrl =
        resolveProductImage(
            product.image_url
        );


    if (imageUrl) {

        imageElement.innerHTML = `
            <img
                src="${escapeAttribute(imageUrl)}"
                alt="${escapeAttribute(product.product_name || "")}"
            >
        `;

    } else {

        imageElement.innerHTML = `
            <div class="order-drawer__item-placeholder">
                KOHI CEBU
            </div>
        `;

    }


    categoryElement.textContent =
        formatCategoryName(
            product.category || ""
        );


    nameElement.textContent =
        product.product_name || "";


    priceElement.textContent =
        formatPrice(
            product.price
        );


    updateDrawerQuantity();

}


/* =========================================================
   DRAWER QUANTITY
   ========================================================= */

function updateDrawerQuantity() {

    if (!currentProduct) {
        return;
    }


    const quantityValue =
        document.getElementById(
            "drawerQuantityValue"
        );

    const subtotal =
        document.getElementById(
            "drawerSubtotal"
        );


    const price =
        Number(
            currentProduct.price
        ) || 0;


    const total =
        price *
        currentQuantity;


    quantityValue.textContent =
        currentQuantity;


    subtotal.textContent =
        formatPrice(
            total
        );

}


/* =========================================================
   DRAWER CONTROLS
   ========================================================= */

function setupOrderDrawerControls() {

    const closeButton =
        document.getElementById(
            "closeOrderDrawer"
        );

    const backdrop =
        document.getElementById(
            "orderDrawerBackdrop"
        );

    const decreaseButton =
        document.getElementById(
            "drawerQuantityDecrease"
        );

    const increaseButton =
        document.getElementById(
            "drawerQuantityIncrease"
        );


    if (closeButton) {

        closeButton.addEventListener(
            "click",
            closeOrderDrawer
        );

    }


    if (backdrop) {

        backdrop.addEventListener(
            "click",
            closeOrderDrawer
        );

    }


    if (decreaseButton) {

        decreaseButton.addEventListener(
            "click",
            function () {

                if (
                    currentQuantity <= 1
                ) {

                    return;

                }


                currentQuantity--;


                syncMainQuantity();


                updateDrawerQuantity();

            }
        );

    }


    if (increaseButton) {

        increaseButton.addEventListener(
            "click",
            function () {

                if (!currentProduct) {
                    return;
                }


                const maxQuantity =
                    Number(
                        currentProduct.max_sellable_quantity
                    ) || 1;


                if (
                    currentQuantity >=
                    maxQuantity
                ) {

                    return;

                }


                currentQuantity++;


                syncMainQuantity();


                updateDrawerQuantity();

            }
        );

    }


    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape"
            ) {

                closeOrderDrawer();

            }

        }
    );

}


/* =========================================================
   SYNC MAIN PRODUCT QUANTITY
   ========================================================= */

function syncMainQuantity() {

    const quantityValue =
        document.getElementById(
            "quantityValue"
        );


    if (quantityValue) {

        quantityValue.textContent =
            currentQuantity;

    }

}


/* =========================================================
   CHECKOUT BUTTON
   ========================================================= */

function setupCheckoutButton() {

    const checkoutButton =
        document.getElementById(
            "drawerCheckoutButton"
        );


    if (!checkoutButton) {
        return;
    }


    checkoutButton.addEventListener(
        "click",
        function () {

            if (!currentProduct) {
                return;
            }


            addProductToCart(
                currentProduct,
                currentQuantity
            );


            window.location.href =
                "checkout.php";

        }
    );

}


/* =========================================================
   ADD PRODUCT TO CART
   ========================================================= */

function addProductToCart(
    product,
    quantity
) {

    if (!product) {
        return;
    }


    if (
        product.can_sell !== true
    ) {

        return;

    }


    const cart =
        getCart();


    const productId =
        Number(
            product.product_id
        );


    const existingIndex =
        cart.findIndex(
            function (item) {

                return Number(
                    item.product_id
                ) === productId;

            }
        );


    if (existingIndex !== -1) {

        const existingItem =
            cart[existingIndex];


        const currentCartQuantity =
            Number(
                existingItem.quantity
            ) || 0;


        const maxQuantity =
            Number(
                product.max_sellable_quantity
            ) || 1;


        existingItem.quantity =
            Math.min(
                currentCartQuantity +
                    quantity,
                maxQuantity
            );


    } else {

        cart.push({

            product_id:
                productId,

            product_name:
                product.product_name,

            price:
                Number(
                    product.price
                ),

            image_url:
                product.image_url || "",

            category:
                product.category || "",

            quantity:
                quantity

        });

    }


    saveCart(
        cart
    );

}


/* =========================================================
   CART STORAGE
   ========================================================= */

function getCart() {

    try {

        const storedCart =
            localStorage.getItem(
                "kohiCebuCart"
            );


        if (!storedCart) {
            return [];
        }


        const cart =
            JSON.parse(
                storedCart
            );


        return Array.isArray(cart)
            ? cart
            : [];


    } catch (error) {

        console.error(
            "KohiCebu cart:",
            error
        );

        return [];

    }

}


function saveCart(
    cart
) {

    localStorage.setItem(
        "kohiCebuCart",
        JSON.stringify(
            cart
        )
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


    if (value.startsWith("/")) {

        return value;

    }


    return "../" +
        value.replace(
            /^\.?\//,
            ""
        );

}


/* =========================================================
   FORMAT CATEGORY
   ========================================================= */

function formatCategoryName(
    category
) {

    if (!category) {
        return "Other";
    }


    return String(category)
        .replace(
            /[_-]+/g,
            " "
        )
        .toLowerCase()
        .replace(
            /\b\w/g,
            function (letter) {

                return letter.toUpperCase();

            }
        );

}


/* =========================================================
   FORMAT PRICE
   ========================================================= */

function formatPrice(
    price
) {

    const value =
        Number(
            price
        );


    if (!Number.isFinite(value)) {

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
   ESCAPE ATTRIBUTE
   ========================================================= */

function escapeAttribute(
    value
) {

    return String(value)
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        );

}


/* =========================================================
   UI HELPERS
   ========================================================= */

function showElement(
    element
) {

    if (element) {
        element.hidden = false;
    }

}


function hideElement(
    element
) {

    if (element) {
        element.hidden = true;
    }

}


/* =========================================================
   INITIALIZE DRAWER
   ========================================================= */

setupOrderDrawerControls();
setupCheckoutButton();