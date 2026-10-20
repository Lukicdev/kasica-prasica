# Kasica Prasica

A shared household budget. Everyone in the household sees the same accounts, transactions, and monthly plan, and can enter spending as it happens.

## Stack

- PHP 8.3, Laravel
- React, Inertia, Vite, Tailwind CSS
- SQLite by default

## Setup

```bash
composer setup
```

That installs PHP and Node dependencies, copies `.env.example` to `.env`, generates the app key, runs migrations, and builds the frontend.

## Development

```bash
composer dev
```

The app is served at the URL in `APP_URL` (see `.env`).

## Tests

```bash
composer test
```
