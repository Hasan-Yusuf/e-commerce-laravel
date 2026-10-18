/* The account's forms open as what the account holds: read-only until the shopper
   says they want to change it, so a stray click or keystroke cannot rewrite a
   detail. This is an enhancement — without scripting there is nothing to unlock,
   so the forms are answered exactly as they are rendered. */

const formSelector = "[data-account-form]";
const toggleSelector = "[data-account-form-toggle]";
const cancelSelector = "[data-account-form-cancel]";

/* A field the server refused is marked as invalid, and the message it carries
   can only be acted on in the form it belongs to. */
const invalidFieldSelector = "[aria-invalid='true']";

document.querySelectorAll(formSelector).forEach((form) => {
    const fields = form.querySelectorAll("input, select, textarea");
    const toggle = form.querySelector(toggleSelector);
    const cancel = form.querySelector(cancelSelector);

    const setEditing = (editing) => {
        form.classList.toggle("account-form--editing", editing);
        form.classList.toggle("account-form--locked", !editing);

        fields.forEach((field) => {
            field.disabled = !editing;
        });
    };

    // A form that came back with errors opens for editing, on the field that
    // needs answering: the shopper should not have to ask for the form back to
    // fix what they were just refused.
    const refused = form.querySelector(invalidFieldSelector);

    setEditing(refused !== null);

    if (refused) {
        refused.focus();
    }

    toggle?.addEventListener("click", () => {
        setEditing(true);

        // The first field the shopper can actually change owns the focus, so
        // choosing Edit also says where the typing goes.
        form.querySelector("input:not([type='hidden']), select")?.focus();
    });

    cancel?.addEventListener("click", () => {
        // Back to the values the form was rendered with. A form the server
        // refused was rendered with what the shopper typed — that is what is
        // left to correct, not a state to go back to — so this is the account's
        // own values only for a form that was never submitted.
        form.reset();
        setEditing(false);
    });
});
