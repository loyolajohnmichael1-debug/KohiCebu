/*
 * =========================================================
 * KohiCebu Admin Portal
 *
 * Promotions & Website Updates
 *
 * Handles:
 *
 * - Loading website updates
 * - Summary statistics
 * - Type filtering
 * - Status filtering
 * - Refreshing data
 * - Add / Edit modal
 * - Creating website updates
 * - Editing website updates
 * - Deleting website updates
 * - Image upload
 * - Session authentication
 *
 * =========================================================
 */

document.addEventListener("DOMContentLoaded", function () {


    /*
     * =====================================================
     * CONFIGURATION
     * =====================================================
     */

    const API_BASE = "../api/";

    const GET_UPDATES_API =
        API_BASE + "get_admin_website_updates.php";

    const CREATE_UPDATE_API =
        API_BASE + "create_website_update.php";

    const UPDATE_UPDATE_API =
        API_BASE + "update_website_update.php";

    const DELETE_UPDATE_API =
        API_BASE + "delete_website_update.php";


    /*
     * =====================================================
     * ADD / EDIT ELEMENTS
     * =====================================================
     */

    const addUpdateButton =
        document.getElementById("addUpdateButton");

    const refreshUpdatesButton =
        document.getElementById("refreshUpdatesButton");

    const updateModal =
        document.getElementById("updateModal");

    const closeUpdateModal =
        document.getElementById("closeUpdateModal");

    const cancelUpdateButton =
        document.getElementById("cancelUpdateButton");

    const updateForm =
        document.getElementById("updateForm");

    const updateModalTitle =
        document.getElementById("updateModalTitle");

    const updateId =
        document.getElementById("updateId");

    const updateType =
        document.getElementById("updateType");

    const updateTitle =
        document.getElementById("updateTitle");

    const updateDescription =
        document.getElementById("updateDescription");

    const updateImage =
        document.getElementById("updateImage");

    const updateStartDate =
        document.getElementById("updateStartDate");

    const updateEndDate =
        document.getElementById("updateEndDate");

    const updatePublished =
        document.getElementById("updatePublished");

    const updateFormMessage =
        document.getElementById("updateFormMessage");

    const saveUpdateButton =
        document.getElementById("saveUpdateButton");


    /*
     * =====================================================
     * TABLE ELEMENTS
     * =====================================================
     */

    const updatesTableBody =
        document.getElementById("updatesTableBody");

    const updatesPagination =
        document.getElementById("updatesPagination");

    let updatesPage = 1;

    const totalUpdates =
        document.getElementById("totalUpdates");

    const publishedUpdates =
        document.getElementById("publishedUpdates");

    const draftUpdates =
        document.getElementById("draftUpdates");

    const activeUpdates =
        document.getElementById("activeUpdates");

    const updateTypeFilter =
        document.getElementById("updateTypeFilter");

    const updateStatusFilter =
        document.getElementById("updateStatusFilter");


    /*
     * =====================================================
     * DELETE MODAL ELEMENTS
     * =====================================================
     */

    const deleteUpdateModal =
        document.getElementById(
            "deleteUpdateModal"
        );

    const closeDeleteUpdateModal =
        document.getElementById(
            "closeDeleteUpdateModal"
        );

    const cancelDeleteUpdateButton =
        document.getElementById(
            "cancelDeleteUpdateButton"
        );

    const confirmDeleteUpdateButton =
        document.getElementById(
            "confirmDeleteUpdateButton"
        );

    const deleteUpdateTitle =
        document.getElementById(
            "deleteUpdateTitle"
        );

    const deleteUpdateMessage =
        document.getElementById(
            "deleteUpdateMessage"
        );


    /*
     * =====================================================
     * DATA
     * =====================================================
     */

    let updates = [];

    let selectedDeleteUpdateId = null;


    /*
     * =====================================================
     * SESSION
     * =====================================================
     */

    function getAdminSession() {

        return {

            token:
                sessionStorage.getItem("token"),

            role:
                sessionStorage.getItem("role"),

            fullName:
                sessionStorage.getItem("full_name"),

            expiresAt:
                sessionStorage.getItem("token_expiry")

        };

    }


    /*
     * =====================================================
     * AUTHORIZATION HEADERS
     * =====================================================
     */

    function getAuthHeaders() {

        const session =
            getAdminSession();

        const headers = {
            "Accept":
                "application/json"
        };


        if (session.token) {

            headers["Authorization"] =
                "Bearer " + session.token;

        }


        return headers;

    }


    /*
     * =====================================================
     * HANDLE UNAUTHORIZED
     * =====================================================
     */

    function handleUnauthorized() {

        sessionStorage.removeItem("token");

        sessionStorage.removeItem("role");

        sessionStorage.removeItem("full_name");

        sessionStorage.removeItem("token_expiry");


        alert(
            "Your session has expired. Please log in again."
        );


        window.location.href =
            "login.php";

    }


    /*
     * =====================================================
     * HTML ESCAPE
     * =====================================================
     */

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


    /*
     * =====================================================
     * FORMAT UPDATE TYPE
     * =====================================================
     */

    function formatUpdateType(type) {

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
                return type || "—";

        }

    }


    /*
     * =====================================================
     * FORMAT DATE
     * =====================================================
     */

    function formatDate(dateValue) {

        if (!dateValue) {

            return "—";

        }


        const parts =
            String(dateValue).split("-");


        if (parts.length !== 3) {

            return escapeHtml(dateValue);

        }


        const year =
            Number(parts[0]);

        const month =
            Number(parts[1]);

        const day =
            Number(parts[2]);


        const date =
            new Date(
                year,
                month - 1,
                day
            );


        if (
            Number.isNaN(
                date.getTime()
            )
        ) {

            return escapeHtml(dateValue);

        }


        return date.toLocaleDateString(
            "en-US",
            {
                month: "short",
                day: "numeric",
                year: "numeric"
            }
        );

    }


    /*
     * =====================================================
     * FORMAT SCHEDULE
     * =====================================================
     */

    function formatSchedule(update) {

        const startDate =
            update.start_date;

        const endDate =
            update.end_date;


        if (
            !startDate &&
            !endDate
        ) {

            return "No schedule";

        }


        if (
            startDate &&
            !endDate
        ) {

            return (
                "From " +
                formatDate(startDate)
            );

        }


        if (
            !startDate &&
            endDate
        ) {

            return (
                "Until " +
                formatDate(endDate)
            );

        }


        return (
            formatDate(startDate) +
            " → " +
            formatDate(endDate)
        );

    }


    /*
     * =====================================================
     * STATUS LABEL
     * =====================================================
     */

    function formatStatus(status) {

        switch (status) {

            case "ACTIVE":
                return "ACTIVE";

            case "SCHEDULED":
                return "SCHEDULED";

            case "EXPIRED":
                return "EXPIRED";

            case "DRAFT":
                return "DRAFT";

            default:
                return status || "UNKNOWN";

        }

    }


    /*
     * =====================================================
     * STATUS CLASS
     * =====================================================
     */

    function getStatusClass(status) {

        switch (status) {

            case "ACTIVE":
                return "status-active";

            case "SCHEDULED":
                return "status-scheduled";

            case "EXPIRED":
                return "status-expired";

            case "DRAFT":
                return "status-draft";

            default:
                return "status-unknown";

        }

    }


    /*
     * =====================================================
     * LOAD UPDATES
     * =====================================================
     */

    async function loadUpdates() {

        if (updatesTableBody) {

            updatesTableBody.innerHTML = `
                <tr>
                    <td
                        colspan="5"
                        class="table-loading"
                    >
                        Loading updates...
                    </td>
                </tr>
            `;

        }


        try {

            const response =
                await fetch(
                    GET_UPDATES_API,
                    {
                        method: "GET",
                        headers:
                            getAuthHeaders()
                    }
                );


            if (
                response.status === 401
            ) {

                handleUnauthorized();

                return;

            }


            if (
                response.status === 403
            ) {

                if (updatesTableBody) {

                    updatesTableBody.innerHTML = `
                        <tr>
                            <td
                                colspan="5"
                                class="table-loading"
                            >
                                You do not have permission to view these updates.
                            </td>
                        </tr>
                    `;

                }

                return;

            }


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "Failed to load website updates."
                );

            }


            updates =
                Array.isArray(data.updates)
                    ? data.updates
                    : [];


            updateSummary(
                data.summary
            );


            renderUpdates();


        } catch (error) {

            console.error(
                "Failed to load website updates:",
                error
            );


            if (updatesTableBody) {

                updatesTableBody.innerHTML = `
                    <tr>
                        <td
                            colspan="5"
                            class="table-loading"
                        >
                            Failed to load updates.
                            Please try again.
                        </td>
                    </tr>
                `;

            }

        }

    }


    /*
     * =====================================================
     * UPDATE SUMMARY
     * =====================================================
     */

    function updateSummary(summary) {

        if (!summary) {

            return;

        }


        if (totalUpdates) {

            totalUpdates.textContent =
                summary.total ?? 0;

        }


        if (publishedUpdates) {

            publishedUpdates.textContent =
                summary.published ?? 0;

        }


        if (draftUpdates) {

            draftUpdates.textContent =
                summary.drafts ?? 0;

        }


        if (activeUpdates) {

            activeUpdates.textContent =
                summary.active ?? 0;

        }

    }


    /*
     * =====================================================
     * GET FILTERED UPDATES
     * =====================================================
     */

    function getFilteredUpdates() {

        const selectedType =
            updateTypeFilter
                ? updateTypeFilter.value
                : "";


        const selectedStatus =
            updateStatusFilter
                ? updateStatusFilter.value
                : "";


        return updates.filter(
            function (update) {

                if (
                    selectedType !== "" &&
                    update.update_type !== selectedType
                ) {

                    return false;

                }


                if (
                    selectedStatus !== ""
                ) {

                    const updateStatus =
                        String(
                            update.status || ""
                        ).toLowerCase();


                    if (
                        updateStatus !==
                        selectedStatus.toLowerCase()
                    ) {

                        return false;

                    }

                }


                return true;

            }
        );

    }


    /*
     * =====================================================
     * RENDER TABLE
     * =====================================================
     */

    function renderUpdates() {

        if (!updatesTableBody) {

            return;

        }


        const filteredUpdates =
            getFilteredUpdates();


        if (
            filteredUpdates.length === 0
        ) {

            AdminPagination.render(updatesPagination, 0, 1, function () {});

            updatesTableBody.innerHTML = `
                <tr>
                    <td
                        colspan="5"
                        class="table-loading"
                    >
                        No updates found.
                    </td>
                </tr>
            `;

            return;

        }

        updatesPage = Math.min(
            updatesPage,
            Math.max(1, Math.ceil(filteredUpdates.length / AdminPagination.pageSize))
        );

        const visibleUpdates =
            AdminPagination.slice(filteredUpdates, updatesPage);


        updatesTableBody.innerHTML =
            visibleUpdates.map(
                function (update) {

                    const title =
                        escapeHtml(
                            update.title
                        );


                    const description =
                        escapeHtml(
                            update.description
                        );


                    const type =
                        escapeHtml(
                            formatUpdateType(
                                update.update_type
                            )
                        );


                    const schedule =
                        formatSchedule(
                            update
                        );


                    const status =
                        formatStatus(
                            update.status
                        );


                    const statusClass =
                        getStatusClass(
                            update.status
                        );


                    return `
                        <tr>

                            <td>

                                <div
                                    class="update-table-title"
                                >
                                    ${title}
                                </div>

                                <div
                                    class="update-table-description"
                                >
                                    ${description}
                                </div>

                            </td>


                            <td>

                                <span
                                    class="update-type"
                                >
                                    ${type}
                                </span>

                            </td>


                            <td>

                                <div
                                    class="update-schedule"
                                >
                                    ${schedule}
                                </div>

                            </td>


                            <td>

                                <span
                                    class="update-status ${statusClass}"
                                >
                                    ${status}
                                </span>

                            </td>


                            <td>

                                <div class="table-actions">

                                    <button
                                        type="button"
                                        class="table-action-button"
                                        data-update-id="${escapeHtml(update.update_id)}"
                                        data-action="edit"
                                    >
                                        EDIT
                                    </button>

                                    <button
                                        type="button"
                                        class="table-action-button table-action-button--danger"
                                        data-update-id="${escapeHtml(update.update_id)}"
                                        data-action="delete"
                                    >
                                        DELETE
                                    </button>

                                </div>

                            </td>

                        </tr>
                    `;

                }
            ).join("");


        updatesTableBody
            .querySelectorAll(
                ".table-action-button"
            )
            .forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        function () {

                            const id =
                                this.dataset.updateId;

                            const action =
                                this.dataset.action;


                            if (
                                action === "edit"
                            ) {

                                openEditModal(id);

                                return;

                            }


                            if (
                                action === "delete"
                            ) {

                                openDeleteUpdateModal(id);

                            }

                        }
                    );

                }
            );

        AdminPagination.render(
            updatesPagination,
            filteredUpdates.length,
            updatesPage,
            function (page) {
                updatesPage = page;
                renderUpdates();
            }
        );

    }


    /*
     * =====================================================
     * OPEN ADD MODAL
     * =====================================================
     */

    function openUpdateModal() {

        if (!updateModal) {

            return;

        }


        if (updateForm) {

            updateForm.reset();

        }


        if (updateId) {

            updateId.value = "";

        }


        if (updateModalTitle) {

            updateModalTitle.textContent =
                "Add Update";

        }


        if (saveUpdateButton) {

            saveUpdateButton.textContent =
                "SAVE UPDATE";

            saveUpdateButton.disabled =
                false;

        }


        if (updateFormMessage) {

            updateFormMessage.textContent =
                "";

            updateFormMessage.hidden =
                true;

        }


        updateModal.hidden =
            false;


        document.body.classList.add(
            "modal-open"
        );


        if (updateType) {

            setTimeout(
                function () {

                    updateType.focus();

                },
                50
            );

        }

    }


    /*
     * =====================================================
     * OPEN EDIT MODAL
     * =====================================================
     */

    function openEditModal(id) {

        const update =
            updates.find(
                function (item) {

                    return String(
                        item.update_id
                    ) === String(id);

                }
            );


        if (!update) {

            return;

        }


        if (updateForm) {

            updateForm.reset();

        }


        if (updateId) {

            updateId.value =
                update.update_id;

        }


        if (updateType) {

            updateType.value =
                update.update_type || "";

        }


        if (updateTitle) {

            updateTitle.value =
                update.title || "";

        }


        if (updateDescription) {

            updateDescription.value =
                update.description || "";

        }


        if (updateStartDate) {

            updateStartDate.value =
                update.start_date || "";

        }


        if (updateEndDate) {

            updateEndDate.value =
                update.end_date || "";

        }


        if (updatePublished) {

            updatePublished.checked =
                update.is_published === true ||
                update.is_published === "t";

        }


        if (updateImage) {

            updateImage.value = "";

        }


        if (updateModalTitle) {

            updateModalTitle.textContent =
                "Edit Update";

        }


        if (saveUpdateButton) {

            saveUpdateButton.textContent =
                "SAVE CHANGES";

            saveUpdateButton.disabled =
                false;

        }


        if (updateFormMessage) {

            updateFormMessage.textContent =
                "";

            updateFormMessage.hidden =
                true;

        }


        if (updateModal) {

            updateModal.hidden =
                false;

        }


        document.body.classList.add(
            "modal-open"
        );


        if (updateType) {

            setTimeout(
                function () {

                    updateType.focus();

                },
                50
            );

        }

    }


    /*
     * =====================================================
     * CLOSE ADD / EDIT MODAL
     * =====================================================
     */

    function closeUpdateModalWindow() {

        if (!updateModal) {

            return;

        }


        updateModal.hidden =
            true;


        document.body.classList.remove(
            "modal-open"
        );


        if (updateFormMessage) {

            updateFormMessage.textContent =
                "";

            updateFormMessage.hidden =
                true;

        }

    }


    /*
     * =====================================================
     * SHOW FORM MESSAGE
     * =====================================================
     */

    function showFormMessage(message) {

        if (!updateFormMessage) {

            return;

        }


        updateFormMessage.textContent =
            message;

        updateFormMessage.hidden =
            false;

    }


    /*
     * =====================================================
     * SAVE UPDATE
     * =====================================================
     */

    async function saveUpdate() {

        if (!updateForm) {

            return;

        }


        const session =
            getAdminSession();


        if (!session.token) {

            handleUnauthorized();

            return;

        }


        if (
            !updateForm.reportValidity()
        ) {

            return;

        }


        if (
            updateStartDate.value &&
            updateEndDate.value &&
            updateEndDate.value <
                updateStartDate.value
        ) {

            showFormMessage(
                "End date cannot be earlier than start date."
            );

            return;

        }


        if (saveUpdateButton) {

            saveUpdateButton.disabled =
                true;

            saveUpdateButton.textContent =
                "SAVING...";

        }


        if (updateFormMessage) {

            updateFormMessage.textContent =
                "";

            updateFormMessage.hidden =
                true;

        }


        const formData =
            new FormData(
                updateForm
            );


        formData.delete(
            "is_published"
        );


        if (
            updatePublished &&
            updatePublished.checked
        ) {

            formData.append(
                "is_published",
                "1"
            );

        } else {

            formData.append(
                "is_published",
                "0"
            );

        }


        const isEditMode =
            updateId &&
            updateId.value !== "";


        const targetApi =
            isEditMode
                ? UPDATE_UPDATE_API
                : CREATE_UPDATE_API;


        try {

            const response =
                await fetch(
                    targetApi,
                    {
                        method: "POST",

                        headers: {
                            "Authorization":
                                "Bearer " +
                                session.token,

                            "Accept":
                                "application/json"
                        },

                        body:
                            formData
                    }
                );


            if (
                response.status === 401
            ) {

                handleUnauthorized();

                return;

            }


            if (
                response.status === 403
            ) {

                showFormMessage(
                    isEditMode
                        ? "You do not have permission to edit website updates."
                        : "You do not have permission to create website updates."
                );

                return;

            }


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    (
                        isEditMode
                            ? "Failed to update website update."
                            : "Failed to create website update."
                    )
                );

            }


            if (updateFormMessage) {

                updateFormMessage.textContent =
                    isEditMode
                        ? "Website update updated successfully."
                        : "Website update created successfully.";

                updateFormMessage.hidden =
                    false;

            }


            await loadUpdates();


            setTimeout(
                function () {

                    closeUpdateModalWindow();

                },
                500
            );


        } catch (error) {

            console.error(
                isEditMode
                    ? "Failed to update website update:"
                    : "Failed to create website update:",
                error
            );


            showFormMessage(
                error.message ||
                (
                    isEditMode
                        ? "Something went wrong while updating the website update."
                        : "Something went wrong while creating the website update."
                )
            );


        } finally {

            if (saveUpdateButton) {

                saveUpdateButton.disabled =
                    false;

                saveUpdateButton.textContent =
                    isEditMode
                        ? "SAVE CHANGES"
                        : "SAVE UPDATE";

            }

        }

    }


    /*
     * =====================================================
     * OPEN DELETE MODAL
     * =====================================================
     */

    function openDeleteUpdateModal(id) {

        const update =
            updates.find(
                function (item) {

                    return String(
                        item.update_id
                    ) === String(id);

                }
            );


        if (
            !update ||
            !deleteUpdateModal
        ) {

            return;

        }


        selectedDeleteUpdateId =
            update.update_id;


        if (deleteUpdateTitle) {

            deleteUpdateTitle.textContent =
                update.title ||
                "Untitled update";

        }


        if (deleteUpdateMessage) {

            deleteUpdateMessage.textContent =
                "";

            deleteUpdateMessage.hidden =
                true;

        }


        if (confirmDeleteUpdateButton) {

            confirmDeleteUpdateButton.disabled =
                false;

            confirmDeleteUpdateButton.textContent =
                "DELETE UPDATE";

        }


        deleteUpdateModal.hidden =
            false;


        document.body.classList.add(
            "modal-open"
        );

    }


    /*
     * =====================================================
     * CLOSE DELETE MODAL
     * =====================================================
     */

    function closeDeleteUpdateModalWindow() {

        if (!deleteUpdateModal) {

            return;

        }


        deleteUpdateModal.hidden =
            true;


        selectedDeleteUpdateId =
            null;


        document.body.classList.remove(
            "modal-open"
        );


        if (deleteUpdateMessage) {

            deleteUpdateMessage.textContent =
                "";

            deleteUpdateMessage.hidden =
                true;

        }

    }


    /*
     * =====================================================
     * DELETE UPDATE
     * =====================================================
     */

    async function deleteUpdate() {

        if (
            !selectedDeleteUpdateId
        ) {

            return;

        }


        const session =
            getAdminSession();


        if (!session.token) {

            handleUnauthorized();

            return;

        }


        if (confirmDeleteUpdateButton) {

            confirmDeleteUpdateButton.disabled =
                true;

            confirmDeleteUpdateButton.textContent =
                "DELETING...";

        }


        const formData =
            new FormData();


        formData.append(
            "update_id",
            selectedDeleteUpdateId
        );


        try {

            const response =
                await fetch(
                    DELETE_UPDATE_API,
                    {
                        method: "POST",

                        headers: {
                            "Authorization":
                                "Bearer " +
                                session.token,

                            "Accept":
                                "application/json"
                        },

                        body:
                            formData
                    }
                );


            if (
                response.status === 401
            ) {

                handleUnauthorized();

                return;

            }


            if (
                response.status === 403
            ) {

                if (deleteUpdateMessage) {

                    deleteUpdateMessage.textContent =
                        "You do not have permission to delete website updates.";

                    deleteUpdateMessage.hidden =
                        false;

                }

                return;

            }


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "Failed to delete website update."
                );

            }


            if (deleteUpdateMessage) {

                deleteUpdateMessage.textContent =
                    "Website update deleted successfully.";

                deleteUpdateMessage.hidden =
                    false;

            }


            await loadUpdates();


            setTimeout(
                function () {

                    closeDeleteUpdateModalWindow();

                },
                500
            );


        } catch (error) {

            console.error(
                "Failed to delete website update:",
                error
            );


            if (deleteUpdateMessage) {

                deleteUpdateMessage.textContent =
                    error.message ||
                    "Something went wrong while deleting the website update.";

                deleteUpdateMessage.hidden =
                    false;

            }

        } finally {

            if (confirmDeleteUpdateButton) {

                confirmDeleteUpdateButton.disabled =
                    false;

                confirmDeleteUpdateButton.textContent =
                    "DELETE UPDATE";

            }

        }

    }


    /*
     * =====================================================
     * ADD BUTTON
     * =====================================================
     */

    if (addUpdateButton) {

        addUpdateButton.addEventListener(
            "click",
            function () {

                openUpdateModal();

            }
        );

    }


    /*
     * =====================================================
     * UPDATE MODAL CONTROLS
     * =====================================================
     */

    if (closeUpdateModal) {

        closeUpdateModal.addEventListener(
            "click",
            function () {

                closeUpdateModalWindow();

            }
        );

    }


    if (cancelUpdateButton) {

        cancelUpdateButton.addEventListener(
            "click",
            function () {

                closeUpdateModalWindow();

            }
        );

    }


    if (updateModal) {

        updateModal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === updateModal
                ) {

                    closeUpdateModalWindow();

                }

            }
        );

    }


    /*
     * =====================================================
     * DELETE MODAL CONTROLS
     * =====================================================
     */

    if (closeDeleteUpdateModal) {

        closeDeleteUpdateModal.addEventListener(
            "click",
            function () {

                closeDeleteUpdateModalWindow();

            }
        );

    }


    if (cancelDeleteUpdateButton) {

        cancelDeleteUpdateButton.addEventListener(
            "click",
            function () {

                closeDeleteUpdateModalWindow();

            }
        );

    }


    if (deleteUpdateModal) {

        deleteUpdateModal.addEventListener(
            "click",
            function (event) {

                if (
                    event.target === deleteUpdateModal
                ) {

                    closeDeleteUpdateModalWindow();

                }

            }
        );

    }


    if (confirmDeleteUpdateButton) {

        confirmDeleteUpdateButton.addEventListener(
            "click",
            function () {

                deleteUpdate();

            }
        );

    }


    /*
     * =====================================================
     * ESCAPE KEY
     * =====================================================
     */

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key !== "Escape"
            ) {

                return;

            }


            if (
                updateModal &&
                !updateModal.hidden
            ) {

                closeUpdateModalWindow();

                return;

            }


            if (
                deleteUpdateModal &&
                !deleteUpdateModal.hidden
            ) {

                closeDeleteUpdateModalWindow();

            }

        }
    );


    /*
     * =====================================================
     * FILTER EVENTS
     * =====================================================
     */

    if (updateTypeFilter) {

        updateTypeFilter.addEventListener(
            "change",
            function () {

                updatesPage = 1;

                renderUpdates();

            }
        );

    }


    if (updateStatusFilter) {

        updateStatusFilter.addEventListener(
            "change",
            function () {

                updatesPage = 1;

                renderUpdates();

            }
        );

    }


    /*
     * =====================================================
     * REFRESH
     * =====================================================
     */

    if (refreshUpdatesButton) {

        refreshUpdatesButton.addEventListener(
            "click",
            function () {

                loadUpdates();

            }
        );

    }


    /*
     * =====================================================
     * FORM SUBMISSION
     * =====================================================
     */

    if (updateForm) {

        updateForm.addEventListener(
            "submit",
            function (event) {

                event.preventDefault();

                saveUpdate();

            }
        );

    }


    /*
     * =====================================================
     * INITIAL LOAD
     * =====================================================
 */

    loadUpdates();

});
