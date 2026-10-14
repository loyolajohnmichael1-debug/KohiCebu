const API_BASE = "../api/";


/* =========================================================
   AUTHENTICATION
   ========================================================= */

const token = sessionStorage.getItem("token");
const role = sessionStorage.getItem("role");

if (!token || role !== "ADMIN") {
    window.location.href = "login.php";
}


/* =========================================================
   ELEMENTS
   ========================================================= */

// Header
const refreshProductsButton =
    document.getElementById("refreshProductsButton");

const addProductButton =
    document.getElementById("addProductButton");

// Summary
const totalProducts =
    document.getElementById("totalProducts");

const activeProducts =
    document.getElementById("activeProducts");

const inactiveProducts =
    document.getElementById("inactiveProducts");

const recipeProducts =
    document.getElementById("recipeProducts");

// Filters
const productSearch =
    document.getElementById("productSearch");

const productStatusFilter =
    document.getElementById("productStatusFilter");

const productCategoryFilter =
    document.getElementById("productCategoryFilter");

// Product table
const productCount =
    document.getElementById("productCount");

const productsLoading =
    document.getElementById("productsLoading");

const productsError =
    document.getElementById("productsError");

const productsErrorMessage =
    document.getElementById("productsErrorMessage");

const productsEmpty =
    document.getElementById("productsEmpty");

const productsTableWrapper =
    document.getElementById("productsTableWrapper");

const productsTableBody =
    document.getElementById("productsTableBody");

const productsPagination =
    document.getElementById("productsPagination");

let productsPage = 1;

const retryProductsButton =
    document.getElementById("retryProductsButton");

// Admin
const adminName =
    document.getElementById("adminName");

const signOutButton =
    document.getElementById("signOutButton");

const reportsLink =
    document.getElementById("reportsLink");

const auditLink =
    document.getElementById("auditLink");

const systemStatus =
    document.getElementById("systemStatus");


// Add Product Modal
const addProductModal =
    document.getElementById("addProductModal");

const closeAddProductModal =
    document.getElementById("closeAddProductModal");

const cancelAddProduct =
    document.getElementById("cancelAddProduct");

const saveProductButton =
    document.getElementById("saveProductButton");

const productName =
    document.getElementById("productName");

const productDescription =
    document.getElementById("productDescription");

const productCategory =
    document.getElementById("productCategory");

const productPrice =
    document.getElementById("productPrice");

const productAvailability =
    document.getElementById("productAvailability");

const productStock =
    document.getElementById("productStock");

const productImage =
    document.getElementById("productImage");

const imagePreviewContainer =
    document.getElementById("imagePreviewContainer");

const productImagePreview =
    document.getElementById("productImagePreview");

const addProductMessage =
    document.getElementById("addProductMessage");


// Product Details Modal
const productDetailsModal =
    document.getElementById("productDetailsModal");

    // Delete Product Confirmation Modal
const deleteProductModal =
    document.getElementById("deleteProductModal");

const closeDeleteProductModal =
    document.getElementById("closeDeleteProductModal");

const cancelDeleteProduct =
    document.getElementById("cancelDeleteProduct");

const confirmDeleteProduct =
    document.getElementById("confirmDeleteProduct");

const deleteProductName =
    document.getElementById("deleteProductName");

const closeProductDetailsModal =
    document.getElementById("closeProductDetailsModal");

const closeProductDetails =
    document.getElementById("closeProductDetails");

const productDetailsTitle =
    document.getElementById("productDetailsTitle");

const productDetailsSubtitle =
    document.getElementById("productDetailsSubtitle");

const productDetailsBody =
    document.getElementById("productDetailsBody");


// Recipe Configuration Modal
const recipeModal =
    document.getElementById("recipeModal");

const closeRecipeModal =
    document.getElementById("closeRecipeModal");

const cancelRecipeModal =
    document.getElementById("cancelRecipeModal");

const recipeModalTitle =
    document.getElementById("recipeModalTitle");

const recipeModalSubtitle =
    document.getElementById("recipeModalSubtitle");

const recipeModalLoading =
    document.getElementById("recipeModalLoading");

const recipeModalError =
    document.getElementById("recipeModalError");

const recipeModalErrorMessage =
    document.getElementById("recipeModalErrorMessage");

const retryRecipeModalButton =
    document.getElementById("retryRecipeModalButton");

const recipeModalEmpty =
    document.getElementById("recipeModalEmpty");

const recipeModalContent =
    document.getElementById("recipeModalContent");

const recipeProductName =
    document.getElementById("recipeProductName");

const recipeProductStatus =
    document.getElementById("recipeProductStatus");

const recipeModalTableBody =
    document.getElementById("recipeModalTableBody");

const recipeModalMessage =
    document.getElementById("recipeModalMessage");

const addRecipeIngredientSection =
    document.getElementById("addRecipeIngredientSection");

const addRecipeIngredientButton =
    document.getElementById("addRecipeIngredientButton");

const saveRecipeIngredientButton =
    document.getElementById("saveRecipeIngredientButton");

const recipeIngredientSelect =
    document.getElementById("recipeIngredientSelect");

const recipeIngredientQuantity =
    document.getElementById("recipeIngredientQuantity");

const recipeIngredientUnit =
    document.getElementById("recipeIngredientUnit");


/* =========================================================
   STATE
   ========================================================= */

let allProducts = [];

let selectedImageFile = null;

let selectedRecipeProduct = null;

let selectedDeleteProduct = null;

let currentRecipeIngredients = [];

let allIngredients = [];


/* =========================================================
   ADMIN INFORMATION
   ========================================================= */

if (adminName) {
    adminName.textContent = "Administrator";
}


/* =========================================================
   AUTH HEADERS
   ========================================================= */

function getAuthHeaders() {

    return {
        "Authorization": "Bearer " + token,
        "Content-Type": "application/json"
    };

}


/* =========================================================
   API REQUEST
   ========================================================= */

async function apiRequest(
    endpoint,
    options = {}
) {

    const headers = {
        ...getAuthHeaders(),
        ...(options.headers || {})
    };

    const response = await fetch(
        API_BASE + endpoint,
        {
            ...options,
            headers
        }
    );

    let data = null;

    try {

        data = await response.json();

    }

    catch (error) {

        throw new Error(
            "The server returned an invalid response."
        );

    }

    if (!response.ok || !data.success) {

        throw new Error(
            data.message ||
            "The request could not be completed."
        );

    }

    return data;

}


/* =========================================================
   LOAD PRODUCTS
   ========================================================= */

async function loadProducts() {

    showLoadingState();

    try {

        const data =
            await apiRequest(
                "get_products.php",
                {
                    method: "GET"
                }
            );

        allProducts =
            Array.isArray(data.products)
                ? data.products
                : [];

        populateCategoryFilter();
        populateAddProductCategories();

        updateSummary();

        applyFilters();

        hideProductStates();

        setSystemStatus(
            "Products loaded successfully."
        );

    }

    catch (error) {

        console.error(
            "Product loading error:",
            error
        );

        showProductsError(
            error.message ||
            "Unable to load products."
        );

        setSystemStatus(
            "Unable to load products."
        );

    }

}


/* =========================================================
   SUMMARY
   ========================================================= */

function updateSummary() {

    const total =
        allProducts.length;

    const active =
        allProducts.filter(
            product =>
                Boolean(product.is_available)
        ).length;

    const inactive =
        total - active;

    const withRecipe =
        allProducts.filter(
            product =>
                Boolean(product.has_recipe)
        ).length;


    totalProducts.textContent =
        total;

    activeProducts.textContent =
        active;

    inactiveProducts.textContent =
        inactive;

    recipeProducts.textContent =
        withRecipe;

}


/* =========================================================
   CATEGORY FILTER
   ========================================================= */

function populateCategoryFilter() {

    const currentValue =
        productCategoryFilter.value;

    const categories =
        [
            ...new Set(
                allProducts
                    .map(
                        product =>
                            String(
                                product.category ||
                                ""
                            ).trim()
                    )
                    .filter(
                        category =>
                            category !== ""
                    )
            )
        ]
        .sort(
            (a, b) =>
                a.localeCompare(b)
        );


    productCategoryFilter.innerHTML =
        `
        <option value="ALL">
            ALL CATEGORIES
        </option>
        `;


    categories.forEach(
        category => {

            const option =
                document.createElement("option");

            option.value =
                category;

            option.textContent =
                category;

            productCategoryFilter.appendChild(
                option
            );

        }
    );


    if (
        categories.includes(
            currentValue
        )
    ) {

        productCategoryFilter.value =
            currentValue;

    }

}


function populateAddProductCategories() {

    const currentValue = productCategory.value;
    const categories = new Set([
        "Coffee",
        "Non-Coffee",
        "Pastries & Desserts"
    ]);

    allProducts.forEach(product => {
        const category = String(product.category || "").trim();

        if (category) {
            categories.add(category);
        }
    });

    productCategory.innerHTML = "";

    const placeholder = document.createElement("option");
    placeholder.value = "";
    placeholder.textContent = "Select a category";
    productCategory.appendChild(placeholder);

    [...categories]
        .sort((a, b) => a.localeCompare(b))
        .forEach(category => {
            const option = document.createElement("option");
            option.value = category;
            option.textContent = category;
            productCategory.appendChild(option);
        });

    if (categories.has(currentValue)) {
        productCategory.value = currentValue;
    }

}


/* =========================================================
   FILTER PRODUCTS
   ========================================================= */

function applyFilters() {

    productsPage = 1;

    const search =
        productSearch.value
            .trim()
            .toLowerCase();

    const status =
        productStatusFilter.value;

    const category =
        productCategoryFilter.value;


    const filteredProducts =
        allProducts.filter(
            product => {

                const name =
                    String(
                        product.product_name ||
                        ""
                    ).toLowerCase();

                const productCategory =
                    String(
                        product.category ||
                        ""
                    ).trim();

                const matchesSearch =
                    !search ||
                    name.includes(search) ||
                    productCategory
                        .toLowerCase()
                        .includes(search);


                if (!matchesSearch) {
                    return false;
                }


                if (
                    category !== "ALL" &&
                    productCategory !== category
                ) {

                    return false;

                }


                const isActive =
                    Boolean(
                        product.is_available
                    );

                const canSell =
                    Boolean(
                        product.can_sell
                    );


                if (status === "ACTIVE") {
                    return isActive;
                }


                if (status === "INACTIVE") {
                    return !isActive;
                }


                if (status === "AVAILABLE") {
                    return (
                        isActive &&
                        canSell
                    );
                }


                if (status === "SOLD_OUT") {
                    return (
                        !canSell
                    );
                }


                return true;

            }
        );


    renderProducts(
        filteredProducts
    );

}


/* =========================================================
   RENDER PRODUCTS
   ========================================================= */

function renderProducts(products) {

    productsTableBody.innerHTML = "";


    productCount.textContent =
        products.length +
        (
            products.length === 1
                ? " product"
                : " products"
        );


    if (products.length === 0) {

        AdminPagination.render(productsPagination, 0, 1, () => {});

        productsTableWrapper.hidden = true;

        productsEmpty.hidden = false;

        return;

    }


    productsEmpty.hidden = true;

    productsTableWrapper.hidden = false;

    productsPage = Math.min(
        productsPage,
        Math.max(1, Math.ceil(products.length / AdminPagination.pageSize))
    );

    const visibleProducts =
        AdminPagination.slice(products, productsPage);


    visibleProducts.forEach(
        product => {

            const row =
                document.createElement("tr");


            /* =============================================
               PRODUCT
               ============================================= */

            const productCell =
                document.createElement("td");

            const productContainer =
                document.createElement("div");

            productContainer.className =
                "product-cell";


            const imageContainer =
                document.createElement("div");

            imageContainer.className =
                "product-image";


            if (
                product.image_url &&
                String(
                    product.image_url
                ).trim() !== ""
            ) {

                const image =
                    document.createElement("img");

                image.src =
                    buildImageUrl(
                        product.image_url
                    );

                image.alt =
                    product.product_name ||
                    "Product image";

                image.loading =
                    "lazy";


                image.onerror =
                    function () {

                        this.style.display =
                            "none";

                        imageContainer.innerHTML =
                            '<span class="product-image-placeholder">☕</span>';

                    };


                imageContainer.appendChild(
                    image
                );

            }

            else {

                imageContainer.innerHTML =
                    '<span class="product-image-placeholder">☕</span>';

            }


            const productInfo =
                document.createElement("div");

            productInfo.className =
                "product-info";


            const productNameElement =
                document.createElement("div");

            productNameElement.className =
                "product-name";

            productNameElement.textContent =
                product.product_name ||
                "Unnamed Product";


            const productDescriptionElement =
                document.createElement("div");

            productDescriptionElement.className =
                "product-description";

            productDescriptionElement.textContent =
                product.description ||
                "No description";


            productInfo.appendChild(
                productNameElement
            );

            productInfo.appendChild(
                productDescriptionElement
            );


            productContainer.appendChild(
                imageContainer
            );

            productContainer.appendChild(
                productInfo
            );


            productCell.appendChild(
                productContainer
            );


            /* =============================================
               CATEGORY
               ============================================= */

            const categoryCell =
                document.createElement("td");

            categoryCell.className =
                "category-text";

            categoryCell.textContent =
                product.category ||
                "—";


            /* =============================================
               PRICE
               ============================================= */

            const priceCell =
                document.createElement("td");

            priceCell.className =
                "price-text";

            priceCell.textContent =
                formatCurrency(
                    product.price
                );


            /* =============================================
               AVAILABILITY
               ============================================= */

            const availabilityCell =
                document.createElement("td");

            availabilityCell.appendChild(
                createStatusBadge(
                    product.is_available
                        ? "ACTIVE"
                        : "INACTIVE",
                    product.is_available
                        ? "status-active"
                        : "status-inactive"
                )
            );


            /* =============================================
               RECIPE
               ============================================= */

            const recipeCell =
                document.createElement("td");

            recipeCell.appendChild(
                createStatusBadge(
                    product.has_recipe
                        ? "CONFIGURED"
                        : "NOT CONFIGURED",
                    product.has_recipe
                        ? "status-recipe"
                        : "status-no-recipe"
                )
            );


            /* =============================================
               SELLABLE
               ============================================= */

            const sellableCell =
                document.createElement("td");

            const canSell =
                Boolean(
                    product.can_sell
                );

            sellableCell.appendChild(
                createStatusBadge(
                    canSell
                        ? "SELLABLE"
                        : "SOLD OUT",
                    canSell
                        ? "status-sellable"
                        : "status-not-sellable"
                )
            );


            /* =============================================
               ACTIONS
               ============================================= */

            const actionCell =
                document.createElement("td");

            const actions =
                document.createElement("div");

            actions.className =
                "product-actions";


            const detailsButton =
                document.createElement("button");

            detailsButton.type =
                "button";

            detailsButton.className =
                "product-action-button";

            detailsButton.textContent =
                "VIEW";

            detailsButton.addEventListener(
                "click",
                function () {

                    openProductDetails(
                        product
                    );

                }
            );

            const editPriceButton =
                document.createElement("button");

            editPriceButton.type = "button";
            editPriceButton.className = "product-action-button";
            editPriceButton.textContent = "EDIT PRICE";
            editPriceButton.addEventListener("click", function () {
                editProductPrice(product);
            });


            const recipeButton =
                document.createElement("button");


                /* =============================================
                 DELETE PRODUCT
                ============================================= */

            const deleteButton =
                  document.createElement("button");

                deleteButton.type =
                   "button";

                deleteButton.className =
                    "product-action-button delete";

                deleteButton.textContent =
                    "DELETE";

                deleteButton.addEventListener(
                    "click",
                    function () {

        openDeleteProductModal(
            product
        );

    }
);

            recipeButton.type =
                "button";

            recipeButton.className =
                "product-action-button recipe";

            recipeButton.textContent =
                product.has_recipe
                    ? "RECIPE"
                    : "CONFIGURE RECIPE";


            recipeButton.addEventListener(
                "click",
                function () {

                    openRecipeManagement(
                        product
                    );

                }
            );


            actions.appendChild(
                detailsButton
            );

            actions.appendChild(
                editPriceButton
            );

            

            actions.appendChild(
                recipeButton
            );

            actions.appendChild(
                deleteButton
            );

            actionCell.appendChild(
                actions
            );


            /* =============================================
               ROW
               ============================================= */

            row.appendChild(
                productCell
            );

            row.appendChild(
                categoryCell
            );

            row.appendChild(
                priceCell
            );

            row.appendChild(
                availabilityCell
            );

            row.appendChild(
                recipeCell
            );

            row.appendChild(
                sellableCell
            );

            row.appendChild(
                actionCell
            );


            productsTableBody.appendChild(
                row
            );

        }
    );

    AdminPagination.render(
        productsPagination,
        products.length,
        productsPage,
        page => {
            productsPage = page;
            renderProducts(products);
        }
    );

}


async function editProductPrice(product) {

    const enteredPrice = window.prompt(
        `Enter the new price for ${product.product_name} (PHP):`,
        Number(product.price).toFixed(2)
    );

    if (enteredPrice === null) {
        return;
    }

    const price = Number(enteredPrice.trim());

    if (!Number.isFinite(price) || price <= 0 || price > 99999999.99) {
        window.alert("Enter a valid price greater than ₱0.00.");
        return;
    }

    try {
        await apiRequest("update_product_price.php", {
            method: "POST",
            body: JSON.stringify({
                product_id: product.product_id,
                price
            })
        });

        await loadProducts();
        setSystemStatus(`Price updated for ${product.product_name}.`);
    } catch (error) {
        window.alert(error.message || "Unable to update product price.");
    }

}


/* =========================================================
   STATUS BADGE
   ========================================================= */

function createStatusBadge(
    text,
    className
) {

    const badge =
        document.createElement("span");

    badge.className =
        "status-badge " +
        className;

    badge.textContent =
        text;

    return badge;

}


/* =========================================================
   IMAGE URL
   ========================================================= */

function buildImageUrl(
    imageUrl
) {

    const value =
        String(
            imageUrl ||
            ""
        ).trim();


    if (!value) {
        return "";
    }


    /*
     * Already a complete URL.
     */

    if (
        value.startsWith(
            "http://"
        ) ||
        value.startsWith(
            "https://"
        )
    ) {

        return value;

    }


    /*
     * Product images returned by the API
     * normally look like:
     *
     * uploads/products/file.png
     */

    return (
        "../" +
        value.replace(
            /^\/+/,
            ""
        )
    );

}


/* =========================================================
   PRODUCT DETAILS
   ========================================================= */

function openProductDetails(
    product
) {

    productDetailsTitle.textContent =
        product.product_name ||
        "Product Details";

    productDetailsSubtitle.textContent =
        product.category ||
        "Product";


    const imageUrl =
        buildImageUrl(
            product.image_url
        );


    const imageHtml =
        imageUrl
            ? `
                <div class="product-details-image">
                    <img
                        src="${escapeHtml(imageUrl)}"
                        alt="${escapeHtml(
                            product.product_name ||
                            "Product"
                        )}"
                    >
                </div>
            `
            : `
                <div class="product-details-image">
                    <div
                        style="
                            width:100%;
                            height:100%;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            font-size:30px;
                            color:#a49384;
                        "
                    >
                        ☕
                    </div>
                </div>
            `;


    productDetailsBody.innerHTML =
        `
        <div class="product-details">

            ${imageHtml}

            <div class="product-details-grid">

                <div class="detail-item">

                    <div class="detail-label">
                        PRODUCT ID
                    </div>

                    <div class="detail-value">
                        #${escapeHtml(
                            product.product_id
                        )}
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        CATEGORY
                    </div>

                    <div class="detail-value">
                        ${escapeHtml(
                            product.category ||
                            "—"
                        )}
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        PRICE
                    </div>

                    <div class="detail-value">
                        ${escapeHtml(
                            formatCurrency(
                                product.price
                            )
                        )}
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        PRODUCT STOCK
                    </div>

                    <div class="detail-value">
                        ${escapeHtml(
                            product.stock_quantity ??
                            "0"
                        )}
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        AVAILABILITY
                    </div>

                    <div class="detail-value">
                        ${product.is_available
                            ? "ACTIVE"
                            : "INACTIVE"}
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        MAX SELLABLE
                    </div>

                    <div class="detail-value">
                        ${escapeHtml(
                            product.max_sellable_quantity ??
                            "0"
                        )}
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        RECIPE
                    </div>

                    <div class="detail-value">
                        ${product.has_recipe
                            ? "CONFIGURED"
                            : "NOT CONFIGURED"}
                    </div>

                </div>


                <div class="detail-item">

                    <div class="detail-label">
                        SELLABILITY
                    </div>

                    <div class="detail-value">
                        ${product.can_sell
                            ? "SELLABLE"
                            : "SOLD OUT"}
                    </div>

                </div>

            </div>


            <div class="detail-description">

                <strong>
                    Description
                </strong>

                <br><br>

                ${escapeHtml(
                    product.description ||
                    "No description provided."
                )}

            </div>

        </div>
        `;


    productDetailsModal.hidden =
        false;

    document.body.classList.add(
        "modal-open"
    );

}

/* =========================================================
   DELETE PRODUCT MODAL
   ========================================================= */

function closeDeleteProductModalWindow() {

    deleteProductModal.hidden =
        true;


    document.body.classList.remove(
        "modal-open"
    );


    selectedDeleteProduct =
        null;

}


closeDeleteProductModal.addEventListener(
    "click",
    closeDeleteProductModalWindow
);


cancelDeleteProduct.addEventListener(
    "click",
    closeDeleteProductModalWindow
);


deleteProductModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target ===
            deleteProductModal
        ) {

            closeDeleteProductModalWindow();

        }

    }
);


confirmDeleteProduct.addEventListener(
    "click",
    async function () {

        if (
            !selectedDeleteProduct
        ) {

            closeDeleteProductModalWindow();

            return;

        }


        const product =
            selectedDeleteProduct;


        confirmDeleteProduct.disabled =
            true;


        confirmDeleteProduct.textContent =
            "DELETING...";


        try {

            closeDeleteProductModalWindow();


            await deleteProduct(
                product
            );

        }

        finally {

            confirmDeleteProduct.disabled =
                false;


            confirmDeleteProduct.textContent =
                "DELETE PRODUCT";

        }

    }
);


/* =========================================================
   DELETE PRODUCT
   ========================================================= */

function openDeleteProductModal(product) {

    if (!deleteProductModal) {
        console.error(
            "Delete product modal was not found."
        );
        return;
    }


    selectedDeleteProduct = product;


    deleteProductName.textContent =
        product.product_name ||
        "this product";


    deleteProductModal.hidden =
        false;


    document.body.classList.add(
        "modal-open"
    );

}


async function deleteProduct(product) {

    const productId =
        Number(
            product.product_id
        );


    const productName =
        product.product_name ||
        "this product";


    if (!productId) {

        setSystemStatus(
            "Invalid product ID."
        );

        return;

    }


    setSystemStatus(
        "Deleting product..."
    );


    try {

        const data =
            await apiRequest(
                "delete_product.php",
                {
                    method: "POST",

                    body:
                        JSON.stringify({
                            product_id:
                                productId
                        })
                }
            );


        setSystemStatus(
            data.message ||
            "Product deleted successfully."
        );


        await loadProducts();


    }

    catch (error) {

        console.error(
            "Product deletion error:",
            error
        );


        setSystemStatus(
            error.message ||
            "Unable to delete product."
        );

    }

}

/* =========================================================
   RECIPE MANAGEMENT
   ========================================================= */

function openRecipeManagement(
    product
) {

    selectedRecipeProduct =
        product;

    recipeModalTitle.textContent =
        product.product_name ||
        "Product Recipe";

    recipeModalSubtitle.textContent =
        "Configure ingredients for " +
        (
            product.product_name ||
            "this product"
        ) +
        ".";

    recipeProductName.textContent =
        product.product_name ||
        "Product";


    resetRecipeModal();


    recipeModal.hidden =
        false;

    document.body.classList.add(
        "modal-open"
    );


    loadRecipeForProduct(
        product.product_id
    );

}


/* =========================================================
   RESET RECIPE MODAL
   ========================================================= */

function resetRecipeModal() {

    currentRecipeIngredients =
        [];

    recipeModalLoading.hidden =
        false;

    recipeModalError.hidden =
        true;

    recipeModalEmpty.hidden =
        true;

    recipeModalContent.hidden =
        true;

    addRecipeIngredientSection.hidden =
        true;

    addRecipeIngredientButton.hidden =
        false;

    saveRecipeIngredientButton.hidden =
        true;

    saveRecipeIngredientButton.disabled =
        false;

    saveRecipeIngredientButton.textContent =
        "SAVE INGREDIENT";


    recipeIngredientSelect.innerHTML =
        `
        <option value="">
            Select an ingredient
        </option>
        `;


    recipeIngredientQuantity.value =
        "";

    recipeIngredientUnit.textContent =
        "Select an ingredient to see its unit.";


    recipeModalMessage.hidden =
        true;

    recipeModalMessage.textContent =
        "";

    recipeModalTableBody.innerHTML =
        "";

    recipeProductStatus.textContent =
        "0 ingredients";

}


/* =========================================================
   LOAD PRODUCT RECIPE
   ========================================================= */

async function loadRecipeForProduct(
    productId
) {

    recipeModalLoading.hidden =
        false;

    recipeModalError.hidden =
        true;

    recipeModalEmpty.hidden =
        true;

    recipeModalContent.hidden =
        true;

    addRecipeIngredientSection.hidden =
        true;

    addRecipeIngredientButton.hidden =
        false;

    saveRecipeIngredientButton.hidden =
        true;


    try {

        const data =
            await apiRequest(
                "get_product_recipes.php?product_id=" +
                encodeURIComponent(
                    productId
                ),
                {
                    method: "GET"
                }
            );


        currentRecipeIngredients =
            Array.isArray(
                data.recipes
            )
                ? data.recipes
                : [];


        recipeModalLoading.hidden =
            true;


        if (
            currentRecipeIngredients.length === 0
        ) {

            recipeModalEmpty.hidden =
                false;

        }

        else {

            recipeModalContent.hidden =
                false;

            renderRecipeIngredients();

        }


        await loadRecipeIngredients();

    }

    catch (error) {

        console.error(
            "Recipe loading error:",
            error
        );


        recipeModalLoading.hidden =
            true;

        recipeModalEmpty.hidden =
            true;

        recipeModalContent.hidden =
            true;

        recipeModalErrorMessage.textContent =
            error.message ||
            "Unable to load recipe.";

        recipeModalError.hidden =
            false;

    }

}


/* =========================================================
   RENDER RECIPE INGREDIENTS
   ========================================================= */

function renderRecipeIngredients() {

    recipeModalTableBody.innerHTML =
        "";


    recipeProductStatus.textContent =
        currentRecipeIngredients.length +
        (
            currentRecipeIngredients.length === 1
                ? " ingredient"
                : " ingredients"
        );


    currentRecipeIngredients.forEach(
        function (recipe) {

            const row =
                document.createElement("tr");


            const ingredientCell =
                document.createElement("td");

            ingredientCell.textContent =
                recipe.ingredient_name ||
                "Unknown Ingredient";


            const quantityCell =
                document.createElement("td");

            quantityCell.textContent =
                recipe.quantity_required ??
                "0";


            const unitCell =
                document.createElement("td");

            unitCell.textContent =
                recipe.unit_code ||
                recipe.unit_name ||
                "—";


            row.appendChild(
                ingredientCell
            );

            row.appendChild(
                quantityCell
            );

            row.appendChild(
                unitCell
            );


            recipeModalTableBody.appendChild(
                row
            );

        }
    );

}


/* =========================================================
   LOAD INVENTORY INGREDIENTS
   ========================================================= */

async function loadRecipeIngredients() {

    try {

        const data =
            await apiRequest(
                "get_ingredients.php",
                {
                    method: "GET"
                }
            );


        allIngredients =
            Array.isArray(
                data.ingredients
            )
                ? data.ingredients
                : [];


        populateRecipeIngredientSelect();

    }

    catch (error) {

        console.error(
            "Ingredient loading error:",
            error
        );

    }

}


/* =========================================================
   POPULATE INGREDIENT SELECT
   ========================================================= */

function populateRecipeIngredientSelect() {

    recipeIngredientSelect.innerHTML =
        `
        <option value="">
            Select an ingredient
        </option>
        `;


    /*
     * Get ingredients already used by THIS product.
     *
     * These ingredients must remain visible because
     * they can be used by other products.
     *
     * They are only disabled for the current product
     * to prevent duplicate recipe entries.
     */

    const existingIds =
        new Set(
            currentRecipeIngredients.map(
                function (recipe) {

                    return Number(
                        recipe.ingredient_id
                    );

                }
            )
        );


    /*
     * Display ALL master ingredients.
     */

    allIngredients.forEach(
        function (ingredient) {

            const ingredientId =
                Number(
                    ingredient.ingredient_id
                );

            const alreadyUsed =
                existingIds.has(
                    ingredientId
                );


            const option =
                document.createElement("option");


            option.value =
                ingredientId;


            option.dataset.unitCode =
                ingredient.unit_code ||
                "";


            option.dataset.unitName =
                ingredient.unit_name ||
                "";


            const unit =
                ingredient.unit_code ||
                ingredient.unit_name ||
                "—";


            if (alreadyUsed) {

                option.textContent =
                    ingredient.ingredient_name +
                    " (" +
                    unit +
                    ") — Already in recipe";

                option.disabled =
                    true;

            } else {

                option.textContent =
                    ingredient.ingredient_name +
                    " (" +
                    unit +
                    ")";

            }


            recipeIngredientSelect.appendChild(
                option
            );

        }
    );

}


/* =========================================================
   START ADD RECIPE INGREDIENT
   ========================================================= */

function startAddRecipeIngredient() {

    addRecipeIngredientSection.hidden =
        false;

    saveRecipeIngredientButton.hidden =
        false;

    addRecipeIngredientButton.hidden =
        true;


    recipeIngredientSelect.focus();

}


/* =========================================================
   CANCEL ADD RECIPE INGREDIENT
   ========================================================= */

function cancelAddRecipeIngredient() {

    addRecipeIngredientSection.hidden =
        true;

    saveRecipeIngredientButton.hidden =
        true;

    addRecipeIngredientButton.hidden =
        false;


    recipeIngredientSelect.value =
        "";

    recipeIngredientQuantity.value =
        "";

    recipeIngredientUnit.textContent =
        "Select an ingredient to see its unit.";

}


/* =========================================================
   SAVE RECIPE INGREDIENT
   ========================================================= */

async function saveRecipeIngredient() {

    const productId =
        Number(
            selectedRecipeProduct &&
            selectedRecipeProduct.product_id
        );


    const ingredientId =
        Number(
            recipeIngredientSelect.value
        );


    const quantity =
        Number(
            recipeIngredientQuantity.value
        );


    if (!productId) {

        showRecipeModalMessage(
            "No product was selected.",
            "error"
        );

        return;

    }


    if (!ingredientId) {

        showRecipeModalMessage(
            "Please select an ingredient.",
            "error"
        );

        return;

    }


    if (
        !Number.isFinite(quantity) ||
        quantity <= 0
    ) {

        showRecipeModalMessage(
            "Please enter a quantity greater than zero.",
            "error"
        );

        return;

    }


    saveRecipeIngredientButton.disabled =
        true;

    saveRecipeIngredientButton.textContent =
        "SAVING...";


    try {

        await apiRequest(
            "add_product_recipe.php",
            {
                method: "POST",

                body:
                    JSON.stringify({
                        product_id:
                            productId,

                        ingredient_id:
                            ingredientId,

                        quantity_required:
                            quantity
                    })
            }
        );


        showRecipeModalMessage(
            "Ingredient added successfully.",
            "success"
        );


        cancelAddRecipeIngredient();


        await loadRecipeForProduct(
            productId
        );


        await loadProducts();

    }

    catch (error) {

        console.error(
            "Add recipe ingredient error:",
            error
        );


        showRecipeModalMessage(
            error.message ||
            "Unable to add ingredient.",
            "error"
        );

    }

    finally {

        saveRecipeIngredientButton.disabled =
            false;

        saveRecipeIngredientButton.textContent =
            "SAVE INGREDIENT";

    }

}


/* =========================================================
   RECIPE MODAL MESSAGE
   ========================================================= */

function showRecipeModalMessage(
    message,
    type = "error"
) {

    recipeModalMessage.hidden =
        false;

    recipeModalMessage.textContent =
        message;

    recipeModalMessage.className =
        "recipe-modal-message " +
        type;

}


/* =========================================================
   CLOSE RECIPE MODAL
   ========================================================= */

function closeRecipeModalWindow() {

    recipeModal.hidden =
        true;

    document.body.classList.remove(
        "modal-open"
    );


    selectedRecipeProduct =
        null;

    currentRecipeIngredients =
        [];

}


/* =========================================================
   RECIPE INGREDIENT UNIT
   ========================================================= */

recipeIngredientSelect.addEventListener(
    "change",
    function () {

        const option =
            recipeIngredientSelect
                .selectedOptions[0];


        if (
            !option ||
            !option.value
        ) {

            recipeIngredientUnit.textContent =
                "Select an ingredient to see its unit.";

            return;

        }


        recipeIngredientUnit.textContent =
            "Unit: " +
            (
                option.dataset.unitCode ||
                option.dataset.unitName ||
                "—"
            );

    }
);


/* =========================================================
   ADD PRODUCT MODAL
   ========================================================= */

function openAddProductModal() {

    resetAddProductForm();

    addProductModal.hidden =
        false;

    document.body.classList.add(
        "modal-open"
    );


    setTimeout(
        function () {

            productName.focus();

        },
        50
    );

}


function closeAddProductModalWindow() {

    addProductModal.hidden =
        true;

    document.body.classList.remove(
        "modal-open"
    );

    resetAddProductForm();

}


/* =========================================================
   RESET ADD PRODUCT FORM
   ========================================================= */

function resetAddProductForm() {

    productName.value = "";

    productDescription.value = "";

    productCategory.value = "";

    productPrice.value = "";

    productAvailability.value =
        "true";

    productStock.value =
        "0";

    productImage.value = "";

    selectedImageFile =
        null;

    imagePreviewContainer.hidden =
        true;

    productImagePreview.src =
        "";

    hideAddProductMessage();

    saveProductButton.disabled =
        false;

    saveProductButton.textContent =
        "CREATE PRODUCT";

}


/* =========================================================
   IMAGE PREVIEW
   ========================================================= */

function handleImageSelection(
    event
) {

    const file =
        event.target.files &&
        event.target.files[0];


    if (!file) {

        selectedImageFile =
            null;

        imagePreviewContainer.hidden =
            true;

        return;

    }


    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/webp"
    ];


    if (
        !allowedTypes.includes(
            file.type
        )
    ) {

        showAddProductMessage(
            "Please select a JPG, PNG, or WEBP image.",
            "error"
        );

        productImage.value =
            "";

        selectedImageFile =
            null;

        imagePreviewContainer.hidden =
            true;

        return;

    }


    const maxSize =
        5 * 1024 * 1024;


    if (
        file.size > maxSize
    ) {

        showAddProductMessage(
            "The product image must not exceed 5 MB.",
            "error"
        );

        productImage.value =
            "";

        selectedImageFile =
            null;

        imagePreviewContainer.hidden =
            true;

        return;

    }


    selectedImageFile =
        file;


    const objectUrl =
        URL.createObjectURL(
            file
        );


    productImagePreview.src =
        objectUrl;

    imagePreviewContainer.hidden =
        false;


    productImagePreview.onload =
        function () {

            URL.revokeObjectURL(
                objectUrl
            );

        };


    hideAddProductMessage();

}


/* =========================================================
   CREATE PRODUCT
   ========================================================= */

async function createProduct() {

    const name =
        productName.value.trim();

    const description =
        productDescription.value.trim();

    const category =
        productCategory.value.trim();

    const price =
        Number(
            productPrice.value
        );

    const availability =
        productAvailability.value ===
        "true";

    const stock =
        Number(
            productStock.value
        );


    /* =============================================
       VALIDATION
       ============================================= */

    if (!name) {

        showAddProductMessage(
            "Please enter a product name."
        );

        productName.focus();

        return;

    }


    if (!category) {

        showAddProductMessage(
            "Please enter a product category."
        );

        productCategory.focus();

        return;

    }


    if (
        !Number.isFinite(price) ||
        price <= 0
    ) {

        showAddProductMessage(
            "Please enter a valid price greater than zero."
        );

        productPrice.focus();

        return;

    }


    if (
        !Number.isFinite(stock) ||
        stock < 0
    ) {

        showAddProductMessage(
            "Initial stock cannot be negative."
        );

        productStock.focus();

        return;

    }


    /* =============================================
       DISABLE BUTTON
       ============================================= */

    saveProductButton.disabled =
        true;

    saveProductButton.textContent =
        "CREATING...";

    hideAddProductMessage();


    try {

        /* =========================================
           CREATE PRODUCT
           ========================================= */

        const productData = {

            product_name:
                name,

            description:
                description,

            category:
                category,

            price:
                price,

            image_url:
                "",

            is_available:
                availability,

            stock_quantity:
                stock

        };


        const data =
            await apiRequest(
                "add_product.php",
                {
                    method: "POST",

                    body:
                        JSON.stringify(
                            productData
                        )
                }
            );


        const createdProduct =
            data.product;


        if (
            !createdProduct ||
            !createdProduct.product_id
        ) {

            throw new Error(
                "Product was created but the server did not return a product ID."
            );

        }


        const productId =
            Number(
                createdProduct.product_id
            );


        /* =========================================
           UPLOAD IMAGE
           ========================================= */

        if (selectedImageFile) {

            saveProductButton.textContent =
                "UPLOADING IMAGE...";


            await uploadProductImage(
                productId,
                selectedImageFile
            );

        }


        /* =========================================
           SUCCESS
           ========================================= */

        closeAddProductModalWindow();


        setSystemStatus(
            "Product created successfully."
        );


        await loadProducts();


        /*
         * Scroll back toward the catalog after
         * refreshing the product list.
         */

        const productsCard =
            document.querySelector(
                ".products-card"
            );

        if (productsCard) {

            productsCard.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });

        }

    }

    catch (error) {

        console.error(
            "Product creation error:",
            error
        );


        showAddProductMessage(
            error.message ||
            "Unable to create product.",
            "error"
        );


        saveProductButton.disabled =
            false;

        saveProductButton.textContent =
            "CREATE PRODUCT";

    }

}


/* =========================================================
   UPLOAD PRODUCT IMAGE
   ========================================================= */

async function uploadProductImage(
    productId,
    file
) {

    const formData =
        new FormData();


    formData.append(
        "product_id",
        String(
            productId
        )
    );


    formData.append(
        "image",
        file
    );


    const response =
        await fetch(
            API_BASE +
            "upload_product_image.php",
            {
                method: "POST",

                headers: {
                    "Authorization":
                        "Bearer " +
                        token
                },

                body:
                    formData
            }
        );


    const responseText = await response.text();
    let data = null;

    try {
        data = JSON.parse(responseText);
    } catch (error) {
        console.error("Image upload returned a non-JSON response.", {
            status: response.status,
            statusText: response.statusText,
            contentType: response.headers.get("content-type"),
            body: responseText.slice(0, 2000)
        });

        const responsePreview = responseText
            .replace(/\s+/g, " ")
            .slice(0, 220);

        throw new Error(
            `The image server returned an unreadable response (HTTP ${response.status}, ${response.headers.get("content-type") || "unknown content type"}). Response: ${responsePreview || "(empty)"}`
        );
    }


    if (
        !response.ok ||
        !data.success
    ) {

        throw new Error(
            data.message ||
            "Product was created, but the image upload failed."
        );

    }


    return data;

}


/* =========================================================
   ADD PRODUCT MESSAGE
   ========================================================= */

function showAddProductMessage(
    message,
    type = "error"
) {

    addProductMessage.hidden =
        false;

    addProductMessage.textContent =
        message;

    addProductMessage.className =
        "modal-message " +
        type;

}


function hideAddProductMessage() {

    addProductMessage.hidden =
        true;

    addProductMessage.textContent =
        "";

    addProductMessage.className =
        "modal-message";

}


/* =========================================================
   PRODUCT STATES
   ========================================================= */

function showLoadingState() {

    productsLoading.hidden =
        false;

    productsError.hidden =
        true;

    productsEmpty.hidden =
        true;

    productsTableWrapper.hidden =
        true;

}


function showProductsError(
    message
) {

    productsLoading.hidden =
        true;

    productsEmpty.hidden =
        true;

    productsTableWrapper.hidden =
        true;

    productsErrorMessage.textContent =
        message;

    productsError.hidden =
        false;

}


function hideProductStates() {

    productsLoading.hidden =
        true;

    productsError.hidden =
        true;

}


/* =========================================================
   SYSTEM STATUS
   ========================================================= */

function setSystemStatus(
    message
) {

    if (systemStatus) {

        systemStatus.textContent =
            message;

    }

}


/* =========================================================
   FORMAT CURRENCY
   ========================================================= */

function formatCurrency(
    value
) {

    const number =
        Number(value);


    if (
        !Number.isFinite(number)
    ) {

        return "₱0.00";

    }


    return number.toLocaleString(
        "en-PH",
        {
            style: "currency",
            currency: "PHP",
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );

}


/* =========================================================
   ESCAPE HTML
   ========================================================= */

function escapeHtml(
    value
) {

    return String(
        value ?? ""
    )
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
   PRODUCT SEARCH
   ========================================================= */

productSearch.addEventListener(
    "input",
    function () {

        applyFilters();

    }
);


/* =========================================================
   STATUS FILTER
   ========================================================= */

productStatusFilter.addEventListener(
    "change",
    function () {

        applyFilters();

    }
);


/* =========================================================
   CATEGORY FILTER
   ========================================================= */

productCategoryFilter.addEventListener(
    "change",
    function () {

        applyFilters();

    }
);


/* =========================================================
   REFRESH
   ========================================================= */

refreshProductsButton.addEventListener(
    "click",
    function () {

        loadProducts();

    }
);


/* =========================================================
   RETRY
   ========================================================= */

retryProductsButton.addEventListener(
    "click",
    function () {

        loadProducts();

    }
);


/* =========================================================
   ADD PRODUCT BUTTON
   ========================================================= */

addProductButton.addEventListener(
    "click",
    function () {

        openAddProductModal();

    }
);


/* =========================================================
   ADD PRODUCT MODAL EVENTS
   ========================================================= */

closeAddProductModal.addEventListener(
    "click",
    closeAddProductModalWindow
);


cancelAddProduct.addEventListener(
    "click",
    closeAddProductModalWindow
);


saveProductButton.addEventListener(
    "click",
    createProduct
);


addProductModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target ===
            addProductModal
        ) {

            closeAddProductModalWindow();

        }

    }
);


/* =========================================================
   IMAGE INPUT
   ========================================================= */

productImage.addEventListener(
    "change",
    handleImageSelection
);


/* =========================================================
   PRODUCT DETAILS MODAL
   ========================================================= */

function closeProductDetailsModalWindow() {

    productDetailsModal.hidden =
        true;

    document.body.classList.remove(
        "modal-open"
    );

}


closeProductDetailsModal.addEventListener(
    "click",
    closeProductDetailsModalWindow
);


closeProductDetails.addEventListener(
    "click",
    closeProductDetailsModalWindow
);


productDetailsModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target ===
            productDetailsModal
        ) {

            closeProductDetailsModalWindow();

        }

    }
);


/* =========================================================
   RECIPE MODAL EVENTS
   ========================================================= */

addRecipeIngredientButton.addEventListener(
    "click",
    startAddRecipeIngredient
);


saveRecipeIngredientButton.addEventListener(
    "click",
    saveRecipeIngredient
);


closeRecipeModal.addEventListener(
    "click",
    closeRecipeModalWindow
);


cancelRecipeModal.addEventListener(
    "click",
    function () {

        if (
            !addRecipeIngredientSection.hidden
        ) {

            cancelAddRecipeIngredient();

            return;

        }

        closeRecipeModalWindow();

    }
);


retryRecipeModalButton.addEventListener(
    "click",
    function () {

        if (
            selectedRecipeProduct &&
            selectedRecipeProduct.product_id
        ) {

            loadRecipeForProduct(
                selectedRecipeProduct.product_id
            );

        }

    }
);


recipeModal.addEventListener(
    "click",
    function (event) {

        if (
            event.target ===
            recipeModal
        ) {

            closeRecipeModalWindow();

        }

    }
);




/* =========================================================
   PLACEHOLDER LINKS
   ========================================================= */

function comingSoon(event) {

    event.preventDefault();

    setSystemStatus(
        "This Admin feature will be added in a later phase."
    );

}


if (reportsLink) {

    reportsLink.addEventListener(
        "click",
        comingSoon
    );

}


if (auditLink) {

    auditLink.addEventListener(
        "click",
        comingSoon
    );

}


/* =========================================================
   KEYBOARD
   ========================================================= */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key !==
            "Escape"
        ) {

            return;

        }

        if (
           deleteProductModal &&
           !deleteProductModal.hidden
        ) {

                closeDeleteProductModalWindow();

                return;

        }


        if (
            addProductModal &&
            !addProductModal.hidden
        ) {

            closeAddProductModalWindow();

            return;

        }


        if (
            productDetailsModal &&
            !productDetailsModal.hidden
        ) {

            closeProductDetailsModalWindow();

            return;

        }


        if (
            recipeModal &&
            !recipeModal.hidden
        ) {

            closeRecipeModalWindow();

        }

        

    }
);





/* =========================================================
   INITIALIZE
   ========================================================= */

setSystemStatus(
    "Admin Portal is connected."
);

loadProducts();
