---
paths:
  - 'resources/views/**/*.blade.php'
---

# Views

## Class attributes read component-first
In a storefront `class` attribute, write the component/BEM classes first, then a `|` separator, then the utility classes — e.g. `class="cart-item__total-label | visually-hidden"`.

The pipe is a human-only separator: it is never a CSS selector for anything we own and is never read by `classList` logic. The browser treats it as an unmatched class name and ignores it, so it changes no styling.
