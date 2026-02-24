# Kasica Prasica

A private household money and budget management application built with Laravel, Inertia.js, and React.

Kasica Prasica is a lightweight alternative to Firefly III, designed specifically for small households (2–3 users) who want full control over their financial data without relying on third-party SaaS platforms.

---

## ✨ Features

### Core
- Multi-account support (cash, bank, savings, credit)
- Income, expense, and transfer transactions
- Shared and personal accounts
- Category-based transaction organization
- Monthly budgeting
- Dashboard overview (income, expenses, net savings)

### Reporting
- Monthly summaries
- Category breakdown charts
- Account balance tracking
- Budget progress indicators

### Future Enhancements
- Recurring transactions
- CSV import
- Bank API integration
- Forecasting
- Receipt uploads
- PWA support

---

## 🛠 Tech Stack

### Backend
- Laravel 12
- PHP 8.2+ (8.4 recommended)
- MySQL
- Laravel Policies for authorization
- PHPStan for static analysis
- Pest PHP for testing

### Frontend
- Inertia.js v2
- React
- Tailwind CSS v4

---

## 🏗 Architecture

The application follows an Actions pattern to keep business logic out of controllers.


Key principles:
- Controllers remain thin
- Business logic lives in Actions
- Database transactions used for financial consistency
- Authorization handled via Policies

---

## Core Domain Models

### Users
Application users (private household members).

### Accounts
Financial containers such as:
- Cash
- Bank accounts
- Savings accounts
- Credit cards

Supports:
- Personal accounts
- Shared household accounts

### Categories
Income and expense classifications (e.g., Groceries, Rent, Salary).

### Transactions
Supports:
- Income
- Expense
- Transfers (internally linked transactions)

### Budgets
Monthly spending limits tied to categories.

---

## 🔐 Authentication

- Login-only system (no public registration)
- Users manually created by admin
- Laravel Fortify for authentication

---

## 💰 Financial Logic

Important principles:

- No stored running balances as single source of truth
- Account balance calculated from transaction sums
- Transfers create two linked transactions
- All financial writes wrapped in database transactions

---

## 🚀 Installation

### 1. Clone repository

```bash
git clone git@github.com:Lukicdev/kasica-prasica.git
cd kasica-prasica
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Build frontend

```bash
npm run dev
```
