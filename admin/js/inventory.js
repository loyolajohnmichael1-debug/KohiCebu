/* =========================================================
   KOHICEBU ADMIN PORTAL
   INVENTORY MANAGEMENT
   ========================================================= */

const API_BASE_URL = "../api/";

const adminName = document.getElementById("adminName");

if (adminName) {
    adminName.textContent = "Administrator";
}

let inventoryData = [];
let selectedIngredient = null;
let inventoryPage = 1;


/* =========================================================
   DOM ELEMENTS
   ========================================================= */

const totalIngredients =
    document.getElementById("totalIngredients");

const inStockIngredients =
    document.getElementById("inStockIngredients");

const lowStockIngredients =
    document.getElementById("lowStockIngredients");

const outOfStockIngredients =
    document.getElementById("outOfStockIngredients");

const inventorySearch =
    document.getElementById("inventorySearch");

const stockStatusFilter =
    document.getElementById("stockStatusFilter");

const inventoryTableBody =
    document.getElementById("inventoryTableBody");

const inventoryTableWrapper =
    document.getElementById("inventoryTableWrapper");

const inventoryLoading =
    document.getElementById("inventoryLoading");

const inventoryError =
    document.getElementById("inventoryError");

const inventoryErrorMessage =
    document.getElementById("inventoryErrorMessage");

const inventoryEmpty =
    document.getElementById("inventoryEmpty");

const inventoryCount =
    document.getElementById("inventoryCount");

const inventoryPagination =
    document.getElementById("inventoryPagination");

const systemStatus =
    document.getElementById("systemStatus");

const refreshInventory =
    document.getElementById("refreshInventory");


/* =========================================================
   INVENTORY ACTION MODAL
   ========================================================= */

const inventoryModal =
    document.getElementById("inventoryModal");

const closeInventoryModal =
    document.getElementById("closeInventoryModal");

const cancelInventoryAction =
    document.getElementById("cancelInventoryAction");

const selectedIngredientElement =
    document.getElementById("selectedIngredient");

const inventoryAction =
    document.getElementById("inventoryAction");

const inventoryQuantity =
    document.getElementById("inventoryQuantity");

const inventoryUnitHelp =
    document.getElementById("inventoryUnitHelp");

const inventoryReason =
    document.getElementById("inventoryReason");

const inventoryModalError =
    document.getElementById("inventoryModalError");

const saveInventoryAction =
    document.getElementById("saveInventoryAction");


/* =========================================================
   ADD INGREDIENT MODAL
   ========================================================= */

const addIngredientButton =
    document.getElementById("addIngredientButton");

const addIngredientModal =
    document.getElementById("addIngredientModal");

const closeAddIngredientModal =
    document.getElementById("closeAddIngredientModal");

const cancelAddIngredient =
    document.getElementById("cancelAddIngredient");

const newIngredientName =
    document.getElementById("newIngredientName");

const newIngredientDescription =
    document.getElementById("newIngredientDescription");

const newIngredientUnit =
    document.getElementById("newIngredientUnit");

const newIngredientMinimum =
    document.getElementById("newIngredientMinimum");

const addIngredientError =
    document.getElementById("addIngredientError");

const saveNewIngredient =
    document.getElementById("saveNewIngredient");


/* =========================================================
   AUTH TOKEN
   ========================================================= */

function getAuthToken() {

    const possibleKeys = [
        "auth_token",
        "token",
        "api_token",
        "access_token"
    ];

    for (const key of possibleKeys) {

        const localValue =
            localStorage.getItem(key);

        if (
            localValue &&
            localValue.trim() !== ""
        ) {

            return localValue.trim();
        }


        const sessionValue =
            sessionStorage.getItem(key);

        if (
            sessionValue &&
            sessionValue.trim() !== ""
        ) {

            return sessionValue.trim();
        }
    }

    return null;
}


/* =========================================================
   API REQUEST
   ========================================================= */

async function apiRequest(
    endpoint,
    options = {}
) {

    const token =
        getAuthToken();


    if (!token) {

        throw new Error(
            "Admin authentication token was not found. Please sign in again."
        );
    }


    const headers = {

        "Accept":
            "application/json",

        "Authorization":
            `Bearer ${token}`

    };


    if (
        options.body &&
        !(options.body instanceof FormData)
    ) {

        headers["Content-Type"] =
            "application/json";
    }


    const response =
        await fetch(
            API_BASE_URL + endpoint,
            {
                ...options,

                headers: {
                    ...headers,
                    ...(options.headers || {})
                }
            }
        );


    let data;


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
        data.success === false
    ) {

        throw new Error(
            data.message ||
            `Request failed with status ${response.status}.`
        );
    }


    return data;
}


/* =========================================================
   PAGE STATE VISIBILITY
   ========================================================= */

/*
 * We explicitly control display as well as the hidden
 * attribute. This prevents inventory.css from accidentally
 * forcing the state messages to remain visible.
 */

function setStateVisibility(
    element,
    visible
) {

    if (!element) {
        return;
    }


    element.hidden =
        !visible;


    if (visible) {

        element.style.display =
            "";

    }
    else {

        element.style.display =
            "none";
    }
}


/* =========================================================
   PAGE STATES
   ========================================================= */

function showLoading() {

    setStateVisibility(
        inventoryLoading,
        true
    );

    setStateVisibility(
        inventoryError,
        false
    );

    setStateVisibility(
        inventoryEmpty,
        false
    );

    setStateVisibility(
        inventoryTableWrapper,
        false
    );
}


function showError(
    message
) {

    setStateVisibility(
        inventoryLoading,
        false
    );

    setStateVisibility(
        inventoryError,
        true
    );

    setStateVisibility(
        inventoryEmpty,
        false
    );

    setStateVisibility(
        inventoryTableWrapper,
        false
    );


    inventoryErrorMessage.textContent =
        message;
}


function showEmpty() {

    setStateVisibility(
        inventoryLoading,
        false
    );

    setStateVisibility(
        inventoryError,
        false
    );

    setStateVisibility(
        inventoryEmpty,
        true
    );

    setStateVisibility(
        inventoryTableWrapper,
        false
    );
}


function showTable() {

    setStateVisibility(
        inventoryLoading,
        false
    );

    setStateVisibility(
        inventoryError,
        false
    );

    setStateVisibility(
        inventoryEmpty,
        false
    );

    setStateVisibility(
        inventoryTableWrapper,
        true
    );
}


/* =========================================================
   LOAD INVENTORY
   ========================================================= */

async function loadInventory() {

    showLoading();


    systemStatus.textContent =
        "Loading latest inventory...";


    try {

        const data =
            await apiRequest(
                "get_ingredients.php"
            );


        if (
            !data.ingredients ||
            !Array.isArray(
                data.ingredients
            )
        ) {

            throw new Error(
                "Inventory response is missing ingredient data."
            );
        }


        inventoryData =
            data.ingredients;


        updateSummary();

        applyFilters();


        systemStatus.textContent =
            "Inventory loaded successfully.";

    }
    catch (error) {

        console.error(
            "Inventory loading error:",
            error
        );


        inventoryData = [];

        updateSummary();


        showError(
            error.message ||
            "Unable to load inventory."
        );


        inventoryCount.textContent =
            "0 ingredients";


        systemStatus.textContent =
            "Unable to load inventory.";
    }
}


/* =========================================================
   LOAD INVENTORY UNITS
   ========================================================= */

async function loadInventoryUnits() {

    newIngredientUnit.innerHTML = `
        <option value="">
            Loading units...
        </option>
    `;


    try {

        const data =
            await apiRequest(
                "get_inventory_units.php"
            );


        if (
            !data.units ||
            !Array.isArray(
                data.units
            )
        ) {

            throw new Error(
                "Inventory units were not returned by the server."
            );
        }


        newIngredientUnit.innerHTML = `
            <option value="">
                Select unit
            </option>
        `;


        data.units.forEach(
            unit => {

                const option =
                    document.createElement(
                        "option"
                    );


                option.value =
                    unit.unit_id;


                option.textContent =
                    `${unit.unit_name} (${unit.unit_code})`;


                newIngredientUnit.appendChild(
                    option
                );
            }
        );

    }
    catch (error) {

        console.error(
            "Inventory unit loading error:",
            error
        );


        newIngredientUnit.innerHTML = `
            <option value="">
                Unable to load units
            </option>
        `;


        addIngredientError.textContent =
            error.message ||
            "Unable to load inventory units.";

        addIngredientError.hidden =
            false;
    }
}


/* =========================================================
   UPDATE SUMMARY
   ========================================================= */

function updateSummary() {

    const total =
        inventoryData.length;


    const inStock =
        inventoryData.filter(
            ingredient =>
                ingredient.stock_status ===
                "IN_STOCK"
        ).length;


    const lowStock =
        inventoryData.filter(
            ingredient =>
                ingredient.stock_status ===
                "LOW_STOCK"
        ).length;


    const outOfStock =
        inventoryData.filter(
            ingredient =>
                ingredient.stock_status ===
                "OUT_OF_STOCK"
        ).length;


    totalIngredients.textContent =
        total;

    inStockIngredients.textContent =
        inStock;

    lowStockIngredients.textContent =
        lowStock;

    outOfStockIngredients.textContent =
        outOfStock;
}


/* =========================================================
   SEARCH + FILTER
   ========================================================= */

function applyFilters() {

    inventoryPage = 1;

    const searchTerm =
        inventorySearch.value
            .trim()
            .toLowerCase();


    const selectedStatus =
        stockStatusFilter.value;


    let filtered =
        [...inventoryData];


    if (
        searchTerm !== ""
    ) {

        filtered =
            filtered.filter(
                ingredient => {

                    const name =
                        (
                            ingredient.ingredient_name ||
                            ""
                        ).toLowerCase();


                    const unit =
                        (
                            ingredient.unit_name ||
                            ""
                        ).toLowerCase();


                    return (
                        name.includes(
                            searchTerm
                        ) ||
                        unit.includes(
                            searchTerm
                        )
                    );
                }
            );
    }


    if (
        selectedStatus !==
        "ALL"
    ) {

        filtered =
            filtered.filter(
                ingredient =>
                    ingredient.stock_status ===
                    selectedStatus
            );
    }


    renderInventory(
        filtered
    );
}


/* =========================================================
   RENDER INVENTORY
   ========================================================= */

function renderInventory(
    ingredients
) {

    inventoryTableBody.innerHTML =
        "";


    inventoryCount.textContent =
        `${ingredients.length} ingredient${
            ingredients.length === 1
                ? ""
                : "s"
        }`;


    if (
        ingredients.length === 0
    ) {

        AdminPagination.render(inventoryPagination, 0, 1, () => {});

        showEmpty();

        return;
    }

    inventoryPage = Math.min(
        inventoryPage,
        Math.max(1, Math.ceil(ingredients.length / AdminPagination.pageSize))
    );

    const visibleIngredients =
        AdminPagination.slice(ingredients, inventoryPage);


    for (
        const ingredient
        of visibleIngredients
    ) {

        const row =
            document.createElement(
                "tr"
            );


        /* -----------------------------------------------
           Ingredient
           ----------------------------------------------- */

        const ingredientCell =
            document.createElement(
                "td"
            );


        ingredientCell.innerHTML = `
            <strong>
                ${escapeHtml(
                    ingredient.ingredient_name
                )}
            </strong>
        `;


        /* -----------------------------------------------
           Unit
           ----------------------------------------------- */

        const unitCell =
            document.createElement(
                "td"
            );


        unitCell.textContent =
            ingredient.unit_code ||
            ingredient.unit_name ||
            "-";


        /* -----------------------------------------------
           Current Stock
           ----------------------------------------------- */

        const currentCell =
            document.createElement(
                "td"
            );


        currentCell.textContent =
            formatQuantity(
                ingredient.current_quantity
            );


        /* -----------------------------------------------
           Minimum Stock
           ----------------------------------------------- */

        const minimumCell =
            document.createElement(
                "td"
            );


        minimumCell.textContent =
            formatQuantity(
                ingredient.minimum_quantity
            );


        /* -----------------------------------------------
           Stock Status
           ----------------------------------------------- */

        const statusCell =
            document.createElement(
                "td"
            );


        statusCell.appendChild(
            createStockStatus(
                ingredient.stock_status
            )
        );


        /* -----------------------------------------------
           Availability
           ----------------------------------------------- */

        const availabilityCell =
            document.createElement(
                "td"
            );


        availabilityCell.appendChild(
            createAvailabilityBadge(
                ingredient.is_available
            )
        );


        /* -----------------------------------------------
           Actions
           ----------------------------------------------- */

        const actionCell =
            document.createElement(
                "td"
            );


        const actionButton =
            document.createElement(
                "button"
            );


        actionButton.type =
            "button";


        actionButton.className =
            "inventory-action-button";


        actionButton.textContent =
            "Manage";


        actionButton.addEventListener(
            "click",
            () => {

                openInventoryModal(
                    ingredient
                );

            }
        );


        actionCell.appendChild(
            actionButton
        );


        const deleteButton =
            actionButton.cloneNode(
                true
            );


        deleteButton.type =
            "button";


        deleteButton.textContent =
            "Delete";


        deleteButton.style.color =
            "#a83a2f";


        deleteButton.style.marginLeft =
            "8px";


        deleteButton.style.appearance =
            "none";


        deleteButton.style.webkitAppearance =
            "none";


        deleteButton.addEventListener(
            "click",
            () => {

                openDeleteIngredientModal(
                    ingredient
                );

            }
        );


        actionCell.appendChild(
            deleteButton
        );


        /* -----------------------------------------------
           Add cells to row
           ----------------------------------------------- */

        row.appendChild(
            ingredientCell
        );

        row.appendChild(
            unitCell
        );

        row.appendChild(
            currentCell
        );

        row.appendChild(
            minimumCell
        );

        row.appendChild(
            statusCell
        );

        row.appendChild(
            availabilityCell
        );

        row.appendChild(
            actionCell
        );


        inventoryTableBody.appendChild(
            row
        );
    }

    AdminPagination.render(
        inventoryPagination,
        ingredients.length,
        inventoryPage,
        page => {
            inventoryPage = page;
            renderInventory(ingredients);
        }
    );


    showTable();
}


/* =========================================================
   STOCK STATUS BADGE
   ========================================================= */

function createStockStatus(
    status
) {

    const badge =
        document.createElement(
            "span"
        );


    badge.className =
        "stock-status";


    switch (status) {

        case "IN_STOCK":

            badge.classList.add(
                "in-stock"
            );

            badge.textContent =
                "In Stock";

            break;


        case "LOW_STOCK":

            badge.classList.add(
                "low-stock"
            );

            badge.textContent =
                "Low Stock";

            break;


        default:

            badge.classList.add(
                "out-of-stock"
            );

            badge.textContent =
                "Out of Stock";

            break;
    }


    return badge;
}


/* =========================================================
   AVAILABILITY BADGE
   ========================================================= */

function createAvailabilityBadge(
    isAvailable
) {

    const badge =
        document.createElement(
            "span"
        );


    badge.className =
        "availability-badge";


    if (isAvailable) {

        badge.classList.add(
            "available"
        );

        badge.textContent =
            "Available";

    }
    else {

        badge.classList.add(
            "unavailable"
        );

        badge.textContent =
            "Unavailable";
    }


    return badge;
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
        !Number.isFinite(
            number
        )
    ) {

        return "0";
    }


    return number.toLocaleString(
        undefined,
        {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3
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
        .replaceAll(
            "&",
            "&amp;"
        )
        .replaceAll(
            "<",
            "&lt;"
        )
        .replaceAll(
            ">",
            "&gt;"
        )
        .replaceAll(
            '"',
            "&quot;"
        )
        .replaceAll(
            "'",
            "&#039;"
        );
}


/* =========================================================
   OPEN INVENTORY ACTION MODAL
   ========================================================= */

function openInventoryModal(
    ingredient
) {

    selectedIngredient =
        ingredient;


    selectedIngredientElement.textContent =
        `${ingredient.ingredient_name} — Current stock: ${formatQuantity(
            ingredient.current_quantity
        )} ${
            ingredient.unit_code || ""
        }`;


    inventoryUnitHelp.textContent =
        `Unit: ${
            ingredient.unit_name ||
            ingredient.unit_code ||
            "-"
        }`;


    inventoryAction.value =
        "STOCK_IN";


    inventoryQuantity.value =
        "";


    inventoryReason.value =
        "";


    inventoryModalError.hidden =
        true;


    inventoryModal.hidden =
        false;
}


/* =========================================================
   CLOSE INVENTORY ACTION MODAL
   ========================================================= */

function closeInventoryActionModal() {

    selectedIngredient =
        null;


    inventoryModal.hidden =
        true;
}


closeInventoryModal.addEventListener(
    "click",
    closeInventoryActionModal
);


cancelInventoryAction.addEventListener(
    "click",
    closeInventoryActionModal
);


/* =========================================================
   SAVE INVENTORY ACTION
   ========================================================= */

saveInventoryAction.addEventListener(
    "click",
    async () => {

        inventoryModalError.hidden =
            true;


        if (!selectedIngredient) {

            inventoryModalError.textContent =
                "Please select an ingredient.";

            inventoryModalError.hidden =
                false;

            return;
        }


        const action =
            inventoryAction.value;


        const quantity =
            Number(
                inventoryQuantity.value
            );


        const reason =
            inventoryReason.value.trim();


        if (
            action === "STOCK_IN" ||
            action === "STOCK_OUT" ||
            action === "WASTE"
        ) {

            if (
                !Number.isFinite(
                    quantity
                ) ||
                quantity <= 0
            ) {

                inventoryModalError.textContent =
                    "Enter a quantity greater than 0.";

                inventoryModalError.hidden =
                    false;

                return;
            }
        }


        if (
            action === "ADJUSTMENT"
        ) {

            if (
                !Number.isFinite(
                    quantity
                ) ||
                quantity < 0
            ) {

                inventoryModalError.textContent =
                    "Enter the actual physical quantity. It cannot be negative.";

                inventoryModalError.hidden =
                    false;

                return;
            }
        }


        if (
            reason === ""
        ) {

            inventoryModalError.textContent =
                "A reason is required.";

            inventoryModalError.hidden =
                false;

            return;
        }


        let endpoint =
            "";


        switch (action) {

            case "STOCK_IN":

                endpoint =
                    "stock_in.php";

                break;


            case "STOCK_OUT":

                endpoint =
                    "inventory_stock_out.php";

                break;


            case "WASTE":

                endpoint =
                    "inventory_waste.php";

                break;


            case "ADJUSTMENT":

                endpoint =
                    "inventory_adjustment.php";

                break;


            default:

                inventoryModalError.textContent =
                    "Invalid inventory action.";

                inventoryModalError.hidden =
                    false;

                return;
        }


        let requestBody;


        if (
            action === "ADJUSTMENT"
        ) {

            requestBody = {

                ingredient_id:
                    Number(
                        selectedIngredient.ingredient_id
                    ),

                actual_quantity:
                    quantity,

                reason:
                    reason
            };

        }
        else {

            requestBody = {

                ingredient_id:
                    Number(
                        selectedIngredient.ingredient_id
                    ),

                quantity:
                    quantity,

                reason:
                    reason
            };
        }


        saveInventoryAction.disabled =
            true;

        saveInventoryAction.textContent =
            "Saving...";


        try {

            const data =
                await apiRequest(
                    endpoint,
                    {
                        method:
                            "POST",

                        body:
                            JSON.stringify(
                                requestBody
                            )
                    }
                );


            closeInventoryActionModal();


            systemStatus.textContent =
                data.message ||
                "Inventory transaction completed successfully.";


            await loadInventory();

        }
        catch (error) {

            console.error(
                "Inventory transaction error:",
                error
            );


            inventoryModalError.textContent =
                error.message ||
                "Unable to complete inventory transaction.";


            inventoryModalError.hidden =
                false;

        }
        finally {

            saveInventoryAction.disabled =
                false;

            saveInventoryAction.textContent =
                "Save Changes";
        }

    }
);


/* =========================================================
   DELETE INGREDIENT
   ========================================================= */

function openDeleteIngredientModal(
    ingredient
) {

    const existingModal =
        document.getElementById(
            "deleteIngredientModal"
        );


    if (existingModal) {

        existingModal.remove();
    }


    const modal =
        document.createElement(
            "div"
        );


    modal.id =
        "deleteIngredientModal";


    modal.className =
        "modal-overlay";


    modal.innerHTML = `
        <div class="inventory-modal">

            <div class="modal-header">

                <div>

                    <h2>
                        Delete Ingredient
                    </h2>

                    <p>
                        This action cannot be undone.
                    </p>

                </div>

                <button
                    type="button"
                    class="modal-close"
                    id="closeDeleteIngredientModal"
                >
                    ×
                </button>

            </div>


            <div class="modal-body">

                <div class="selected-ingredient">

                    <strong>
                        ${escapeHtml(
                            ingredient.ingredient_name
                        )}
                    </strong>

                    <div
                        class="input-help"
                        style="margin-top: 6px;"
                    >
                        Current stock:
                        ${formatQuantity(
                            ingredient.current_quantity
                        )}
                        ${
                            ingredient.unit_code ||
                            ""
                        }
                    </div>

                </div>


                <p
                    style="
                        margin: 16px 0;
                        line-height: 1.6;
                        color: #555;
                    "
                >
                    Are you sure you want to permanently
                    delete this ingredient?
                </p>


                <div
                    class="modal-error"
                    id="deleteIngredientError"
                    hidden
                ></div>

            </div>


            <div class="modal-footer">

                <button
                    type="button"
                    class="secondary-button"
                    id="cancelDeleteIngredient"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="primary-button"
                    id="confirmDeleteIngredient"
                >
                    Delete Ingredient
                </button>

            </div>

        </div>
    `;


    document.body.appendChild(
        modal
    );


    const closeButton =
        document.getElementById(
            "closeDeleteIngredientModal"
        );


    const cancelButton =
        document.getElementById(
            "cancelDeleteIngredient"
        );


    const confirmButton =
        document.getElementById(
            "confirmDeleteIngredient"
        );


    const errorElement =
        document.getElementById(
            "deleteIngredientError"
        );


    function closeModal() {

        modal.remove();
    }


    closeButton.addEventListener(
        "click",
        closeModal
    );


    cancelButton.addEventListener(
        "click",
        closeModal
    );


    modal.addEventListener(
        "click",
        event => {

            if (
                event.target ===
                modal
            ) {

                closeModal();
            }

        }
    );


    confirmButton.addEventListener(
        "click",
        async () => {

            confirmButton.disabled =
                true;


            confirmButton.textContent =
                "Deleting...";


            errorElement.hidden =
                true;


            try {

                const data =
                    await apiRequest(
                        "delete_ingredient.php",
                        {
                            method:
                                "DELETE",

                            body:
                                JSON.stringify({
                                    ingredient_id:
                                        Number(
                                            ingredient.ingredient_id
                                        )
                                })
                        }
                    );


                closeModal();


                systemStatus.textContent =
                    data.message ||
                    "Ingredient deleted successfully.";


                await loadInventory();

            }
            catch (error) {

                console.error(
                    "Delete ingredient error:",
                    error
                );


                errorElement.textContent =
                    error.message ||
                    "Unable to delete ingredient.";


                errorElement.hidden =
                    false;


                confirmButton.disabled =
                    false;


                confirmButton.textContent =
                    "Delete Ingredient";
            }

        }
    );
}


/* =========================================================
   OPEN ADD INGREDIENT MODAL
   ========================================================= */

addIngredientButton.addEventListener(
    "click",
    async () => {

        addIngredientError.hidden =
            true;


        newIngredientName.value =
            "";

        newIngredientDescription.value =
            "";

        newIngredientMinimum.value =
            "0";


        newIngredientUnit.innerHTML = `
            <option value="">
                Loading units...
            </option>
        `;


        addIngredientModal.hidden =
            false;


        await loadInventoryUnits();

    }
);


/* =========================================================
   CLOSE ADD INGREDIENT MODAL
   ========================================================= */

function closeAddIngredient() {

    addIngredientModal.hidden =
        true;
}


closeAddIngredientModal.addEventListener(
    "click",
    closeAddIngredient
);


cancelAddIngredient.addEventListener(
    "click",
    closeAddIngredient
);


/* =========================================================
   CREATE NEW INGREDIENT
   ========================================================= */

saveNewIngredient.addEventListener(
    "click",
    async () => {

        addIngredientError.hidden =
            true;


        const ingredientName =
            newIngredientName.value.trim();


        const description =
            newIngredientDescription.value.trim();


        const unitId =
            Number(
                newIngredientUnit.value
            );


        const minimumQuantity =
            Number(
                newIngredientMinimum.value
            );


        if (
            ingredientName === ""
        ) {

            addIngredientError.textContent =
                "Ingredient name is required.";

            addIngredientError.hidden =
                false;

            return;
        }


        if (
            !Number.isInteger(
                unitId
            ) ||
            unitId <= 0
        ) {

            addIngredientError.textContent =
                "Please select an inventory unit.";

            addIngredientError.hidden =
                false;

            return;
        }


        if (
            !Number.isFinite(
                minimumQuantity
            ) ||
            minimumQuantity < 0
        ) {

            addIngredientError.textContent =
                "Minimum stock cannot be negative.";

            addIngredientError.hidden =
                false;

            return;
        }


        saveNewIngredient.disabled =
            true;


        saveNewIngredient.textContent =
            "Creating...";


        try {

            const data =
                await apiRequest(
                    "add_ingredient.php",
                    {
                        method:
                            "POST",

                        body:
                            JSON.stringify({

                                ingredient_name:
                                    ingredientName,

                                description:
                                    description,

                                unit_id:
                                    unitId,

                                minimum_quantity:
                                    minimumQuantity

                            })
                    }
                );


            closeAddIngredient();


            await loadInventory();


            systemStatus.textContent =
                data.message ||
                "Ingredient created successfully.";

        }
        catch (error) {

            console.error(
                "Add ingredient error:",
                error
            );


            addIngredientError.textContent =
                error.message ||
                "Unable to create ingredient.";


            addIngredientError.hidden =
                false;

        }
        finally {

            saveNewIngredient.disabled =
                false;


            saveNewIngredient.textContent =
                "Create Ingredient";
        }

    }
);


/* =========================================================
   MODAL BACKDROP
   ========================================================= */

inventoryModal.addEventListener(
    "click",
    event => {

        if (
            event.target ===
            inventoryModal
        ) {

            closeInventoryActionModal();
        }

    }
);


addIngredientModal.addEventListener(
    "click",
    event => {

        if (
            event.target ===
            addIngredientModal
        ) {

            closeAddIngredient();
        }

    }
);


/* =========================================================
   SEARCH / FILTER
   ========================================================= */

inventorySearch.addEventListener(
    "input",
    applyFilters
);


stockStatusFilter.addEventListener(
    "change",
    applyFilters
);


refreshInventory.addEventListener(
    "click",
    loadInventory
);


/* =========================================================
   INITIAL LOAD
   ========================================================= */

document.addEventListener(
    "DOMContentLoaded",
    () => {

        loadInventory();

    }
);
