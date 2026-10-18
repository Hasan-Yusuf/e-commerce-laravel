---
paths:
    - "./**"
---

# Database

## Comment the why, not the what

Write a class or method docblock only when it gives a reason the code cannot state itself — why the thing exists, why it is written this way, or a constraint it has to honour — and only where that cannot be understood from the code. Never restate what the code already says: no narrating the steps, the parameters or the return value. Add `@param`, `@return` or array-shape tags only where they tell the caller more than the signature does, such as units, an array's shape or a value's meaning.
