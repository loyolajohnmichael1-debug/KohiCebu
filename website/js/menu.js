document.addEventListener("DOMContentLoaded", function () {
    loadMenu();
});


let allProducts = [];
let activeCategory = "ALL";


/* =========================================================
   LOAD MENU
   ========================================================= */

async function loadMenu() {

    const productsContainer =
        document.getElementById("menuProducts");

    const loadingContainer =
        document.getElementById("menuLoading");

    const errorContainer =
        document.getElementById("menuError");

    const emptyContainer =
        document.getElementById("menuEmpty");

    const retryButton =
        document.getElementById("retryMenu");


    if (!productsContainer) {
        return;
    }


    try {

        showMenuLoading(
            productsContainer,
            loadingContainer,
            errorContainer,
            emptyContainer
        );


        const response = await fetch(
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


        allProducts =
            data.products;


        hideElement(
            loadingContainer
        );

        hideElement(
            errorContainer
        );

        hideElement(
            emptyContainer
        );


        if (allProducts.length === 0) {

            showElement(
                emptyContainer
            );

            return;
        }


        buildCategories(
            allProducts
        );


        renderMenu();


    } catch (error) {

        console.error(
            "KohiCebu menu:",
            error
        );


        hideElement(
            loadingContainer
        );

        showElement(
            errorContainer
        );


        if (retryButton) {

            retryButton.onclick =
                function () {

                    loadMenu();

                };
        }

    }

}


/* =========================================================
   BUILD CATEGORIES
   ========================================================= */

function buildCategories(products) {

    const categoriesContainer =
        document.getElementById(
            "menuCategories"
        );


    if (!categoriesContainer) {
        return;
    }


    /*
     * Use a Map so categories with different
     * capitalization or spacing are treated
     * as the same category.
     */
    const categoryMap =
        new Map();


    products.forEach(
        function (product) {

            const rawCategory =
                String(
                    product.category || ""
                ).trim();


            if (!rawCategory) {
                return;
            }


            /*
             * Normalize the category only for
             * comparison.
             *
             * Example:
             *
             * "Pastries & Desserts"
             * "PASTRIES & DESSERTS"
             * " pastries & desserts "
             *
             * will all become:
             *
             * "PASTRIES & DESSERTS"
             */
            const categoryKey =
                normalizeCategoryKey(rawCategory);


            if (!categoryMap.has(categoryKey)) {

                categoryMap.set(
                    categoryKey,
                    rawCategory
                );

            }

        }
    );

    // Keep this filter available even when there are currently no
    // non-coffee products in the catalog.
    if (!categoryMap.has("NONCOFFEE")) {
        categoryMap.set("NONCOFFEE", "Non-Coffee");
    }


    const categories =
        Array.from(
            categoryMap.values()
        );


    /*
     * Sort categories alphabetically.
     */
    categories.sort(
        function (a, b) {

            return formatCategoryName(a)
                .localeCompare(
                    formatCategoryName(b)
                );

        }
    );


    categoriesContainer.innerHTML = "";


    /*
     * Always keep ALL first.
     */
    addCategoryButton(
        categoriesContainer,
        "ALL",
        "All"
    );


    /*
     * Add unique categories.
     */
    categories.forEach(
        function (category) {

            addCategoryButton(
                categoriesContainer,
                category,
                formatCategoryName(
                    category
                )
            );

        }
    );

}


/* =========================================================
   CATEGORY BUTTON
   ========================================================= */

function addCategoryButton(
    container,
    value,
    label
) {

    const button =
        document.createElement(
            "button"
        );


    button.type = "button";

    button.className =
        "menu-category";


    if (value === "ALL") {

        button.classList.add(
            "active"
        );

    }


    button.dataset.category =
        value;


    button.textContent =
        label;


    button.addEventListener(
        "click",
        function () {

            activeCategory =
                value;


            document
                .querySelectorAll(
                    ".menu-category"
                )
                .forEach(
                    function (item) {

                        item.classList.toggle(
                            "active",
                            item.dataset.category ===
                                activeCategory
                        );

                    }
                );


            renderMenu();

        }
    );


    container.appendChild(
        button
    );

}


/* =========================================================
   RENDER MENU
   ========================================================= */

function renderMenu() {

    const container =
        document.getElementById(
            "menuProducts"
        );


    if (!container) {
        return;
    }


    let products =
        allProducts;


    if (activeCategory !== "ALL") {

        products =
            allProducts.filter(
                function (product) {

                    return normalizeCategoryKey(
                        product.category || ""
                    ) === normalizeCategoryKey(activeCategory);

                }
            );

    }


    if (products.length === 0) {

        container.innerHTML = `
            <div class="menu-no-results">
                <p>No products found in this category.</p>
            </div>
        `;

        return;
    }


    container.innerHTML =
        products
            .map(
                function (product) {

                    return createProductCard(
                        product
                    );

                }
            )
            .join("");

}


/* =========================================================
   PRODUCT CARD
   ========================================================= */

function createProductCard(
    product
) {

    const productId =
        Number(
            product.product_id
        );


    const productName =
        escapeHtml(
            product.product_name || ""
        );


    const description =
        escapeHtml(
            product.description || ""
        );


    const category =
        escapeHtml(
            formatCategoryName(
                product.category || ""
            )
        );


    const price =
        formatPrice(
            product.price
        );


    const imageUrl =
        resolveProductImage(
            product.image_url
        );


    const canSell =
        product.can_sell === true;


    const availabilityLabel =
        canSell
            ? "AVAILABLE"
            : "SOLD OUT";


    const availabilityClass =
        canSell
            ? "is-available"
            : "is-sold-out";


    const imageMarkup =
        imageUrl
            ? `
                <img
                    src="${escapeAttribute(imageUrl)}"
                    alt="${escapeAttribute(productName)}"
                    loading="lazy"
                >
            `
            : `
                <div class="product-image-placeholder">
                    <span>KOHI CEBU</span>
                </div>
            `;


    return `
        <article
            class="menu-product-card ${
                canSell
                    ? ""
                    : "menu-product-card--sold-out"
            }"
            data-product-id="${productId}"
        >

            <a
                href="product.php?id=${productId}"
                class="menu-product-card__image"
                aria-label="View ${escapeAttribute(productName)}"
            >

                ${imageMarkup}

                <span
                    class="menu-product-card__availability ${availabilityClass}"
                >
                    ${availabilityLabel}
                </span>

            </a>


            <div class="menu-product-card__content">

                <span class="menu-product-card__category">
                    ${category}
                </span>


                <div class="menu-product-card__heading">

                    <h3>
                        ${productName}
                    </h3>

                    <span class="menu-product-card__price">
                        ${price}
                    </span>

                </div>


                ${
                    description
                        ? `
                            <p>
                                ${description}
                            </p>
                        `
                        : ""
                }


                ${
                    canSell
                        ? `
                            <a
                                href="product.php?id=${productId}"
                                class="menu-product-card__action"
                            >
                                View Product →
                            </a>
                        `
                        : `
                            <span
                                class="menu-product-card__unavailable"
                            >
                                Currently unavailable
                            </span>
                        `
                }

            </div>

        </article>
    `;

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


    if (normalizeCategoryKey(category) === "NONCOFFEE") {
        return "Non-Coffee";
    }

    return String(category)
        .replace(/[_-]+/g, " ")
        .toLowerCase()
        .replace(
            /\b\w/g,
            function (letter) {

                return letter.toUpperCase();

            }
        );

}


function normalizeCategoryKey(category) {

    return String(category || "")
        .trim()
        .replace(/[\s_-]+/g, "")
        .toUpperCase();

}


/* =========================================================
   FORMAT PRICE
   ========================================================= */

function formatPrice(
    price
) {

    const value =
        Number(price);


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
   ESCAPE HTML
   ========================================================= */

function escapeHtml(
    value
) {

    return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

}


/* =========================================================
   ESCAPE ATTRIBUTE
   ========================================================= */

function escapeAttribute(
    value
) {

    return escapeHtml(
        value
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


function showMenuLoading(
    productsContainer,
    loadingContainer,
    errorContainer,
    emptyContainer
) {

    productsContainer.innerHTML = "";

    showElement(
        loadingContainer
    );

    hideElement(
        errorContainer
    );

    hideElement(
        emptyContainer
    );

}
