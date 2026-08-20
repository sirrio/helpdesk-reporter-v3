# Helpdesk Reporter 3.0

A Laravel application for tutor attendance tracking and reporting.

Current release: **0.1.0**

## Tech Stack

- **Backend:** PHP 8.4, Laravel 13, Laravel Fortify
- **Frontend:** React 19, Inertia.js v3, Tailwind CSS v4
- **Testing:** Pest v4
- **Database:** MySQL

## Requirements

- PHP 8.4+
- Node.js 22.19+
- MySQL 8+
- [Laravel Herd](https://herd.laravel.com/) (recommended) or another local server

## Installation

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Copy environment file and configure
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed
```

## Development

```bash
# Start all dev services (Laravel + Vite)
composer run dev

# Or separately
php artisan serve
npm run dev
```

## Building for Production

```bash
npm run build
```

## Testing

```bash
# Run all tests
php artisan test --compact

# Run a specific test file or filter
php artisan test --compact --filter=AttendanceTest
```

## Code Quality

```bash
# Format PHP
vendor/bin/pint

# Lint & format JS/TS
npm run lint
npm run format

# Type check
npm run types:check
```

The complete release-ready check is:

```bash
composer ci:check
npm audit --audit-level=high
npm run build
```

## Deployment and releases

The public demo at <https://helpdesk-reporter.on-forge.com> is hosted on
DigitalOcean and managed through Laravel Forge. Forge watches `main` with Quick
Deploy enabled, so an approved pull-request merge also triggers a zero-downtime
production deployment.

All work uses `release/x.y.z` branches. After the required checks pass, the
release branch is merged into `main` with explicit approval. Forge installs
dependencies, applies forward-only migrations, builds the frontend, activates
the new release, and restarts queues. After successful production smoke tests,
the merge commit receives an annotated `vX.Y.Z` tag and matching GitHub release.

Application rollback uses a retained Forge release. Released database changes
are repaired with new forward migrations rather than destructive automated
rollbacks.

## License

This project is open-source software licensed under the [MIT license](LICENSE).
