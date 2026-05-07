# Helpdesk Reporter 3.0

A Laravel application for tutor attendance tracking and reporting.

## Tech Stack

- **Backend:** PHP 8.4, Laravel 13, Laravel Fortify
- **Frontend:** React 19, Inertia.js v3, Tailwind CSS v4
- **Testing:** Pest v4
- **Database:** MySQL

## Requirements

- PHP 8.4+
- Node.js 20+
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

## License

This project is open-source software licensed under the [MIT license](LICENSE).
