---
paths:
  - '{app/Console/Commands,database/migrations,tests/Feature}/**'
---

# Feature

## Prepare legacy databases before consolidated migrations
A production database created by database/migrations/other must run `php artisan app:prepare-legacy-upgrade --dry-run` and then `php artisan app:prepare-legacy-upgrade` before regular migrations. Never mark the consolidated baseline manually: the command validates the complete known legacy history and required schema, and aborts on partial or unknown states.
