---
paths:
  - '{app/Actions/Fortify,app/Http/Controllers/AdminUserController.php,app/Http/Middleware,app/Http/Responses,config/fortify.php,routes}/**'
---

# Responsesconfig

## Keep authentication mail-free and approval-based
Self-registration creates pending accounts that cannot authenticate until an admin approves them. Do not enable email verification or mail-based password recovery. Admin password resets issue a temporary password, revoke sessions and 2FA state, and require the user to choose a new password at next login.
