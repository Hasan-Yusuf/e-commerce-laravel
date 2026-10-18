/* A shared helper rather than a behaviour: a focus trap for a modal surface —
 * the auth dialog, the catalogue's filter drawer — so the keyboard cannot leave
 * a panel that covers the page, and is handed back to whatever opened it when
 * it closes. */

const FOCUSABLE =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export function createFocusTrap(container) {
    const items = () => Array.from(container.querySelectorAll(FOCUSABLE));

    return {
        /** Puts focus on the container's first focusable control. */
        focusFirst() {
            items()[0]?.focus();
        },

        /**
         * Answers a Tab press by stepping from the last control back to the
         * first, and from the first back to the last. Returns whether it took
         * the key, so the caller can leave the rest of the event alone.
         */
        handleKeydown(event) {
            if (event.key !== "Tab") {
                return false;
            }

            const focusable = items();

            if (focusable.length === 0) {
                return false;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();

                return true;
            }

            if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();

                return true;
            }

            return false;
        },
    };
}
