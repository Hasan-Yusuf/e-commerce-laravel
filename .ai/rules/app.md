---
paths:
  - 'app/**'
---

# App

## Suffix variables that hold a service with `Service`

A variable, parameter, or promoted property that holds a service instance must be named with a `Service` suffix, so a reader can tell a service apart from a model or a plain value. Name it after the domain rather than the class: `ProductPricingService $pricingService`, `CartService $cartService`, `OrderService $orderService`, `WishlistService $wishlistService`. Avoid `$productPricingService`, and never leave a service in a bare name like `$pricing`.
