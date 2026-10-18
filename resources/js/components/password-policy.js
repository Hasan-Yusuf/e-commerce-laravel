/* Mirror the server password policy into the field's own constraint validation.
   The native constraints (minlength/maxlength/pattern) already make the field's
   valid/invalid state — and the green/red field styling — agree with the policy;
   this script only replaces the browser's generic message with the app's own
   translated version, and adds the character checks a `pattern` cannot report
   individually. The breach check has no client-side equivalent, so it stays on
   the server: a field can look valid here and still be rejected there. */

/* The policy is read off the field, keyed by the rule it was built from. */
const policySelector = "[data-password-policy]";

const hasMixedCase = (value) => /\p{Ll}/u.test(value) && /\p{Lu}/u.test(value);

const requirements = {
    letters: (value) => /\p{L}/u.test(value),
    numbers: (value) => /\p{N}/u.test(value),
    symbols: (value) => /[\p{Z}\p{S}\p{P}]/u.test(value),
    mixedCase: hasMixedCase,
};

/**
 * The first requirement the value breaks, as the same sentence the server
 * would have returned for it (or an empty string when it breaks none).
 */
const failureMessage = (value, policy) => {
    const { messages } = policy;

    if (value.length < policy.min) {
        return messages.min;
    }

    if (policy.max && value.length > policy.max) {
        return messages.max;
    }

    for (const [requirement, matches] of Object.entries(requirements)) {
        if (policy[requirement] && !matches(value)) {
            return messages[requirement];
        }
    }

    return "";
};

document.querySelectorAll(policySelector).forEach((field) => {
    const policy = JSON.parse(field.dataset.passwordPolicy);

    const validate = () => {
        // An empty value is left to `required`, exactly as the rule does.
        field.setCustomValidity(
            field.value === "" ? "" : failureMessage(field.value, policy),
        );
    };

    field.addEventListener("input", validate);
    field.addEventListener("blur", validate);
});
