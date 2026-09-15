---
paths:
  - 'resources/js/pages/admin/**'
---

# Admin

## Preserve applied list context during mutations
Pass the server-applied filters and current page to management mutations, rather than unsubmitted filter form values. router.visit() mutations need explicit preserveState: true and preserveScroll: true; specifying a non-GET method alone does not inherit the state-preserving defaults of router.patch()/delete(). Keep filter controls synchronized with returned server filters.
