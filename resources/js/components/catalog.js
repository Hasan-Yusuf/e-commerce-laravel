/* Catalog enhancements: sort auto-submit, filter drawer, gallery, quantity. */

import { createFocusTrap } from "../lib/focus-trap";

/* ── Sort select: submit on change (the submit button keeps it working
   without JavaScript) ── */
const autoSubmitFields = document.querySelectorAll("[data-auto-submit]");

autoSubmitFields.forEach((field) => {
    field.addEventListener("change", () => {
        field.form?.requestSubmit();
    });
});

/* ── Filter drawer (mobile) ── */
const filterPanel = document.querySelector("[data-filter-panel]");

if (filterPanel) {
    const sidebar = filterPanel.querySelector("[data-filter-sidebar]");
    const toggle = filterPanel.querySelector("[data-filter-toggle]");
    const close = filterPanel.querySelector("[data-filter-close]");
    const overlay = filterPanel.querySelector("[data-filter-overlay]");

    // The width at which the sidebar becomes a drawer, in pixels, as the filters
    // stylesheet declares it. A media query is resolved before custom properties
    // are, so the stylesheet still repeats the number in its rules; the script
    // keeps no copy of its own and reads the token back instead.
    const drawerBreakpoint =
        parseFloat(
            getComputedStyle(filterPanel).getPropertyValue(
                "--_drawer-breakpoint",
            ),
        ) || 0;

    const isDrawerRange = () =>
        window.matchMedia(`(max-width: ${drawerBreakpoint}px)`).matches;

    const isOpen = () => sidebar?.getAttribute("data-open") === "true";

    // The drawer covers the page at drawer widths, so while it is open the
    // keyboard stays inside it — the same trap the auth modal uses — and the
    // control that opened it takes focus back when it closes.
    const trap = sidebar ? createFocusTrap(sidebar) : null;

    let lastFocused = null;

    const setOpen = (open) => {
        sidebar?.setAttribute("data-open", open ? "true" : "false");
        toggle?.setAttribute("aria-expanded", open ? "true" : "false");

        if (overlay) {
            overlay.hidden = !open;
        }

        if (open) {
            lastFocused = document.activeElement;
            document.body.setAttribute("data-filters-open", "true");

            // The trigger sits outside the panel, so focus lands on the drawer's
            // own close control rather than behind it.
            if (isDrawerRange()) {
                trap?.focusFirst();
            }
        } else {
            document.body.removeAttribute("data-filters-open");

            if (lastFocused instanceof HTMLElement) {
                lastFocused.focus();
            }
        }
    };

    toggle?.addEventListener("click", () => setOpen(!isOpen()));
    close?.addEventListener("click", () => setOpen(false));
    overlay?.addEventListener("click", () => setOpen(false));

    /* The drawer's own covering does not reach everything: on a narrow screen the
       drawer takes most of the width, and what the page pins above the covering
       (the site header) is not under it either, so a click out there never lands
       on it. Listened for on the document instead, and only while the drawer is
       open on a small screen: a click that lands in the drawer, or on the control
       that opened it, is the shopper's and is left alone. */
    document.addEventListener("click", (event) => {
        if (!isOpen() || !isDrawerRange()) {
            return;
        }

        if (sidebar?.contains(event.target) || toggle?.contains(event.target)) {
            return;
        }

        setOpen(false);
    });

    document.addEventListener("keydown", (event) => {
        if (!isOpen()) {
            return;
        }

        if (event.key === "Escape") {
            setOpen(false);
            return;
        }

        if (isDrawerRange()) {
            trap?.handleKeydown(event);
        }
    });
}

/* ── Product gallery: swap the main image when a thumbnail is chosen ── */
document.querySelectorAll("[data-product-gallery]").forEach((gallery) => {
    const main = gallery.querySelector("[data-gallery-main]");
    const thumbs = gallery.querySelectorAll("[data-gallery-thumb]");

    if (!main || thumbs.length === 0) {
        return;
    }

    thumbs.forEach((thumb) => {
        thumb.addEventListener("click", (event) => {
            event.preventDefault();

            const source = thumb.getAttribute("data-src");

            if (!source) {
                return;
            }

            // The main image carries a srcset for the image it opened with, and
            // a srcset wins over src: drop it so the chosen thumbnail is what
            // the browser draws.
            main.removeAttribute("srcset");
            main.setAttribute("src", source);

            thumbs.forEach((item) => {
                item.setAttribute(
                    "aria-current",
                    item === thumb ? "true" : "false",
                );
            });
        });
    });
});
