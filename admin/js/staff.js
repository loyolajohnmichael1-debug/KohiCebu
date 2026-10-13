const GET_STAFF_API = "../api/get_staff.php";
const CREATE_STAFF_API = "../api/create_staff.php";
const UPDATE_STAFF_API = "../api/update_staff.php";
const UPDATE_STATUS_API = "../api/update_staff_status.php";

let staffList = [];
let confirmationAction = null;
let staffPage = 1;


/* =========================================================
   DOM ELEMENTS
========================================================= */

const staffTableBody = document.getElementById("staffTableBody");
const staffTableWrapper = document.getElementById("staffTableWrapper");

const staffLoading = document.getElementById("staffLoading");
const staffError = document.getElementById("staffError");
const staffErrorMessage = document.getElementById("staffErrorMessage");
const staffEmpty = document.getElementById("staffEmpty");

const staffSearch = document.getElementById("staffSearch");
const staffStatusFilter = document.getElementById("staffStatusFilter");

const totalStaff = document.getElementById("totalStaff");
const activeStaff = document.getElementById("activeStaff");
const inactiveStaff = document.getElementById("inactiveStaff");
const staffResultCount = document.getElementById("staffResultCount");
const staffPagination = document.getElementById("staffPagination");

const addStaffButton = document.getElementById("addStaffButton");
const refreshStaffButton = document.getElementById("refreshStaffButton");
const retryStaffButton = document.getElementById("retryStaffButton");

const addStaffModal = document.getElementById("addStaffModal");
const closeAddStaffModal = document.getElementById("closeAddStaffModal");
const cancelAddStaffButton = document.getElementById("cancelAddStaffButton");

const addStaffForm = document.getElementById("addStaffForm");
const addStaffMessage = document.getElementById("addStaffMessage");
const saveStaffButton = document.getElementById("saveStaffButton");

const editStaffModal = document.getElementById("editStaffModal");
const closeEditStaffModal = document.getElementById("closeEditStaffModal");
const cancelEditStaffButton = document.getElementById("cancelEditStaffButton");

const editStaffForm = document.getElementById("editStaffForm");
const editStaffMessage = document.getElementById("editStaffMessage");
const updateStaffButton = document.getElementById("updateStaffButton");

const confirmationModal = document.getElementById("confirmationModal");
const confirmationTitle = document.getElementById("confirmationTitle");
const confirmationMessage = document.getElementById("confirmationMessage");
const closeConfirmationModal = document.getElementById("closeConfirmationModal");
const cancelConfirmationButton = document.getElementById("cancelConfirmationButton");
const confirmActionButton = document.getElementById("confirmActionButton");

/* =========================================================
   STAFF NOTIFICATION ELEMENTS
========================================================= */

const staffNotification = document.getElementById("staffNotification");
const notificationIcon = document.getElementById("notificationIcon");
const notificationTitle = document.getElementById("notificationTitle");
const notificationMessage = document.getElementById("notificationMessage");
const closeStaffNotification = document.getElementById("closeStaffNotification");

let notificationTimeout = null;


/* =========================================================
   AUTH
========================================================= */

function getAuthHeaders() {

    const token = sessionStorage.getItem("token");

    return {
        "Content-Type": "application/json",
        "Authorization": "Bearer " + token
    };
}


/* =========================================================
   HTML ESCAPE
========================================================= */

function escapeHtml(value) {

    if (value === null || value === undefined) {
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
   STAFF NOTIFICATION
========================================================= */

function showStaffNotification(
    title,
    message,
    type = "success"
) {

    if (!staffNotification) {
        return;
    }

    if (notificationTimeout) {

        clearTimeout(notificationTimeout);

        notificationTimeout = null;
    }


    notificationTitle.textContent = title;
    notificationMessage.textContent = message;


    if (type === "error") {

        staffNotification.className =
            "staff-notification error";

        notificationIcon.textContent = "!";

    } else {

        staffNotification.className =
            "staff-notification success";

        notificationIcon.textContent = "✓";
    }


    staffNotification.hidden = false;


    notificationTimeout = setTimeout(
        hideStaffNotification,
        4000
    );
}


function hideStaffNotification() {

    if (!staffNotification) {
        return;
    }

    staffNotification.hidden = true;

    if (notificationTimeout) {

        clearTimeout(notificationTimeout);

        notificationTimeout = null;
    }
}


if (closeStaffNotification) {

    closeStaffNotification.addEventListener(
        "click",
        hideStaffNotification
    );
}


/* =========================================================
   LOADING / STATES
========================================================= */

function showLoading() {

    staffLoading.hidden = false;
    staffError.hidden = true;
    staffEmpty.hidden = true;
    staffTableWrapper.hidden = true;
}


function hideLoading() {

    staffLoading.hidden = true;
}


function showError(message) {

    staffLoading.hidden = true;
    staffError.hidden = false;
    staffEmpty.hidden = true;
    staffTableWrapper.hidden = true;

    staffErrorMessage.textContent =
        message || "Unable to load staff accounts.";
}


function hideError() {

    staffError.hidden = true;
}


function showEmpty() {

    staffLoading.hidden = true;
    staffError.hidden = true;
    staffEmpty.hidden = false;
    staffTableWrapper.hidden = true;
}


function showTable() {

    staffLoading.hidden = true;
    staffError.hidden = true;
    staffEmpty.hidden = true;
    staffTableWrapper.hidden = false;
}


/* =========================================================
   LOAD STAFF
========================================================= */

async function loadStaff() {

    showLoading();

    try {

        const response = await fetch(
            GET_STAFF_API,
            {
                method: "GET",
                headers: getAuthHeaders()
            }
        );

        const data = await response.json();

        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Unable to retrieve staff accounts."
            );
        }

        staffList = Array.isArray(data.staff)
            ? data.staff
            : [];

        updateSummary();
        renderStaff();

    } catch (error) {

        console.error(
            "Load staff error:",
            error
        );

        showError(
            error.message ||
            "Unable to load staff accounts."
        );
    }
}


/* =========================================================
   SUMMARY
========================================================= */

function updateSummary() {

    const total = staffList.length;

    const active = staffList.filter(
        staff => staff.is_active === true
    ).length;

    const inactive = total - active;

    totalStaff.textContent = total;
    activeStaff.textContent = active;
    inactiveStaff.textContent = inactive;
}


/* =========================================================
   FILTER STAFF
========================================================= */

function getFilteredStaff() {

    const searchTerm =
        staffSearch.value
            .trim()
            .toLowerCase();

    const statusFilter =
        staffStatusFilter.value;

    return staffList.filter(staff => {

        const matchesSearch =
            !searchTerm ||
            String(staff.full_name || "")
                .toLowerCase()
                .includes(searchTerm) ||
            String(staff.email || "")
                .toLowerCase()
                .includes(searchTerm) ||
            String(staff.phone || "")
                .toLowerCase()
                .includes(searchTerm);

        const matchesStatus =
            statusFilter === "ALL" ||
            (
                statusFilter === "ACTIVE" &&
                staff.is_active === true
            ) ||
            (
                statusFilter === "INACTIVE" &&
                staff.is_active === false
            );

        return matchesSearch && matchesStatus;
    });
}


/* =========================================================
   RENDER STAFF
========================================================= */

function renderStaff() {

    const filteredStaff = getFilteredStaff();

    staffTableBody.innerHTML = "";

    staffResultCount.textContent =
        `${filteredStaff.length} staff member${filteredStaff.length === 1 ? "" : "s"}`;


    if (filteredStaff.length === 0) {

        AdminPagination.render(staffPagination, 0, 1, () => {});

        showEmpty();

        return;
    }


    showTable();

    staffPage = Math.min(
        staffPage,
        Math.max(1, Math.ceil(filteredStaff.length / AdminPagination.pageSize))
    );

    const visibleStaff = AdminPagination.slice(filteredStaff, staffPage);


    visibleStaff.forEach(staff => {

        const row = document.createElement("tr");

        const statusClass =
            staff.is_active
                ? "status-active"
                : "status-inactive";

        const statusText =
            staff.is_active
                ? "ACTIVE"
                : "INACTIVE";


        const actionButton =
            staff.is_active
                ? `
                    <button
                        type="button"
                        class="staff-action deactivate"
                        data-action="deactivate"
                        data-id="${staff.user_id}"
                    >
                        Deactivate
                    </button>
                `
                : `
                    <button
                        type="button"
                        class="staff-action activate"
                        data-action="activate"
                        data-id="${staff.user_id}"
                    >
                        Activate
                    </button>
                `;


        row.innerHTML = `

            <td>
                <div class="staff-name">
                    ${escapeHtml(staff.full_name)}
                </div>
            </td>

            <td>
                ${escapeHtml(staff.email)}
            </td>

            <td>
                ${escapeHtml(staff.phone || "—")}
            </td>

            <td>
                ${escapeHtml(staff.address || "—")}
            </td>

            <td>
                <span class="role-badge">
                    ${escapeHtml(staff.role)}
                </span>
            </td>

            <td>
                <span class="status-badge ${statusClass}">
                    ${statusText}
                </span>
            </td>

            <td>

                <div class="staff-actions">

                    <button
                        type="button"
                        class="staff-action edit"
                        data-action="edit"
                        data-id="${staff.user_id}"
                    >
                        Edit
                    </button>

                    ${actionButton}

                </div>

            </td>
        `;

        staffTableBody.appendChild(row);
    });

    AdminPagination.render(
        staffPagination,
        filteredStaff.length,
        staffPage,
        page => {
            staffPage = page;
            renderStaff();
        }
    );
}


/* =========================================================
   CONFIRMATION MODAL
========================================================= */

function openConfirmationModal(
    title,
    message,
    action,
    confirmText = "Confirm"
) {

    confirmationTitle.textContent = title;

    confirmationMessage.textContent = message;

    confirmActionButton.textContent = confirmText;

    confirmationAction = action;

    confirmationModal.hidden = false;
}


function closeConfirmationModalUI() {

    confirmationModal.hidden = true;

    confirmationAction = null;

    confirmActionButton.disabled = false;

    confirmActionButton.textContent = "Confirm";
}


/* =========================================================
   CONFIRM ACTION
========================================================= */

confirmActionButton.addEventListener(
    "click",
    async function () {

        if (!confirmationAction) {
            return;
        }


        const action =
            confirmationAction;


        confirmationAction = null;

        confirmActionButton.disabled = true;


        try {

            await action();

        } finally {

            confirmationModal.hidden = true;

            confirmActionButton.disabled = false;

            confirmActionButton.textContent = "Confirm";
        }
    }
);


/* =========================================================
   CLOSE CONFIRMATION
========================================================= */

closeConfirmationModal.addEventListener(
    "click",
    closeConfirmationModalUI
);

cancelConfirmationButton.addEventListener(
    "click",
    closeConfirmationModalUI
);


/* =========================================================
   OPEN ADD STAFF
========================================================= */

addStaffButton.addEventListener(
    "click",
    function () {

        addStaffForm.reset();

        addStaffMessage.hidden = true;

        addStaffMessage.textContent = "";

        addStaffModal.hidden = false;
    }
);


/* =========================================================
   CLOSE ADD STAFF
========================================================= */

function closeAddStaffModalUI() {

    addStaffModal.hidden = true;

    addStaffForm.reset();

    addStaffMessage.hidden = true;

    addStaffMessage.textContent = "";
}


closeAddStaffModal.addEventListener(
    "click",
    closeAddStaffModalUI
);

cancelAddStaffButton.addEventListener(
    "click",
    closeAddStaffModalUI
);


/* =========================================================
   ADD STAFF
========================================================= */

addStaffForm.addEventListener(
    "submit",
    async function (event) {

        event.preventDefault();

        addStaffMessage.hidden = true;


        const fullName =
            document.getElementById("staffFullName")
                .value
                .trim();


        const email =
            document.getElementById("staffEmail")
                .value
                .trim();


        const phone =
            document.getElementById("staffPhone")
                .value
                .trim();


        const address =
            document.getElementById("staffAddress")
                .value
                .trim();


        const password =
            document.getElementById("staffPassword")
                .value;


        if (!fullName || !email || !password) {

            showAddStaffMessage(
                "Full name, email, and password are required.",
                "error"
            );

            return;
        }


        if (password.length < 8) {

            showAddStaffMessage(
                "Password must be at least 8 characters.",
                "error"
            );

            return;
        }


        saveStaffButton.disabled = true;

        saveStaffButton.textContent = "Creating...";


        try {

            const response = await fetch(
                CREATE_STAFF_API,
                {
                    method: "POST",
                    headers: getAuthHeaders(),
                    body: JSON.stringify({
                        full_name: fullName,
                        email: email,
                        phone: phone,
                        address: address,
                        password: password
                    })
                }
            );


            const data =
                await response.json();


            if (!response.ok || !data.success) {

                throw new Error(
                    data.message ||
                    "Unable to create staff account."
                );
            }


            addStaffMessage.className =
                "form-message success";


            addStaffMessage.textContent =
                data.message ||
                "Staff account created successfully.";


            addStaffMessage.hidden = false;


            addStaffForm.reset();


            await loadStaff();


            setTimeout(
                closeAddStaffModalUI,
                700
            );

        } catch (error) {

            console.error(
                "Create staff error:",
                error
            );


            showAddStaffMessage(
                error.message ||
                "Unable to create staff account.",
                "error"
            );

        } finally {

            saveStaffButton.disabled = false;

            saveStaffButton.textContent =
                "Create Staff";
        }
    }
);


/* =========================================================
   SHOW ADD STAFF MESSAGE
========================================================= */

function showAddStaffMessage(
    message,
    type = "error"
) {

    addStaffMessage.hidden = false;

    addStaffMessage.textContent = message;

    addStaffMessage.className =
        "form-message " + type;
}


/* =========================================================
   OPEN EDIT STAFF
========================================================= */

function openEditStaff(staff) {

    document.getElementById("editStaffId").value =
        staff.user_id;


    document.getElementById("editStaffFullName").value =
        staff.full_name || "";


    document.getElementById("editStaffEmail").value =
        staff.email || "";


    document.getElementById("editStaffPhone").value =
        staff.phone || "";


    document.getElementById("editStaffAddress").value =
        staff.address || "";


    editStaffMessage.hidden = true;

    editStaffMessage.textContent = "";

    editStaffModal.hidden = false;
}


/* =========================================================
   CLOSE EDIT STAFF
========================================================= */

function closeEditStaffModalUI() {

    editStaffModal.hidden = true;

    editStaffForm.reset();

    editStaffMessage.hidden = true;

    editStaffMessage.textContent = "";
}


closeEditStaffModal.addEventListener(
    "click",
    closeEditStaffModalUI
);

cancelEditStaffButton.addEventListener(
    "click",
    closeEditStaffModalUI
);


/* =========================================================
   EDIT STAFF SUBMIT
========================================================= */

editStaffForm.addEventListener(
    "submit",
    function (event) {

        event.preventDefault();


        const userId =
            document.getElementById("editStaffId")
                .value;


        const fullName =
            document.getElementById("editStaffFullName")
                .value
                .trim();


        const email =
            document.getElementById("editStaffEmail")
                .value
                .trim();


        const phone =
            document.getElementById("editStaffPhone")
                .value
                .trim();


        const address =
            document.getElementById("editStaffAddress")
                .value
                .trim();


        if (!userId || !fullName || !email) {

            showEditStaffMessage(
                "Full name and email are required.",
                "error"
            );

            return;
        }


        openConfirmationModal(
            "Confirm Staff Update",

            "Are you sure you want to save these changes?",

            () => performUpdateStaff(
                userId,
                fullName,
                email,
                phone,
                address
            ),

            "Save Changes"
        );
    }
);


/* =========================================================
   PERFORM STAFF UPDATE
========================================================= */

async function performUpdateStaff(
    userId,
    fullName,
    email,
    phone,
    address
) {

    updateStaffButton.disabled = true;

    updateStaffButton.textContent = "Saving...";


    try {

        const response = await fetch(
            UPDATE_STAFF_API,
            {
                method: "PUT",
                headers: getAuthHeaders(),
                body: JSON.stringify({
                    user_id: Number(userId),
                    full_name: fullName,
                    email: email,
                    phone: phone,
                    address: address
                })
            }
        );


        const data =
            await response.json();


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Unable to update staff account."
            );
        }


        editStaffMessage.className =
            "form-message success";


        editStaffMessage.textContent =
            data.message ||
            "Staff account updated successfully.";


        editStaffMessage.hidden = false;


        await loadStaff();


        setTimeout(
            closeEditStaffModalUI,
            700
        );

    } catch (error) {

        console.error(
            "Update staff error:",
            error
        );


        showEditStaffMessage(
            error.message ||
            "Unable to update staff account.",
            "error"
        );

    } finally {

        updateStaffButton.disabled = false;

        updateStaffButton.textContent =
            "Save Changes";
    }
}


/* =========================================================
   SHOW EDIT STAFF MESSAGE
========================================================= */

function showEditStaffMessage(
    message,
    type = "error"
) {

    editStaffMessage.hidden = false;

    editStaffMessage.textContent = message;

    editStaffMessage.className =
        "form-message " + type;
}


/* =========================================================
   UPDATE STAFF STATUS
========================================================= */

function changeStaffStatus(
    userId,
    isActive
) {

    openConfirmationModal(

        isActive
            ? "Activate Staff Account"
            : "Deactivate Staff Account",

        isActive
            ? "Are you sure you want to activate this staff account?"
            : "Are you sure you want to deactivate this staff account?",

        () => performStatusUpdate(
            userId,
            isActive
        ),

        isActive
            ? "Activate"
            : "Deactivate"
    );
}


/* =========================================================
   PERFORM STATUS UPDATE
========================================================= */

async function performStatusUpdate(
    userId,
    isActive
) {

    try {

        const response = await fetch(
            UPDATE_STATUS_API,
            {
                method: "PUT",
                headers: getAuthHeaders(),
                body: JSON.stringify({
                    user_id: Number(userId),
                    is_active: isActive
                })
            }
        );


        /*
         * Read response as text first.
         * This prevents JSON parsing errors when PHP
         * returns an HTML warning/error.
         */

        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(responseText);

        } catch (parseError) {

            console.error(
                "Invalid JSON returned by update_staff_status.php:",
                responseText
            );

            throw new Error(
                "The server returned an invalid response. Please check the browser console for the PHP error."
            );
        }


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Unable to update staff status."
            );
        }


        /* =====================================================
           REFRESH STAFF LIST
        ===================================================== */

        await loadStaff();


        /* =====================================================
           SHOW SUCCESS NOTIFICATION
        ===================================================== */

        showStaffNotification(

            isActive
                ? "Staff Account Activated"
                : "Staff Account Deactivated",

            data.message ||
            (
                isActive
                    ? "Staff account activated successfully."
                    : "Staff account deactivated successfully."
            ),

            "success"
        );

    } catch (error) {

        console.error(
            "Update staff status error:",
            error
        );


        /* =====================================================
           SHOW ERROR NOTIFICATION
        ===================================================== */

        showStaffNotification(
            "Action Failed",

            error.message ||
            "Unable to update staff status.",

            "error"
        );
    }
}


/* =========================================================
   TABLE ACTIONS
========================================================= */

staffTableBody.addEventListener(
    "click",
    function (event) {

        const button =
            event.target.closest(
                "button[data-action]"
            );


        if (!button) {
            return;
        }


        const action =
            button.dataset.action;


        const userId =
            Number(button.dataset.id);


        const staff =
            staffList.find(
                item =>
                    Number(item.user_id) === userId
            );


        if (!staff) {
            return;
        }


        if (action === "edit") {

            openEditStaff(staff);

            return;
        }


        if (action === "activate") {

            changeStaffStatus(
                userId,
                true
            );

            return;
        }


        if (action === "deactivate") {

            changeStaffStatus(
                userId,
                false
            );

            return;
        }
    }
);


/* =========================================================
   SEARCH / FILTER
========================================================= */

staffSearch.addEventListener(
    "input",
    () => {
        staffPage = 1;
        renderStaff();
    }
);

staffStatusFilter.addEventListener(
    "change",
    () => {
        staffPage = 1;
        renderStaff();
    }
);


/* =========================================================
   REFRESH
========================================================= */

refreshStaffButton.addEventListener(
    "click",
    loadStaff
);

retryStaffButton.addEventListener(
    "click",
    loadStaff
);


/* =========================================================
   CLOSE MODALS WHEN CLICKING OVERLAY
========================================================= */

addStaffModal.addEventListener(
    "click",
    function (event) {

        if (event.target === addStaffModal) {

            closeAddStaffModalUI();
        }
    }
);


editStaffModal.addEventListener(
    "click",
    function (event) {

        if (event.target === editStaffModal) {

            closeEditStaffModalUI();
        }
    }
);


confirmationModal.addEventListener(
    "click",
    function (event) {

        if (event.target === confirmationModal) {

            closeConfirmationModalUI();
        }
    }
);


/* =========================================================
   INITIAL LOAD
========================================================= */

loadStaff();
