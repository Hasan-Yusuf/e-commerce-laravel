/* A scroller's own way along: the two buttons step the row and go deaf at the
 * ends of it.
 *
 * How far a step is follows what the row shows. A hero that shows one banner at
 * a time steps by that banner; a section that shows a row of cards steps by a
 * screenful, which is a step rather than a chairlift. The distance between the
 * first two items is what tells the two apart, measured as they lie rather than
 * assumed from either one's size.
 *
 * Which way "next" is depends on the writing direction. In RTL a row starts at
 * the right and `scrollLeft` counts down from zero rather than up, so the
 * direction is read off the row instead of assumed, and `Math.abs` is what makes
 * the same two ends measurable either way round.
 */

const scrollerGroups = document.querySelectorAll("[data-scroller-group]");

scrollerGroups.forEach((group) => {
    const rail = group.querySelector("[data-scroller]");
    const nav = group.querySelector("[data-scroller-nav]");
    const prev = nav?.querySelector('[data-scroller-step="previous"]');
    const next = nav?.querySelector('[data-scroller-step="next"]');

    if (!rail || !nav || !prev || !next) {
        return;
    }

    const step = () => {
        const [first, second] = Array.from(rail.children).slice(0, 2);

        if (second) {
            const advance = Math.abs(
                second.getBoundingClientRect().left -
                    first.getBoundingClientRect().left,
            );

            // An item that is the screen is a carousel showing one at a time.
            if (advance >= rail.clientWidth * 0.9) {
                return advance;
            }
        }

        return rail.clientWidth * 0.8;
    };

    // How far along the row is, whichever way the browser counts it, and how
    // far it can go.
    const travelled = () => Math.abs(rail.scrollLeft);
    const reach = () => Math.max(rail.scrollWidth - rail.clientWidth, 0);

    const update = () => {
        const end = reach();

        // A row that fits has nowhere to go, and is not offered a way.
        nav.hidden = end <= 1;

        prev.disabled = travelled() <= 1;
        next.disabled = travelled() >= end - 1;
    };

    const move = (distance) => {
        const rtl = getComputedStyle(rail).direction === "rtl";

        rail.scrollBy({
            left: rtl ? -distance : distance,
            behavior: window.matchMedia("(prefers-reduced-motion: reduce)")
                .matches
                ? "auto"
                : "smooth",
        });
    };

    // "Previous" asks for the negative direction; which way that is on screen is
    // the row's business rather than the button's.
    prev.addEventListener("click", () => move(-step()));
    next.addEventListener("click", () => move(step()));

    rail.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", update);

    update();
});
