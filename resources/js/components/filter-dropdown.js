/* A filter dropdown reads back what is chosen in its summary. The page only
   re-renders when the form is submitted, so the summary would otherwise still
   say what it said before the shopper ticked anything: this keeps it in step as
   the choices are made.

   The summary names at most as many choices as the control says it may
   (`data-max-labels`) and counts the rest with the wording it carries
   (`data-more-label`), so the numbers and the phrasing are the catalogue's and
   the server-rendered summary says the same thing this does.

   The change is heard on the dropdown rather than on each box, so the boxes the
   choice-controls script flips are heard too. */

function chosenLabels(dropdown) {
    return [...dropdown.querySelectorAll("input[type='checkbox']")]
        .filter((control) => control.checked)
        .map((control) => control.closest("label")?.textContent.trim())
        .filter((label) => Boolean(label));
}

function summarise(dropdown, labels) {
    const maxLabels = Number(dropdown.dataset.maxLabels) || 0;

    if (!labels.length) {
        return dropdown.dataset.emptyLabel || "";
    }

    if (!maxLabels || labels.length <= maxLabels) {
        return labels.join(", ");
    }

    const moreLabel = dropdown.dataset.moreLabel || "";

    return `${labels.slice(0, maxLabels).join(", ")} ${moreLabel.replace(
        ":count",
        labels.length - maxLabels,
    )}`;
}

function updateSummary(dropdown) {
    const value = dropdown.querySelector(".filter-dropdown__value");

    if (!value) {
        return;
    }

    value.textContent = summarise(dropdown, chosenLabels(dropdown));
}

document.querySelectorAll("[data-filter-dropdown]").forEach((dropdown) => {
    dropdown.addEventListener("change", (event) => {
        if (event.target.matches("input[type='checkbox']")) {
            updateSummary(dropdown);
        }
    });
});
