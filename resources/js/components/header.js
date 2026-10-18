/**
 * Site header interactivity:
 * - mobile burger button (off-canvas drawer)
 * - single-column disclosure submenus (click / Enter / Space / Escape)
 * - arrow-key navigation along the bar and through an open submenu
 * - desktop hover convenience, only when the device supports hover
 *
 * The width at which the bar becomes a drawer is the stylesheet's
 * --_nav-breakpoint; the script reads that value back rather than keeping a
 * copy of the number.
 *
 * Import this once in resources/js/app.js:
 *   import './components/header';
 */
function initSiteHeader() {
    const nav = document.getElementById("site-nav");
    const burger = document.querySelector(".site-header__burger");
    const overlay = document.querySelector(".site-header__overlay");
    const navClose = document.querySelector("[data-nav-close]");

    if (!nav || !burger) {
        return; // header not present on this page
    }

    const items = Array.from(
        document.querySelectorAll(".site-header__nav-item"),
    );
    const toggles = Array.from(
        document.querySelectorAll(".site-header__nav-toggle"),
    );
    const supportsHover = window.matchMedia("(hover: hover)").matches;

    // The bar's own breakpoint, in pixels, as the header stylesheet declares
    // it. A media query is resolved before custom properties are, so the
    // stylesheet still repeats the number in its @media rules — the script
    // keeps no copy of its own and reads the token back instead. If the value
    // is missing there is no stylesheet to collapse the bar, so the bar is
    // left as the plain visible list that page already shows.
    const navBreakpoint =
        parseFloat(
            getComputedStyle(nav).getPropertyValue("--_nav-breakpoint"),
        ) || 0;

    const isMobile = () =>
        window.matchMedia(`(max-width: ${navBreakpoint}px)`).matches;
    const isDesktop = () => !isMobile();
    const isDrawerOpen = () => burger.getAttribute("aria-expanded") === "true";
    const isOpen = (toggle) => toggle.getAttribute("aria-expanded") === "true";

    function panelOf(toggle) {
        return document.getElementById(toggle.getAttribute("aria-controls"));
    }

    /** The links a panel holds, in the order they are read out. */
    function linksOf(toggle) {
        const panel = panelOf(toggle);

        return panel ? Array.from(panel.querySelectorAll("a")) : [];
    }

    function closeSubmenu(toggle) {
        toggle.setAttribute("aria-expanded", "false");
        panelOf(toggle)?.setAttribute("hidden", "");
    }

    function openSubmenu(toggle) {
        toggles.forEach(closeSubmenu); // only one open at a time
        toggle.setAttribute("aria-expanded", "true");
        panelOf(toggle)?.removeAttribute("hidden");
    }

    function closeAllSubmenus() {
        toggles.forEach(closeSubmenu);
    }

    /** Opens a toggle's panel and steps onto its first (or last) link. */
    function focusSubmenuEdge(toggle, last) {
        openSubmenu(toggle);

        const links = linksOf(toggle);

        links[last ? links.length - 1 : 0]?.focus();
    }

    /**
     * Moves focus to the neighbouring top-level item. Only the horizontal
     * desktop bar reads left and right, so the stacked drawer ignores them.
     */
    function focusSiblingItem(from, direction) {
        const index = items.indexOf(from.closest(".site-header__nav-item"));
        const target = items[index + direction];

        if (index === -1 || !target) {
            return false;
        }

        closeAllSubmenus();

        const control =
            target.querySelector(".site-header__nav-link") ??
            target.querySelector(".site-header__nav-toggle");

        control?.focus();

        return true;
    }

    // Which way a key walks: the arrow keys are the ones a keyboard user
    // navigates a menu with, so the script answers them itself.
    const listStep = (key) =>
        key === "ArrowDown" ? 1 : key === "ArrowUp" ? -1 : 0;
    const barStep = (key) =>
        key === "ArrowRight" ? 1 : key === "ArrowLeft" ? -1 : 0;

    /**
     * Answers right/left when the bar is horizontal, and takes the key only if
     * focus actually moved so the browser keeps what it does not use.
     */
    function moveAlongBar(event, from) {
        const direction = barStep(event.key);

        if (direction === 0 || !isDesktop()) {
            return;
        }

        if (focusSiblingItem(from, direction)) {
            event.preventDefault();
        }
    }

    // The mobile drawer visibility is announced through the burger's
    // aria-expanded and the nav/overlay aria-hidden attributes. On desktop
    // the nav is a normal visible bar, so aria-hidden is removed entirely.
    function setNavVisible(visible) {
        if (isDesktop()) {
            nav.removeAttribute("aria-hidden");
            overlay?.removeAttribute("aria-hidden");
            burger.setAttribute("aria-expanded", "false");
            document.body.removeAttribute("data-nav-open");

            return;
        }

        nav.setAttribute("aria-hidden", String(!visible));
        overlay?.setAttribute("aria-hidden", String(!visible));
        burger.setAttribute("aria-expanded", String(visible));

        if (visible) {
            document.body.dataset.navOpen = "true";
        } else {
            document.body.removeAttribute("data-nav-open");
        }
    }

    /** Opens the drawer and puts the keyboard on the control that closes it. */
    function openDrawer() {
        closeAllSubmenus();
        setNavVisible(true);

        // The open drawer covers the burger, so focus lands on the drawer's
        // own close control rather than on something it can no longer see.
        navClose?.focus();
    }

    /**
     * Closes the drawer and hands the keyboard back to the burger. Focus
     * leaves before the drawer is hidden from assistive technology, so it is
     * never stranded inside a region the reader can no longer reach.
     */
    function closeDrawer() {
        if (!isDrawerOpen()) {
            return;
        }

        burger.focus();
        setNavVisible(false);
        closeAllSubmenus();
    }

    // The drawer starts closed; saying so up front keeps the hidden menu out
    // of the reading order before it is ever touched.
    setNavVisible(false);

    // ── Burger toggle ──
    burger.addEventListener("click", () => {
        if (isDrawerOpen()) {
            closeDrawer();
        } else {
            openDrawer();
        }
    });

    // ── Overlay click closes the drawer ──
    overlay?.addEventListener("click", () => {
        setNavVisible(false);
        closeAllSubmenus();
    });

    // ── Close button above the menu (the drawer covers the burger) ──
    navClose?.addEventListener("click", () => closeDrawer());

    // ── Submenu toggles (touch / keyboard) and the panels they disclose ──
    toggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            if (isOpen(toggle)) {
                closeSubmenu(toggle);
            } else {
                openSubmenu(toggle);
            }
        });

        // Enter and Space already press a <button>; the arrow keys open the
        // panel at the matching end and then step through its links.
        toggle.addEventListener("keydown", (event) => {
            const step = listStep(event.key);

            if (step !== 0) {
                event.preventDefault();
                focusSubmenuEdge(toggle, step === -1);

                return;
            }

            moveAlongBar(event, toggle);
        });

        panelOf(toggle)?.addEventListener("keydown", (event) => {
            const step = listStep(event.key);

            if (step !== 0) {
                const links = linksOf(toggle);
                const target = links[links.indexOf(event.target) + step];

                // An arrow that cannot move through the menu is left to the
                // page, so the drawer still scrolls at either end.
                if (target) {
                    event.preventDefault();
                    target.focus();
                }

                return;
            }

            moveAlongBar(event, event.target);
        });
    });

    // ── Top-level category links ──
    items.forEach((item) => {
        const link = item.querySelector(".site-header__nav-link");
        const toggle = item.querySelector(".site-header__nav-toggle");

        link?.addEventListener("keydown", (event) => {
            const step = listStep(event.key);

            // Down/up from the link treat the link and its toggle as one
            // control, opening the panel the toggle owns.
            if (step !== 0 && toggle) {
                event.preventDefault();
                focusSubmenuEdge(toggle, step === -1);

                return;
            }

            moveAlongBar(event, link);
        });
    });

    // ── Desktop hover convenience (only where hover is supported) ──
    if (supportsHover) {
        items.forEach((item) => {
            const toggle = item.querySelector(".site-header__nav-toggle");

            if (!toggle) {
                return;
            }

            item.addEventListener("mouseenter", () => openSubmenu(toggle));
            item.addEventListener("mouseleave", (event) => {
                // keep the menu open while focus or the pointer is still inside
                if (item.contains(event.relatedTarget)) {
                    return;
                }

                closeSubmenu(toggle);
            });
        });
    }

    // ── Escape closes the open submenu, then the drawer ──
    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        const openToggle = toggles.find(isOpen);

        if (openToggle) {
            closeSubmenu(openToggle);
            openToggle.focus();

            return;
        }

        closeDrawer();
    });

    // ── Outside click closes submenus / drawer ──
    document.addEventListener("click", (event) => {
        if (event.target.closest(".site-header__nav-item")) {
            return;
        }

        closeAllSubmenus();

        // clicking any link inside the drawer should close it
        if (
            event.target.closest("a") &&
            nav.contains(event.target) &&
            !isDesktop()
        ) {
            setNavVisible(false);
        }
    });

    // ── Focus leaving an open submenu closes it ──
    document.addEventListener("focusin", (event) => {
        toggles.forEach((toggle) => {
            if (!isOpen(toggle)) {
                return;
            }

            const panel = panelOf(toggle);

            if (
                panel &&
                !panel.contains(event.target) &&
                !toggle.contains(event.target)
            ) {
                closeSubmenu(toggle);
            }
        });
    });

    // ── Resizing back to desktop closes drawer + submenus ──
    window.addEventListener("resize", () => {
        if (isDesktop()) {
            setNavVisible(false);
            closeAllSubmenus();
        }
    });
}

document.addEventListener("DOMContentLoaded", initSiteHeader);
