---
paths:
  - 'app/Services/**'
---

# Services

## Do not define DTOs inside services
Services consume and return DTOs from App\DTOs; do not define data-transfer objects inside App\Services sub-namespaces. Add a new DTO to app/DTOs instead.
