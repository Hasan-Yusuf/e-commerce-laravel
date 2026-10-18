/* Guard against a double post: the first submit of a form marked
   [data-submit-once] disables its submit buttons until the browser leaves the
   page, so an impatient second click cannot send the form twice. */

const guardedForms = document.querySelectorAll("form[data-submit-once]");

guardedForms.forEach((form) => {
    form.addEventListener("submit", () => {
        if (form.dataset.submitting === "true") {
            return;
        }

        form.dataset.submitting = "true";
        form.setAttribute("aria-busy", "true");

        form.querySelectorAll(
            'button[type="submit"], input[type="submit"]',
        ).forEach((button) => {
            button.disabled = true;
        });
    });
});
