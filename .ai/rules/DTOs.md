---
paths:
    - "app/DTOs/**"
---

# DTOs

## DTOs live in App\DTOs

Data-transfer objects are final readonly classes under the App\DTOs namespace, one class per file. This is the shared home for DTOs across models and services; keep constructing them there rather than nesting them in other namespaces.
