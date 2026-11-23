# Kasica Prasica

A shared household budget. Everyone in the household sees the same accounts, transactions, and monthly plan, and can enter spending as it happens.

## Features

- **Shared workspace**: Any number of users in a household see the same live accounts, budgets, and transactions.
- **Accounts & balances**: Cash, bank, and credit accounts with live balances updated dynamically from transactions, along with account archiving and balance reconciliation.
- **Assets & net worth**: Track tangible assets (real estate, vehicles, valuables) with valuations (fixed, appreciation/depreciation, estimated market value) and monitor household net worth over time.
- **Transactions & transfers**: Income and expense tracking with amount, currency, category, date, account, member, and notes. Transfers between accounts with currency conversion and balance adjustments.
- **Recurring transactions**: Scheduled transactions automatically rolled forward into each new month.
- **Categories & monthly budgets**: Income and expense categories with custom icons and colors, paired with monthly budgets carried forward from the previous month.
- **Monthly overview**: Instant view of monthly income, spending, budget targets, savings rate, and daily balance flows.
- **Insights & trends**: 12-month income vs. spending trends, month-over-month comparisons, and net worth evolution charts.
- **Multi-currency support**: Set a workspace base currency with per-user display currency preferences and daily automated exchange rates from Frankfurter.
- **Built-in MCP server**: Model Context Protocol endpoint for AI assistants (like Claude or Cursor) to query budget status or record expenses and income directly.
- **Passkey authentication**: Fast, biometric, passwordless login with WebAuthn passkeys in addition to traditional credentials.

## Stack

- **Backend**: PHP 8.3+ / 8.4, Laravel 13, Laravel Fortify, Laravel Passport, Laravel MCP
- **Frontend**: React 19, Inertia.js v3, Vite 8 (Vite+), Tailwind CSS v4, Radix UI, Recharts, Lucide Icons
- **Database**: SQLite by default (configured with WAL mode and foreign key constraints)
- **Testing & Quality**: Pest PHP 5, PHPStan / Larastan, Laravel Pint

## Setup

```bash
composer setup
```

This installs Composer and npm dependencies, copies `.env.example` to `.env` (if not already present), generates the application key, runs migrations, and builds frontend assets.

Registration is closed by default. Seed the demo household to populate initial data and sign in:

```bash
php artisan db:seed
```

This sets up a demo workspace with four months of sample transaction history, categories, and budgets, creating two users (password: `password`):

- `test@example.com` (Admin)
- `partner@example.com`

## Development

Start the development server and frontend bundler:

```bash
composer dev
```

The app is served at the URL configured in `APP_URL` (see `.env`, default: `http://kasica-prasica.test`).

### Exchange Rates

Exchange rates are fetched from Frankfurter once a day. Run the scheduler locally to handle periodic updates:

```bash
php artisan schedule:work
```

Or fetch rates on demand:

```bash
php artisan exchange-rates:fetch
```

The demo household defaults to RSD as its base currency and enables EUR. Additional currencies can be added or switched under **Settings > Currencies**.

## Model Context Protocol (MCP)

Kasica Prasica includes a built-in MCP server exposed at `/mcp` (authenticated via Laravel Passport OAuth). This allows AI assistants and agents to safely interact with your budget:

- `get_overview`: Fetches current monthly income, spending, category budget performance, savings rate, balances, and balance flow.
- `create_expense`: Records an expense transaction for today with specified account and expense category.
- `create_income`: Records an income transaction for today with specified account and income category.

## Code Quality & Testing

Run the full test and lint suite:

```bash
composer test
```

Individual checks and tools:

```bash
# PHP linting and formatting (Laravel Pint)
composer lint
composer lint:check

# PHP static analysis (PHPStan / Larastan)
composer types:check

# Frontend linting and type checking (Vite+ / TypeScript)
npm run check
npm run check:fix
npm run types:check

# Comprehensive CI check (runs npm check, type checks, and Pest tests)
composer ci:check
```
