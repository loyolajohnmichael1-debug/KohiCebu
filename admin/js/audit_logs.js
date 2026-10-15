document.addEventListener("DOMContentLoaded", () => {

    const tableBody =
        document.getElementById("auditLogsTableBody");

    const auditLogCount =
        document.getElementById("auditLogCount");

    const refreshButton =
        document.getElementById("refreshAuditLogsButton");

    const applyFiltersButton =
        document.getElementById("applyAuditFiltersButton");

    const clearFiltersButton =
        document.getElementById("clearAuditFiltersButton");

    const dateFromInput =
        document.getElementById("dateFrom");

    const dateToInput =
        document.getElementById("dateTo");

    const pagination = document.getElementById("auditLogsPagination");
    let currentPage = 1;


    /*
     * ---------------------------------------------------------
     * Authentication
     * ---------------------------------------------------------
     */

    function getAuthToken() {

        return (
            localStorage.getItem("auth_token") ||
            localStorage.getItem("token") ||
            localStorage.getItem("api_token") ||
            localStorage.getItem("access_token") ||
            sessionStorage.getItem("auth_token") ||
            sessionStorage.getItem("token") ||
            sessionStorage.getItem("api_token") ||
            sessionStorage.getItem("access_token")
        );

    }


    /*
     * ---------------------------------------------------------
     * Escape HTML
     * ---------------------------------------------------------
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
     * ---------------------------------------------------------
     * Format date/time
     * ---------------------------------------------------------
     */

    function formatDateTime(value) {

        if (!value) {
            return "—";
        }

        const date =
            new Date(
                String(value).replace(" ", "T")
            );

        if (
            Number.isNaN(
                date.getTime()
            )
        ) {

            return value;

        }

        return date.toLocaleString(
            "en-PH",
            {
                year: "numeric",
                month: "short",
                day: "numeric",
                hour: "numeric",
                minute: "2-digit",
                second: "2-digit"
            }
        );

    }


    /*
     * ---------------------------------------------------------
     * Build target label
     * ---------------------------------------------------------
     */

    function formatTarget(log) {

        const targetType =
            log.target_type
                ? String(log.target_type)
                : "";

        const targetId =
            log.target_id !== null &&
            log.target_id !== undefined
                ? `#${log.target_id}`
                : "";

        if (
            !targetType &&
            !targetId
        ) {

            return "—";

        }

        return (
            escapeHtml(targetType) +
            (
                targetId
                    ? ` ${escapeHtml(targetId)}`
                    : ""
            )
        );

    }


    /*
     * ---------------------------------------------------------
     * Load audit logs
     * ---------------------------------------------------------
     */

    async function loadAuditLogs() {

        tableBody.innerHTML = `
            <tr>
                <td
                    colspan="7"
                    class="empty-state"
                >
                    Loading audit logs...
                </td>
            </tr>
        `;

        auditLogCount.textContent = "0";


        const token =
            getAuthToken();

        if (!token) {

            tableBody.innerHTML = `
                <tr>
                    <td
                        colspan="7"
                        class="empty-state error-state"
                    >
                        Authentication token not found.
                    </td>
                </tr>
            `;

            return;

        }


        const params =
            new URLSearchParams();


        const dateFrom =
            dateFromInput.value.trim();

        const dateTo =
            dateToInput.value.trim();


        if (dateFrom) {

            params.set(
                "date_from",
                dateFrom
            );

        }


        if (dateTo) {

            params.set(
                "date_to",
                dateTo
            );

        }

        params.set("page", String(currentPage));


        const queryString =
            params.toString();


        const url =
            "../api/get_audit_logs.php" +
            (
                queryString
                    ? `?${queryString}`
                    : ""
            );


        try {

            const response =
                await fetch(
                    url,
                    {
                        method: "GET",

                        headers: {
                            "Authorization":
                                `Bearer ${token}`,

                            "Accept":
                                "application/json"
                        }
                    }
                );


            const data =
                await response.json();


            if (
                !response.ok ||
                !data.success
            ) {

                throw new Error(
                    data.message ||
                    "Unable to load audit logs."
                );

            }


            const logs =
                Array.isArray(data.logs)
                    ? data.logs
                    : [];

            const total = Number(data.count) || 0;
            auditLogCount.textContent = String(total);
            window.AdminPagination.render(pagination, total, currentPage, (page) => {
                currentPage = page;
                loadAuditLogs();
            });


            if (
                logs.length === 0
            ) {

                tableBody.innerHTML = `
                    <tr>
                        <td
                            colspan="7"
                            class="empty-state"
                        >
                            No audit logs found.
                        </td>
                    </tr>
                `;

                return;

            }


            tableBody.innerHTML =
                logs.map(
                    (log) => {

                        const userName =
                            log.user_name ||
                            "Unknown User";

                        const role =
                            log.user_role ||
                            "UNKNOWN";

                        const action =
                            log.action_type ||
                            "—";

                        const module =
                            log.module ||
                            "—";

                        const description =
                            log.description ||
                            "—";


                        return `
                            <tr>

                                <td>
                                    <span class="date-text">
                                        ${escapeHtml(
                                            formatDateTime(
                                                log.created_at
                                            )
                                        )}
                                    </span>
                                </td>


                                <td>
                                    <div class="user-cell">
                                        <strong>
                                            ${escapeHtml(
                                                userName
                                            )}
                                        </strong>

                                        <span>
                                            User #${escapeHtml(
                                                log.user_id ??
                                                "—"
                                            )}
                                        </span>
                                    </div>
                                </td>


                                <td>
                                    <span
                                        class="role-badge"
                                    >
                                        ${escapeHtml(
                                            role
                                        )}
                                    </span>
                                </td>


                                <td>
                                    <span
                                        class="action-badge action-${escapeHtml(
                                            String(action)
                                                .toLowerCase()
                                                .replace(
                                                    /[^a-z0-9_-]/g,
                                                    "-"
                                                )
                                        )}"
                                    >
                                        ${escapeHtml(
                                            action
                                        )}
                                    </span>
                                </td>


                                <td>
                                    <span class="module-text">
                                        ${escapeHtml(
                                            module
                                        )}
                                    </span>
                                </td>


                                <td>
                                    <span class="target-text">
                                        ${formatTarget(
                                            log
                                        )}
                                    </span>
                                </td>


                                <td>
                                    <span class="description-text">
                                        ${escapeHtml(
                                            description
                                        )}
                                    </span>
                                </td>

                            </tr>
                        `;

                    }
                ).join("");


        } catch (error) {

            console.error(
                "Audit Logs Error:",
                error
            );


            tableBody.innerHTML = `
                <tr>
                    <td
                        colspan="7"
                        class="empty-state error-state"
                    >
                        ${escapeHtml(
                            error.message ||
                            "Unable to load audit logs."
                        )}
                    </td>
                </tr>
            `;

        }

    }


    /*
     * ---------------------------------------------------------
     * Apply filters
     * ---------------------------------------------------------
     */

    applyFiltersButton.addEventListener(
        "click",
        () => {

            const dateFrom =
                dateFromInput.value;

            const dateTo =
                dateToInput.value;


            if (
                dateFrom &&
                dateTo &&
                dateFrom > dateTo
            ) {

                alert(
                    "The From date cannot be later than the To date."
                );

                return;

            }


            currentPage = 1;
            loadAuditLogs();

        }
    );


    /*
     * ---------------------------------------------------------
     * Clear filters
     * ---------------------------------------------------------
     */

    clearFiltersButton.addEventListener(
        "click",
        () => {

            dateFromInput.value = "";
            dateToInput.value = "";

            currentPage = 1;

            loadAuditLogs();

        }
    );


    /*
     * ---------------------------------------------------------
     * Refresh
     * ---------------------------------------------------------
     */

    refreshButton.addEventListener(
        "click",
        () => {

            currentPage = 1;
            loadAuditLogs();

        }
    );


    /*
     * ---------------------------------------------------------
     * Initial load
     * ---------------------------------------------------------
     */

    loadAuditLogs();

});
