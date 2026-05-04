window.addEventListener("DOMContentLoaded", () => {
    // Header
    const burger = document.querySelector(".header__burger");
    const navWrapper = document.querySelector(".header__navigation-wrapper");

    if (burger && navWrapper) {
        burger.addEventListener("click", () => {
            burger.classList.toggle("active");
            navWrapper.classList.toggle("open");
        });
    }

    // Tabs card
    const tab_buttons_container = document.querySelector(".tab-buttons-container");
    const tab_buttons = document.querySelectorAll(".tabs .tab-btn");
    const tab_panes = document.querySelectorAll(".tabs .tab-pane");

    if (tab_buttons_container && tab_buttons && tab_panes) {
        tab_buttons_container.addEventListener("click", function (e) {
            const tab_btn = e.target.closest(".tab-btn");

            if (tab_btn) {
                tab_buttons.forEach((el) => el.classList.remove("active"));
                tab_panes.forEach((el) => el.classList.remove("active"));

                const tab_index = tab_btn.getAttribute("tab-index");
                const tab_pane = Array.from(tab_panes).find((pane) => pane.getAttribute("tab-index") === tab_index);

                tab_btn.classList.add("active");
                tab_pane.classList.add("active");

                const records_table = document.querySelector(".table-wrapper");
                if (records_table) {
                    records_table.outerHTML = ` 
                    <div class="empty-state">
                        <p>No accounts found.</p>
                    </div>`;
                }

                const error_list = document.querySelector(".error-list");
                if (error_list) {
                    error_list.innerHTML = "";
                }
            }
        });
    }

    // Date Inputs
    const from_date = document.querySelector("#from_date");
    const to_date = document.querySelector("#to_date");
    const submit_btn = document.querySelector('button[type="submit"]');

    if (from_date && to_date && submit_btn) {
        submit_btn.addEventListener("click", function (e) {
            const from_date_timestamp = Date.parse(from_date.value);
            const to_date_timestamp = Date.parse(to_date.value);

            if (to_date_timestamp - from_date_timestamp <= 0) {
                alert("from Date input must be before To Date input.");
                e.preventDefault();
            }
        });
    }
});
