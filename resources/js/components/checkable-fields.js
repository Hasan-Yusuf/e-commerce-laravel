/* Choice controls: the wrapper is the label around the control and its name, so
   a click anywhere on it changes the control. One click is not the browser's to
   make: the wrapper's own box — the padding inside its border, when a choice is
   drawn as a card — is edge to edge the label, and the label's activation runs
   on top of this handler. That is a second flip, which for a checkbox cancels
   the first. So this click is handled here instead: the activation is stopped
   and the change is made once. */

function changeCheckable(event) {
    const wrapper = event.currentTarget;
    const control = wrapper.querySelector("input:not(:disabled)");

    if (!control) {
        return;
    }

    // A click on the control itself, or on anything interactive inside the
    // wrapper (a link in a description), belongs to that element rather than to
    // the wrapper.
    const target = event.target;

    if (
        target !== wrapper &&
        target.closest("a, button, input, select, textarea")
    ) {
        return;
    }

    // The wrapper's own box is the control's: stop the label's activation, and
    // make the one change in its place — with the click the control would have
    // received, so a form or a live component listening to it still hears it.
    event.preventDefault();

    control.focus();
    control.click();
}

document
    .querySelectorAll(".radio-field, .checkbox-field")
    .forEach((wrapper) => wrapper.addEventListener("click", changeCheckable));
