---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Keep controllers thin
Keep controllers thin: read/validate the request, delegate to a service, and return a response. Put business logic, multi-step queries, search/filter pipelines, and view-data summaries in `App\Services`, not in controller private methods.
