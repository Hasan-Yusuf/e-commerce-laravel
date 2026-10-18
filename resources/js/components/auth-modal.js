/* Auth modal: one trigger reveals the login + register forms together. */

import { createFocusTrap } from "../lib/focus-trap";

const authModal = document.querySelector("[data-auth-modal]");

if (authModal) {
    const openers = document.querySelectorAll("[data-auth-open]");
    const dialog = authModal.querySelector(".auth-modal__dialog");
    const closers = authModal.querySelectorAll("[data-auth-close]");
    const focusTarget = authModal.querySelector("[data-auth-focus]");

    // The dialog is a modal surface: the keyboard stays inside it while it is
    // open, and returns to whatever opened it when it closes.
    const trap = createFocusTrap(dialog);

    let lastFocused = null;

    const isOpen = () => authModal.getAttribute("data-open") === "true";

    const setOpen = (open, { restoreFocus = true } = {}) => {
        authModal.setAttribute("data-open", open ? "true" : "false");
        authModal.setAttribute("aria-hidden", open ? "false" : "true");
        authModal.toggleAttribute("hidden", !open);

        openers.forEach((opener) => {
            opener.setAttribute("aria-expanded", open ? "true" : "false");
        });

        document.body.toggleAttribute("data-auth-modal-open", open);

        if (open) {
            lastFocused = document.activeElement;
            focusTarget?.focus();
        } else if (restoreFocus && lastFocused instanceof HTMLElement) {
            lastFocused.focus();
        }
    };

    openers.forEach((opener) => {
        opener.addEventListener("click", (event) => {
            // The trigger is a real link to the sign-in page; with JavaScript
            // we show the dialog instead so both forms are one click away.
            event.preventDefault();
            setOpen(true);
        });
    });

    closers.forEach((closer) => {
        closer.addEventListener("click", () => setOpen(false));
    });

    document.addEventListener("keydown", (event) => {
        if (!isOpen()) {
            return;
        }

        if (event.key === "Escape") {
            event.preventDefault();
            setOpen(false);
            return;
        }

        // Keep focus inside the dialog while it is open.
        trap.handleKeydown(event);
    });

    // Opened by the server on validation errors: sync state and move focus in.
    if (isOpen()) {
        setOpen(true, { restoreFocus: false });
    }
}
