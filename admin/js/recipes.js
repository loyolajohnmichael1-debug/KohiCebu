const API_BASE = "../api/";

const token = sessionStorage.getItem("token");
const role = sessionStorage.getItem("role");

if (!token || role !== "ADMIN") {
    window.location.href = "login.php";
}


/* =========================================================
   ELEMENTS
   ========================================================= */

const searchInput =
    document.getElementById("recipeSearch");

const categoryFilter =
    document.getElementById("recipeCategoryFilter");

const refreshButton =
    document.getElementById("refreshRecipesButton");

const recipeTableBody =
    document.getElementById("recipeTableBody");

const recipeLoading =
    document.getElementById("recipeLoading");

const recipeError =
    document.getElementById("recipeError");

const recipeErrorMessage =
    document.getElementById("recipeErrorMessage");

const recipeEmpty =
    document.getElementById("recipeEmpty");

const retryRecipeButton =
    document.getElementById("retryRecipeButton");

const recipeListCard =
    document.getElementById("recipeListCard");

const recipeResultCount =
    document.getElementById("recipeResultCount");

const recipesPagination =
    document.getElementById("recipesPagination");

let recipesPage = 1;


/* =========================================================
   SUMMARY
   ========================================================= */

const totalProducts =
    document.getElementById("totalProducts");

const configuredRecipes =
    document.getElementById("configuredRecipes");

const sellableProducts =
    document.getElementById("sellableProducts");

const productsWithoutRecipe =
    document.getElementById("productsWithoutRecipe");


/* =========================================================
   HEADER / ACCOUNT
   ========================================================= */

const currentDate =
    document.getElementById("currentDate");

const adminName =
    document.getElementById("adminName");

const systemStatus =
    document.getElementById("systemStatus");


/* =========================================================
   RECIPE DETAILS MODAL
   ========================================================= */

const recipeDetailsModal =
    document.getElementById("recipeDetailsModal");

const closeRecipeDetailsModal =
    document.getElementById("closeRecipeDetailsModal");

const closeRecipeDetailsButton =
    document.getElementById("closeRecipeDetailsButton");

const recipeDetailsProductName =
    document.getElementById("recipeDetailsProductName");

const recipeDetailsCategory =
    document.getElementById("recipeDetailsCategory");

const recipeDetailsDescription =
    document.getElementById("recipeDetailsDescription");

const recipeDetailsTableBody =
    document.getElementById("recipeDetailsTableBody");

const recipeDetailsCount =
    document.getElementById("recipeDetailsCount");

const recipeDetailsLoading =
    document.getElementById("recipeDetailsLoading");

const recipeDetailsError =
    document.getElementById("recipeDetailsError");

const recipeDetailsErrorMessage =
    document.getElementById("recipeDetailsErrorMessage");

const recipeDetailsContent =
    document.getElementById("recipeDetailsContent");

const recipeDetailsMaxSellable =
    document.getElementById("recipeDetailsMaxSellable");

const inventoryCheckList =
    document.getElementById("inventoryCheckList");


/* =========================================================
   STATE
   ========================================================= */

let allProducts = [];
let filteredProducts = [];
let recipeData = {};
let selectedProduct = null;


/* =========================================================
   ADMIN INFORMATION
   ========================================================= */

if (adminName) {
    adminName.textContent = "Administrator";
}


/* =========================================================
   DATE
   ========================================================= */

if (currentDate) {

    const dateText =
        currentDate.querySelector(
            ".header-date-text"
        );

    const formattedDate =
        new Date().toLocaleDateString(
            "en-PH",
            {
                weekday: "long",
                year: "numeric",
                month: "long",
                day: "numeric"
            }
        );

    if (dateText) {
        dateText.textContent =
            formattedDate;
    } else {
        currentDate.textContent =
            formattedDate;
    }

}


/* =========================================================
   AUTH HEADERS
   ========================================================= */

function getAuthHeaders() {

    return {
        "Authorization":
            "Bearer " + token,

        "Content-Type":
            "application/json"
    };

}


/* =========================================================
   API REQUEST
   ========================================================= */

async function apiRequest(
    url,
    options = {}
) {

    const response =
        await fetch(
            url,
            {
                ...options,

                headers: {
                    ...getAuthHeaders(),
                    ...(options.headers || {})
                }
            }
        );


    let data = null;


    try {

        data =
            await response.json();

    }

    catch (error) {

        throw new Error(
            "The server returned an invalid response."
        );

    }


    if (
        !response.ok ||
        !data.success
    ) {

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


    if (refreshButton) {

        refreshButton.disabled = true;
        refreshButton.textContent = "LOADING...";

    }


    try {

        const data =
            await apiRequest(
                API_BASE +
                "get_products.php"
            );


        allProducts =
            Array.isArray(data.products)
                ? data.products
                : [];


        recipeData = {};


        await loadAllProductRecipes();


        populateCategoryFilter();

        applyFilters();

        updateSummary();


        if (systemStatus) {

            systemStatus.textContent =
                "Recipe data loaded successfully.";

        }

    }

    catch (error) {

        console.error(
            "Recipe overview loading error:",
            error
        );


        showErrorState(
            error.message ||
            "Unable to load recipe information."
        );

    }

    finally {

        if (refreshButton) {

            refreshButton.disabled = false;
            refreshButton.textContent = "REFRESH";

        }

    }

}


/* =========================================================
   LOAD ALL PRODUCT RECIPES
   ========================================================= */

async function loadAllProductRecipes() {

    const requests =
        allProducts.map(
            async function (product) {

                try {

                    const data =
                        await apiRequest(
                            API_BASE +
                            "get_product_recipes.php?product_id=" +
                            encodeURIComponent(
                                product.product_id
                            )
                        );


                    const recipes =
                        Array.isArray(
                            data.recipes
                        )
                            ? data.recipes
                            : [];


                    recipeData[
                        Number(
                            product.product_id
                        )
                    ] = {

                        hasRecipe:
                            data.has_recipe === true ||
                            recipes.length > 0,

                        recipes:
                            recipes,

                        product:
                            data.product ||
                            product

                    };

                }

                catch (error) {

                    console.error(
                        "Recipe loading failed for product:",
                        product.product_name,
                        error
                    );


                    recipeData[
                        Number(
                            product.product_id
                        )
                    ] = {

                        hasRecipe: false,

                        recipes: [],

                        product: product,

                        error:
                            error.message ||
                            "Unable to load recipe."

                    };

                }

            }
        );


    await Promise.all(
        requests
    );

}


/* =========================================================
   CATEGORY FILTER
   ========================================================= */

function populateCategoryFilter() {

    if (!categoryFilter) {
        return;
    }


    const currentValue =
        categoryFilter.value;


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
            function (a, b) {

                return a.localeCompare(b);

            }
        );


    categoryFilter.innerHTML =
        '<option value="">All Categories</option>';


    categories.forEach(
        function (category) {

            const option =
                document.createElement(
                    "option"
                );

            option.value =
                category;

            option.textContent =
                category;

            categoryFilter.appendChild(
                option
            );

        }
    );


    if (
        categories.includes(
            currentValue
        )
    ) {

        categoryFilter.value =
            currentValue;

    }

}


/* =========================================================
   APPLY FILTERS
   ========================================================= */

function applyFilters() {

    recipesPage = 1;

    const search =
        searchInput
            ? searchInput.value
                .trim()
                .toLowerCase()
            : "";


    const category =
        categoryFilter
            ? categoryFilter.value
            : "";


    filteredProducts =
        allProducts.filter(
            function (product) {

                const productName =
                    String(
                        product.product_name ||
                        ""
                    ).toLowerCase();


                const productCategory =
                    String(
                        product.category ||
                        ""
                    );


                const matchesSearch =
                    !search ||
                    productName.includes(
                        search
                    );


                const matchesCategory =
                    !category ||
                    productCategory ===
                        category;


                return (
                    matchesSearch &&
                    matchesCategory
                );

            }
        );


    renderProducts();

}


/* =========================================================
   RENDER PRODUCTS
   ========================================================= */

function renderProducts() {

    if (!recipeTableBody) {
        return;
    }


    recipeTableBody.innerHTML = "";


    if (recipeResultCount) {

        const resultText =
            recipeResultCount.querySelector(
                ".recipe-result-text"
            );

        const text =
            filteredProducts.length +
            (
                filteredProducts.length === 1
                    ? " product"
                    : " products"
            );

        if (resultText) {

            resultText.textContent =
                text;

        } else {

            recipeResultCount.textContent =
                text;

        }

    }


    if (
        filteredProducts.length === 0
    ) {

        AdminPagination.render(recipesPagination, 0, 1, () => {});

        if (recipeListCard) {
            recipeListCard.hidden = true;
        }

        if (recipeEmpty) {
            recipeEmpty.hidden = false;
        }

        return;

    }


    if (recipeEmpty) {
        recipeEmpty.hidden = true;
    }

    if (recipeListCard) {
        recipeListCard.hidden = false;
    }

    recipesPage = Math.min(
        recipesPage,
        Math.max(1, Math.ceil(filteredProducts.length / AdminPagination.pageSize))
    );

    const visibleProducts =
        AdminPagination.slice(filteredProducts, recipesPage);


    visibleProducts.forEach(
        function (product) {

            const productId =
                Number(
                    product.product_id
                );


            const info =
                recipeData[
                    productId
                ] ||
                {
                    hasRecipe: false,
                    recipes: []
                };


            const row =
                document.createElement("tr");


            /* =========================================
               PRODUCT
               ========================================= */

            const productCell =
                document.createElement("td");

            productCell.className =
                "recipe-product-cell";


            const productName =
                document.createElement("div");

            productName.className =
                "recipe-product-name";

            productName.textContent =
                product.product_name ||
                "Unknown Product";


            const productCategory =
                document.createElement("div");

            productCategory.className =
                "recipe-product-category";

            productCategory.textContent =
                product.category ||
                "Uncategorized";


            productCell.appendChild(
                productName
            );

            productCell.appendChild(
                productCategory
            );


            /* =========================================
               INGREDIENT COUNT
               ========================================= */

            const ingredientCell =
                document.createElement("td");


            const ingredientCount =
                document.createElement("span");

            ingredientCount.className =
                "ingredient-count";

            ingredientCount.textContent =
                String(
                    info.recipes.length
                );


            ingredientCell.appendChild(
                ingredientCount
            );


            /* =========================================
               SELLABILITY
               ========================================= */

            const statusCell =
                document.createElement("td");


            const status =
                getRecipeStatus(
                    product,
                    info
                );


            const statusBadge =
                document.createElement("span");

            statusBadge.className =
                "recipe-status " +
                status.className;

            statusBadge.textContent =
                status.label;


            statusCell.appendChild(
                statusBadge
            );


            /* =========================================
               MAX SELLABLE
               ========================================= */

            const maxSellableCell =
                document.createElement("td");


            const maxSellable =
                Number(
                    product.max_sellable_quantity
                );


            if (
                Number.isFinite(
                    maxSellable
                ) &&
                info.hasRecipe
            ) {

                const maxValue =
                    document.createElement(
                        "div"
                    );

                maxValue.className =
                    "max-sellable";

                maxValue.textContent =
                    String(
                        Math.max(
                            0,
                            Math.floor(
                                maxSellable
                            )
                        )
                    );


                const maxLabel =
                    document.createElement(
                        "div"
                    );

                maxLabel.className =
                    "max-sellable-label";

                maxLabel.textContent =
                    "units";


                maxSellableCell.appendChild(
                    maxValue
                );

                maxSellableCell.appendChild(
                    maxLabel
                );

            }

            else {

                maxSellableCell.textContent =
                    "—";

            }


            /* =========================================
               ACTION
               ========================================= */

            const actionCell =
                document.createElement("td");


            const viewButton =
                document.createElement("button");

            viewButton.type =
                "button";

            viewButton.className =
                "recipe-view-button";

            viewButton.textContent =
                "VIEW RECIPE";


            viewButton.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    openRecipeDetails(
                        product
                    );

                }
            );


            actionCell.appendChild(
                viewButton
            );


            row.appendChild(
                productCell
            );

            row.appendChild(
                ingredientCell
            );

            row.appendChild(
                statusCell
            );

            row.appendChild(
                maxSellableCell
            );

            row.appendChild(
                actionCell
            );


            recipeTableBody.appendChild(
                row
            );

        }
    );

    AdminPagination.render(
        recipesPagination,
        filteredProducts.length,
        recipesPage,
        page => {
            recipesPage = page;
            renderProducts();
        }
    );

}


/* =========================================================
   RECIPE STATUS
   ========================================================= */

function getRecipeStatus(
    product,
    info
) {

    if (
        !info.hasRecipe
    ) {

        return {

            label:
                "NO RECIPE",

            className:
                "no-recipe"

        };

    }


    if (
        product.can_sell === true
    ) {

        return {

            label:
                "SELLABLE",

            className:
                "sellable"

        };

    }


    return {

        label:
            "SOLD OUT",

        className:
            "sold-out"

    };

}


/* =========================================================
   UPDATE SUMMARY
   ========================================================= */

function updateSummary() {

    const total =
        allProducts.length;


    const configured =
        allProducts.filter(
            function (product) {

                const info =
                    recipeData[
                        Number(
                            product.product_id
                        )
                    ];

                return (
                    info &&
                    info.hasRecipe
                );

            }
        ).length;


    const sellable =
        allProducts.filter(
            function (product) {

                const info =
                    recipeData[
                        Number(
                            product.product_id
                        )
                    ];

                return (
                    info &&
                    info.hasRecipe &&
                    product.can_sell === true
                );

            }
        ).length;


    const withoutRecipe =
        total -
        configured;


    if (totalProducts) {

        totalProducts.textContent =
            String(total);

    }


    if (configuredRecipes) {

        configuredRecipes.textContent =
            String(configured);

    }


    if (sellableProducts) {

        sellableProducts.textContent =
            String(sellable);

    }


    if (productsWithoutRecipe) {

        productsWithoutRecipe.textContent =
            String(
                Math.max(
                    0,
                    withoutRecipe
                )
            );

    }

}


/* =========================================================
   OPEN RECIPE DETAILS
========================================================= */

async function openRecipeDetails(
    product
) {

    if (
        !recipeDetailsModal
    ) {

        console.error(
            "Recipe details modal was not found."
        );

        return;

    }


    selectedProduct =
        product;


    /*
     * Explicitly remove the hidden state.
     * This works reliably even if the browser
     * previously kept the modal hidden.
     */
    recipeDetailsModal.removeAttribute(
        "hidden"
    );

    recipeDetailsModal.style.display =
        "flex";


    document.body.classList.add(
        "modal-open"
    );


    if (recipeDetailsLoading) {
        recipeDetailsLoading.hidden = false;
    }

    if (recipeDetailsError) {
        recipeDetailsError.hidden = true;
    }

    if (recipeDetailsContent) {
        recipeDetailsContent.hidden = true;
    }


    if (recipeDetailsProductName) {

        recipeDetailsProductName.textContent =
            product.product_name ||
            "Product";

    }


    if (recipeDetailsCategory) {

        recipeDetailsCategory.textContent =
            product.category ||
            "RECIPE";

    }


    if (recipeDetailsDescription) {

        recipeDetailsDescription.textContent =
            product.description ||
            "Current ingredient recipe for this product.";

    }


    try {

        const data =
            await apiRequest(
                API_BASE +
                "get_product_recipes.php?product_id=" +
                encodeURIComponent(
                    product.product_id
                )
            );


        const recipes =
            Array.isArray(data.recipes)
                ? data.recipes
                : [];


        renderRecipeDetails(
            product,
            recipes
        );


        if (recipeDetailsLoading) {
            recipeDetailsLoading.hidden = true;
        }

        if (recipeDetailsContent) {
            recipeDetailsContent.hidden = false;
        }

    }

    catch (error) {

        console.error(
            "Recipe detail error:",
            error
        );


        if (recipeDetailsLoading) {
            recipeDetailsLoading.hidden = true;
        }

        if (recipeDetailsError) {
            recipeDetailsError.hidden = false;
        }

        if (recipeDetailsErrorMessage) {

            recipeDetailsErrorMessage.textContent =
                error.message ||
                "Unable to load recipe details.";

        }

    }

}


/* =========================================================
   RENDER RECIPE DETAILS
   ========================================================= */

function renderRecipeDetails(
    product,
    recipes
) {

    if (!recipeDetailsTableBody) {
        return;
    }


    recipeDetailsTableBody.innerHTML =
        "";


    if (recipeDetailsCount) {

        recipeDetailsCount.textContent =
            recipes.length +
            (
                recipes.length === 1
                    ? " ingredient"
                    : " ingredients"
            );

    }


    if (recipeDetailsMaxSellable) {

        const maxSellable =
            Number(
                product.max_sellable_quantity
            );


        if (
            Number.isFinite(
                maxSellable
            )
        ) {

            recipeDetailsMaxSellable.textContent =
                Math.max(
                    0,
                    Math.floor(
                        maxSellable
                    )
                ) +
                " units";

        }

        else {

            recipeDetailsMaxSellable.textContent =
                "—";

        }

    }


    recipes.forEach(
        function (recipe) {

            const row =
                document.createElement(
                    "tr"
                );


            const ingredientCell =
                document.createElement(
                    "td"
                );

            ingredientCell.textContent =
                recipe.ingredient_name ||
                "Unknown Ingredient";


            const quantityCell =
                document.createElement(
                    "td"
                );

            quantityCell.textContent =
                formatQuantity(
                    recipe.quantity_required
                );


            const unitCell =
                document.createElement(
                    "td"
                );

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


            recipeDetailsTableBody.appendChild(
                row
            );

        }
    );


    renderInventoryCheck(
        recipes
    );

}


/* =========================================================
   INVENTORY CHECK
   ========================================================= */

function renderInventoryCheck(
    recipes
) {

    if (!inventoryCheckList) {
        return;
    }


    inventoryCheckList.innerHTML =
        "";


    if (
        recipes.length === 0
    ) {

        const empty =
            document.createElement(
                "div"
            );

        empty.className =
            "inventory-check-item";

        empty.textContent =
            "No ingredients configured.";

        inventoryCheckList.appendChild(
            empty
        );

        return;

    }


    recipes.forEach(
        function (recipe) {

            const item =
                document.createElement(
                    "div"
                );

            item.className =
                "inventory-check-item";


            const name =
                document.createElement(
                    "span"
                );

            name.className =
                "inventory-check-name";

            name.textContent =
                recipe.ingredient_name ||
                "Unknown Ingredient";


            const status =
                document.createElement(
                    "span"
                );


            const quantity =
                Number(
                    recipe.current_quantity
                );


            const required =
                Number(
                    recipe.quantity_required
                );


            if (
                Number.isFinite(quantity) &&
                Number.isFinite(required)
            ) {

                if (
                    quantity >= required
                ) {

                    status.className =
                        "inventory-check-status available";

                    status.textContent =
                        "AVAILABLE";

                }

                else if (
                    quantity > 0
                ) {

                    status.className =
                        "inventory-check-status low";

                    status.textContent =
                        "LOW";

                }

                else {

                    status.className =
                        "inventory-check-status unavailable";

                    status.textContent =
                        "OUT OF STOCK";

                }

            }

            else {

                status.className =
                    "inventory-check-status";

                status.textContent =
                    "CHECK INVENTORY";

            }


            item.appendChild(
                name
            );

            item.appendChild(
                status
            );


            inventoryCheckList.appendChild(
                item
            );

        }
    );

}


/* =========================================================
   CLOSE RECIPE DETAILS
========================================================= */

function closeRecipeDetails() {

    if (!recipeDetailsModal) {
        return;
    }


    recipeDetailsModal.hidden =
        true;

    recipeDetailsModal.style.display =
        "";


    document.body.classList.remove(
        "modal-open"
    );


    selectedProduct =
        null;

}


/* =========================================================
   UI STATES
========================================================= */

function hideRecipeStates() {

    if (recipeLoading) {
        recipeLoading.hidden = true;
    }

    if (recipeError) {
        recipeError.hidden = true;
    }

    if (recipeEmpty) {
        recipeEmpty.hidden = true;
    }

    if (recipeListCard) {
        recipeListCard.hidden = false;
    }

}


function showLoadingState() {

    hideRecipeStates();


    if (recipeLoading) {
        recipeLoading.hidden = false;
    }

    if (recipeListCard) {
        recipeListCard.hidden = true;
    }

}


function showErrorState(
    message
) {

    if (recipeLoading) {
        recipeLoading.hidden = true;
    }

    if (recipeListCard) {
        recipeListCard.hidden = true;
    }

    if (recipeEmpty) {
        recipeEmpty.hidden = true;
    }

    if (recipeErrorMessage) {
        recipeErrorMessage.textContent =
            message;
    }

    if (recipeError) {
        recipeError.hidden = false;
    }

}


function showEmptyState() {

    if (recipeLoading) {
        recipeLoading.hidden = true;
    }

    if (recipeError) {
        recipeError.hidden = true;
    }

    if (recipeListCard) {
        recipeListCard.hidden = true;
    }

    if (recipeEmpty) {
        recipeEmpty.hidden = false;
    }

}


/* =========================================================
   FORMAT QUANTITY
========================================================= */

function formatQuantity(
    value
) {

    const number =
        Number(value);


    if (
        Number.isNaN(number)
    ) {

        return "—";

    }


    return Number.isInteger(number)
        ? String(number)
        : number.toString();

}


/* =========================================================
   SEARCH
========================================================= */

if (searchInput) {

    searchInput.addEventListener(
        "input",
        applyFilters
    );

}


/* =========================================================
   CATEGORY FILTER
========================================================= */

if (categoryFilter) {

    categoryFilter.addEventListener(
        "change",
        applyFilters
    );

}


/* =========================================================
   REFRESH
========================================================= */

if (refreshButton) {

    refreshButton.addEventListener(
        "click",
        loadProducts
    );

}


/* =========================================================
   RETRY
========================================================= */

if (retryRecipeButton) {

    retryRecipeButton.addEventListener(
        "click",
        loadProducts
    );

}


/* =========================================================
   MODAL EVENTS
========================================================= */

if (closeRecipeDetailsModal) {

    closeRecipeDetailsModal.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            closeRecipeDetails();

        }
    );

}


if (closeRecipeDetailsButton) {

    closeRecipeDetailsButton.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            closeRecipeDetails();

        }
    );

}


/*
 * Clicking the dark backdrop closes the modal.
 */
if (recipeDetailsModal) {

    recipeDetailsModal.addEventListener(
        "click",
        function (event) {

            if (
                event.target ===
                recipeDetailsModal
            ) {

                closeRecipeDetails();

            }

        }
    );

}


/* =========================================================
   KEYBOARD
========================================================= */

document.addEventListener(
    "keydown",
    function (event) {

        if (
            event.key === "Escape" &&
            recipeDetailsModal &&
            !recipeDetailsModal.hidden
        ) {

            closeRecipeDetails();

        }

    }
);


/* =========================================================
   SYSTEM STATUS
========================================================= */

if (systemStatus) {

    systemStatus.textContent =
        "Admin Portal is connected.";

}


/* =========================================================
   INITIALIZE
========================================================= */

loadProducts();
