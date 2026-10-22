# Kasica Prasica

A shared household budget. Everyone in the household sees the same accounts, transactions, and monthly plan, and can enter spending as it happens.

## Features

- Shared workspace: any number of users see the same live numbers
- Accounts (cash, bank, card) with balances that update from transactions
- Income and expense transactions with amount, currency, category, date, account, person, and note
- Transfers between accounts and balance reconciliation
- Recurring transactions carried into each new month
- Expense categories with a monthly budget, copied forward from the previous month
- Monthly overview of income, spending, budget, and leftover
- Insights: month-over-month comparison and spending trends
- RSD and EUR, with a switch to show figures in either currency

## Stack

- PHP 8.3+, Laravel
- React, Inertia, Vite, Tailwind CSS
- SQLite by default

## Setup

```bash
composer setup
```

That installs PHP and Node dependencies, copies `.env.example` to `.env`, generates the app key, runs migrations, and builds the frontend.

Registration is off, so seed the demo household to sign in:

```bash
php artisan db:seed
```

That creates two people in one workspace, both with the password `password`:

- `test@example.com`
- `partner@example.com`

## Development

```bash
composer dev
```

The app is served at the URL in `APP_URL` (see `.env`).

## Tests

```bash
composer test
```
