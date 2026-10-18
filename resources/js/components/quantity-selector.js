/* Quantity selector: stepper buttons (±) that clamp the bound number input.
   Used on the product page; the cart quantity input is handled by Livewire. */

document.querySelectorAll("[data-quantity-selector]").forEach((selector) => {
    const input = selector.querySelector(".quantity-selector__input");
    const decrease = selector.querySelector("[data-quantity-decrease]");
    const increase = selector.querySelector("[data-quantity-increase]");

    if (!input) {
        return;
    }

    const minimum = Number.parseInt(input.getAttribute("min") ?? "1", 10);
    const maximum = input.hasAttribute("max")
        ? Number.parseInt(input.getAttribute("max"), 10)
        : null;

    const clamp = (value) => {
        if (Number.isNaN(value)) {
            return minimum;
        }

        let result = Math.max(minimum, value);

        if (maximum !== null) {
            result = Math.min(maximum, result);
        }

        return result;
    };

    const current = () => Number.parseInt(input.value, 10) || minimum;

    // Announced so `wire:model` (and any other listener) sees stepper changes:
    // assigning input.value alone fires no event.
    const setValue = (value) => {
        input.value = String(value);
        input.dispatchEvent(new Event("input", { bubbles: true }));
    };

    decrease?.addEventListener("click", () => {
        setValue(clamp(current() - 1));
    });

    increase?.addEventListener("click", () => {
        setValue(clamp(current() + 1));
    });

    input.addEventListener("change", () => {
        setValue(clamp(Number.parseInt(input.value, 10)));
    });
});
