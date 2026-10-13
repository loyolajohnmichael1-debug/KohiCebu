// =========================================================
// KOHI CEBU — HOME JAVASCRIPT
// =========================================================

document.addEventListener("DOMContentLoaded", function () {

    loadFeaturedProducts();

    loadWebsiteUpdates();

});


/* =========================================================
   FEATURED PRODUCTS
   ========================================================= */

async function loadFeaturedProducts() {

    const productContainer =
        document.getElementById("featuredProducts");

    const errorContainer =
        document.getElementById("featuredProductsError");

    const retryButton =
        document.getElementById("retryProducts");


    if (!productContainer) {
        return;
    }


    /*
     * Existing KohiCebu API
     */

    const apiUrl =
        "../api/get_products.php";


    try {

        showProductLoading(
            productContainer
        );

        hideProductError(
            errorContainer
        );


        const response =
            await fetch(
                apiUrl,
                {
                    method: "GET",
                    headers: {
                        "Accept":
                            "application/json"
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


        /*
         * Homepage display:
         *
         * Only hide products that the admin
         * explicitly marked unavailable.
         *
         * Recipe availability is handled by
         * the actual ordering system.
         */

        const availableProducts =
            data.products.filter(
                function (product) {

                    return (
                        product.is_available === true
                    );

                }
            );


        /*
         * Pick up to three products.
         *
         * We try to show different categories
         * whenever possible.
         */

        const featuredProducts =
            selectFeaturedProducts(
                availableProducts,
                3
            );


        if (
            featuredProducts.length === 0
        ) {

            showNoProducts(
                productContainer
            );

            return;

        }


        renderFeaturedProducts(
            productContainer,
            featuredProducts
        );


    } catch (error) {

        console.error(
            "KohiCebu products:",
            error
        );


        showProductError(
            productContainer,
            errorContainer
        );


        if (retryButton) {

            retryButton.onclick =
                function () {

                    loadFeaturedProducts();

                };

        }

    }

}


/* =========================================================
   SELECT FEATURED PRODUCTS
   ========================================================= */

function selectFeaturedProducts(
    products,
    limit
) {

    const selected = [];

    const usedCategories =
        new Set();


    /*
     * First pass:
     * Get one product from each category.
     */

    for (const product of products) {

        if (
            selected.length >= limit
        ) {

            break;

        }


        const category =
            String(
                product.category || ""
            )
                .trim()
                .toLowerCase();


        if (
            category &&
            !usedCategories.has(category)
        ) {

            selected.push(
                product
            );

            usedCategories.add(
                category
            );

        }

    }


    /*
     * Second pass:
     * Fill any remaining slots.
     */

    for (const product of products) {

        if (
            selected.length >= limit
        ) {

            break;

        }


        if (
            !selected.includes(product)
        ) {

            selected.push(
                product
            );

        }

    }


    return selected;

}


/* =========================================================
   RENDER FEATURED PRODUCTS
   ========================================================= */

function renderFeaturedProducts(
    container,
    products
) {

    container.setAttribute("aria-busy", "false");
    container.innerHTML = "";


    products.forEach(
        function (product) {

            const card =
                document.createElement(
                    "article"
                );


            card.className =
                "product-card";


            /* -----------------------------------------
               PRODUCT IMAGE
               ----------------------------------------- */

            const imageContainer =
                document.createElement(
                    "div"
                );


            imageContainer.className =
                "product-card__image";


            const imageUrl =
                resolveProductImage(
                    product.image_url
                );


            if (imageUrl) {

                const image =
                    document.createElement(
                        "img"
                    );


                image.src =
                    imageUrl;


                image.alt =
                    product.product_name ||
                    "KohiCebu product";


                image.loading =
                    "lazy";


                /*
                 * If the image path is invalid,
                 * show a clean fallback instead.
                 */

                image.addEventListener(
                    "error",
                    function () {

                        image.remove();

                        imageContainer.classList.add(
                            "product-card__image--fallback"
                        );

                        imageContainer.innerHTML =
                            "<span>KOHI CEBU</span>";

                    }
                );


                imageContainer.appendChild(
                    image
                );

            } else {

                imageContainer.innerHTML =
                    "<span>KOHI CEBU</span>";

            }


            /* -----------------------------------------
               PRODUCT CONTENT
               ----------------------------------------- */

            const content =
                document.createElement(
                    "div"
                );


            content.className =
                "product-card__content";


            const details =
                document.createElement(
                    "div"
                );


            const title =
                document.createElement(
                    "h3"
                );


            title.textContent =
                product.product_name ||
                "Kohi Product";


            const description =
                document.createElement(
                    "p"
                );


            description.textContent =
                product.description ||
                "Made with care at KohiCebu.";


            details.appendChild(
                title
            );


            details.appendChild(
                description
            );


            /* -----------------------------------------
               PRICE
               ----------------------------------------- */

            const price =
                document.createElement(
                    "span"
                );


            price.className =
                "product-card__price";


            price.textContent =
                formatPrice(
                    product.price
                );


            content.appendChild(
                details
            );


            content.appendChild(
                price
            );


            /* -----------------------------------------
               ASSEMBLE CARD
               ----------------------------------------- */

            card.appendChild(
                imageContainer
            );


            card.appendChild(
                content
            );


            /* -----------------------------------------
               OPEN MENU
               ----------------------------------------- */

            card.style.cursor =
                "pointer";

            card.tabIndex = 0;
            card.setAttribute("role", "link");
            card.setAttribute(
                "aria-label",
                `Browse the menu for ${product.product_name || "this product"}`
            );


            card.addEventListener(
                "click",
                function () {

                    window.location.href =
                        "menu.php";

                }
            );

            card.addEventListener(
                "keydown",
                function (event) {
                    if (event.key === "Enter") {
                        event.preventDefault();
                        window.location.href = "menu.php";
                    }
                }
            );


            container.appendChild(
                card
            );

        }
    );

}


/* =========================================================
   IMAGE URL
   ========================================================= */

/*
 * API returns:
 *
 * uploads/products/product_xxx.jpg
 *
 * Actual location:
 *
 * KOHICEBU/
 * ├── uploads/
 * │   └── products/
 * └── website/
 *
 * Therefore from website/:
 *
 * ../uploads/products/product_xxx.jpg
 */

function resolveProductImage(
    imageUrl
) {

    if (!imageUrl) {
        return "";
    }


    const value =
        String(imageUrl).trim();


    if (!value) {
        return "";
    }


    /*
     * Already a complete URL.
     */

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("data:")
    ) {

        return value;

    }


    /*
     * Root-relative URL.
     */

    if (
        value.startsWith("/")
    ) {

        return value;

    }


    /*
     * Local KohiCebu image.
     */

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

    const numericPrice =
        Number(price);


    if (
        Number.isNaN(
            numericPrice
        )
    ) {

        return "₱—";

    }


    return new Intl.NumberFormat(
        "en-PH",
        {
            style: "currency",
            currency: "PHP",
            minimumFractionDigits: 2
        }
    ).format(
        numericPrice
    );

}


/* =========================================================
   LOADING STATE
   ========================================================= */

function showProductLoading(
    container
) {

    container.setAttribute("aria-busy", "true");
    container.innerHTML = `

        <div class="product-loading">
            <span>
                Loading Kohi favorites...
            </span>
        </div>

        <div class="product-loading">
            <span></span>
        </div>

        <div class="product-loading">
            <span></span>
        </div>

    `;

}


/* =========================================================
   NO PRODUCTS
   ========================================================= */

function showNoProducts(
    container
) {

    container.setAttribute("aria-busy", "false");
    container.innerHTML = `

        <div class="product-empty">

            <p>
                Our menu is getting ready.
            </p>

            <a
                href="menu.php"
                class="text-link"
            >
                Explore Menu →
            </a>

        </div>

    `;

}


/* =========================================================
   ERROR STATE
   ========================================================= */

function showProductError(
    container,
    errorContainer
) {

    container.setAttribute("aria-busy", "false");
    container.innerHTML = "";


    if (errorContainer) {

        errorContainer.hidden =
            false;

    }

}


/* =========================================================
   HIDE ERROR
   ========================================================= */

function hideProductError(
    errorContainer
) {

    if (errorContainer) {

        errorContainer.hidden =
            true;

    }

}


/* =========================================================
   WEBSITE UPDATES
   ========================================================= */

/*
 * Public API:
 *
 * ../api/get_website_updates.php
 *
 * The API already handles:
 *
 * - Published updates
 * - Start dates
 * - End dates
 * - Expired updates
 *
 * Therefore the website only receives
 * currently visible updates.
 */

async function loadWebsiteUpdates() {

    const updatesContainer =
        document.getElementById(
            "websiteUpdates"
        );

    const errorContainer =
        document.getElementById(
            "websiteUpdatesError"
        );

    const emptyContainer =
        document.getElementById(
            "websiteUpdatesEmpty"
        );

    const retryButton =
        document.getElementById(
            "retryWebsiteUpdates"
        );


    if (!updatesContainer) {

        return;

    }


    const apiUrl =
        "../api/get_website_updates.php";


    try {

        showWebsiteUpdatesLoading(
            updatesContainer
        );

        hideWebsiteUpdatesMessage(
            errorContainer
        );

        hideWebsiteUpdatesMessage(
            emptyContainer
        );


        const response =
            await fetch(
                apiUrl,
                {
                    method: "GET",
                    headers: {
                        "Accept":
                            "application/json"
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
            !Array.isArray(data.updates)
        ) {

            throw new Error(
                "Invalid website update response."
            );

        }


        const websiteUpdates =
            data.updates;


        /*
         * No currently visible updates.
         */

        if (
            websiteUpdates.length === 0
        ) {

            showWebsiteUpdatesEmpty(
                updatesContainer,
                emptyContainer
            );

            return;

        }


        /*
         * Homepage should remain concise.
         *
         * Show the latest four active updates.
         */

        const homepageUpdates =
            websiteUpdates.slice(
                0,
                4
            );


        renderWebsiteUpdates(
            updatesContainer,
            homepageUpdates
        );


    } catch (error) {

        console.error(
            "KohiCebu website updates:",
            error
        );


        showWebsiteUpdatesError(
            updatesContainer,
            errorContainer
        );


        if (retryButton) {

            retryButton.onclick =
                function () {

                    loadWebsiteUpdates();

                };

        }

    }

}


/* =========================================================
   RENDER WEBSITE UPDATES
   ========================================================= */

function renderWebsiteUpdates(
    container,
    updates
) {

    container.setAttribute("aria-busy", "false");
    container.innerHTML = "";


    updates.forEach(
        function (update) {

            const card =
                document.createElement(
                    "article"
                );


            card.className =
                "promo-card";


            /*
             * -----------------------------------------
             * IMAGE
             * -----------------------------------------
             */

            const imageContainer =
                document.createElement(
                    "div"
                );


            imageContainer.className =
                "promo-card__image";


            const imageUrl =
                resolveWebsiteUpdateImage(
                    update.image_url
                );


            if (imageUrl) {

                const image =
                    document.createElement(
                        "img"
                    );


                image.src =
                    imageUrl;


                image.alt =
                    update.title ||
                    "KohiCebu update";


                image.loading =
                    "lazy";


                /*
                 * If the uploaded image cannot
                 * be loaded, use a clean fallback.
                 */

                image.addEventListener(
                    "error",
                    function () {

                        image.remove();

                        imageContainer.classList.add(
                            "promo-card__image--fallback"
                        );

                        imageContainer.innerHTML =
                            "<span>KOHI CEBU</span>";

                    }
                );


                imageContainer.appendChild(
                    image
                );

            } else {

                imageContainer.classList.add(
                    "promo-card__image--fallback"
                );

                imageContainer.innerHTML =
                    "<span>KOHI CEBU</span>";

            }


            /*
             * -----------------------------------------
             * CONTENT
             * -----------------------------------------
             */

            const content =
                document.createElement(
                    "div"
                );


            content.className =
                "promo-card__content";


            /*
             * Update type
             */

            const label =
                document.createElement(
                    "span"
                );


            label.className =
                "promo-card__label";


            label.textContent =
                formatWebsiteUpdateType(
                    update.update_type
                );


            /*
             * Title
             */

            const title =
                document.createElement(
                    "h3"
                );


            title.textContent =
                update.title ||
                "KohiCebu Update";


            /*
             * Description
             */

            const description =
                document.createElement(
                    "p"
                );


            description.textContent =
                update.description ||
                "Discover what's new at KohiCebu.";


            content.appendChild(
                label
            );


            content.appendChild(
                title
            );


            content.appendChild(
                description
            );


            /*
             * -----------------------------------------
             * ASSEMBLE CARD
             * -----------------------------------------
             */

            card.appendChild(
                imageContainer
            );


            card.appendChild(
                content
            );


            container.appendChild(
                card
            );

        }
    );

}


/* =========================================================
   WEBSITE UPDATE TYPE
   ========================================================= */

function formatWebsiteUpdateType(
    type
) {

    switch (type) {

        case "PROMO":
            return "PROMOTION";

        case "ANNOUNCEMENT":
            return "ANNOUNCEMENT";

        case "NEW_PRODUCT":
            return "NEW PRODUCT";

        case "EVENT":
            return "EVENT";

        default:
            return "KOHI UPDATE";

    }

}


/* =========================================================
   WEBSITE UPDATE IMAGE URL
   ========================================================= */

/*
 * API returns:
 *
 * uploads/website_updates/update_xxx.jpg
 *
 * Actual location:
 *
 * KOHICEBU/
 * ├── uploads/
 * │   ├── products/
 * │   └── website_updates/
 * └── website/
 *
 * Therefore from website/:
 *
 * ../uploads/website_updates/update_xxx.jpg
 */

function resolveWebsiteUpdateImage(
    imageUrl
) {

    if (!imageUrl) {

        return "";

    }


    const value =
        String(imageUrl).trim();


    if (!value) {

        return "";

    }


    /*
     * Complete external URL.
     */

    if (
        value.startsWith("http://") ||
        value.startsWith("https://") ||
        value.startsWith("data:")
    ) {

        return value;

    }


    /*
     * Root-relative URL.
     */

    if (
        value.startsWith("/")
    ) {

        return value;

    }


    /*
     * Local website update image.
     */

    return "../" +
        value.replace(
            /^\.?\//,
            ""
        );

}


/* =========================================================
   WEBSITE UPDATE LOADING
   ========================================================= */

function showWebsiteUpdatesLoading(
    container
) {

    container.setAttribute("aria-busy", "true");
    container.innerHTML = `

        <article class="promo-card promo-card--loading">

            <div class="promo-card__image">

                <span>
                    Loading...
                </span>

            </div>

            <div class="promo-card__content">

                <span class="promo-card__label">
                    PLEASE WAIT
                </span>

                <h3>
                    Loading updates
                </h3>

                <p>
                    We're checking what's new
                    at KohiCebu.
                </p>

            </div>

        </article>


        <article class="promo-card promo-card--loading">

            <div class="promo-card__image">

                <span>
                    Loading...
                </span>

            </div>

            <div class="promo-card__content">

                <span class="promo-card__label">
                    PLEASE WAIT
                </span>

                <h3>
                    Loading updates
                </h3>

                <p>
                    We're checking what's new
                    at KohiCebu.
                </p>

            </div>

        </article>

    `;

}


/* =========================================================
   WEBSITE UPDATE EMPTY STATE
   ========================================================= */

function showWebsiteUpdatesEmpty(
    container,
    emptyContainer
) {

    container.setAttribute("aria-busy", "false");
    container.innerHTML = "";


    if (emptyContainer) {

        emptyContainer.hidden =
            false;

    }

}


/* =========================================================
   WEBSITE UPDATE ERROR STATE
   ========================================================= */

function showWebsiteUpdatesError(
    container,
    errorContainer
) {

    container.setAttribute("aria-busy", "false");
    container.innerHTML = "";


    if (errorContainer) {

        errorContainer.hidden =
            false;

    }

}


/* =========================================================
   WEBSITE UPDATE MESSAGE HELPERS
   ========================================================= */

function hideWebsiteUpdatesMessage(
    container
) {

    if (container) {

        container.hidden =
            true;

    }

}
