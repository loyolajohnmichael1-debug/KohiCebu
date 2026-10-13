(function () {
    "use strict";

    const pageSize = 10;

    function slice(items, page) {
        const pageCount = Math.max(1, Math.ceil(items.length / pageSize));
        const safePage = Math.min(Math.max(1, page), pageCount);
        const start = (safePage - 1) * pageSize;
        return items.slice(start, start + pageSize);
    }

    function render(container, total, page, onChange) {
        if (!container) return;
        container.replaceChildren();

        const pageCount = Math.max(1, Math.ceil(total / pageSize));
        const safePage = Math.min(Math.max(1, page), pageCount);
        container.hidden = total <= pageSize;
        if (container.hidden) return;

        const start = (safePage - 1) * pageSize + 1;
        const end = Math.min(safePage * pageSize, total);
        const summary = document.createElement("span");
        summary.className = "admin-pagination__summary";
        summary.textContent = `Showing ${start}-${end} of ${total}`;
        container.appendChild(summary);

        const controls = document.createElement("div");
        controls.className = "admin-pagination__controls";

        function addButton(label, target, disabled, current) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "admin-pagination__button";
            button.textContent = label;
            button.disabled = disabled;
            if (current) button.setAttribute("aria-current", "page");
            button.addEventListener("click", () => onChange(target));
            controls.appendChild(button);
        }

        addButton("Previous", safePage - 1, safePage === 1, false);
        const first = Math.max(1, Math.min(safePage - 2, pageCount - 4));
        const last = Math.min(pageCount, first + 4);
        for (let number = first; number <= last; number += 1) {
            addButton(String(number), number, false, number === safePage);
        }
        addButton("Next", safePage + 1, safePage === pageCount, false);
        container.appendChild(controls);
    }

    window.AdminPagination = { pageSize, slice, render };
})();
