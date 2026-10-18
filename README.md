# Nexus

A full-stack e-commerce application built with Laravel and Filament. It pairs a
public, server-rendered storefront with an administrative panel, covering the
complete store workflow: browsing a product catalog, managing a cart and
wishlist, checking out, placing orders, and administering inventory, customers,
orders, and promotions from a single backend.

The project is intended as a general-purpose reference implementation of a
modern Laravel storefront and admin panel, demonstrating idiomatic framework
usage, clean architecture, and a thorough automated test suite.

---

## Overview

Nexus is a general-purpose e-commerce store. Customers browse a bilingual
catalog of products organized into categories and subcategories, filter and
search the catalog, add items to a cart or wishlist, and complete a multi-step
checkout. Administrators use a Filament panel to manage products, brands,
categories, customers, countries, coupons, discounts, banners, and orders, with
dashboard metrics that summarise the store at a glance.

The application is server-rendered with Blade and Livewire — there is no
separate public HTTP API. All money is stored as integer subunits — fils of the
Bahraini dinar (BHD), 1 BHD = 1,000 fils — to avoid floating-point rounding
errors, and the storefront is localised in English and Arabic (including
right-to-left layout). The demo data models a Gulf storefront: Bahraini
localities, Gulf shipping countries, and fictional customers, brands, and
products.

---

## Features

### Storefront

- **Catalog browsing** — categories and subcategories with breadcrumbs,
  product listings, grid and list views, and pagination.
- **Search and filtering** — full-text product search plus filters for brand,
  category, price, availability, discounts, and per-type specifications.
- **Product pages** — media gallery, localised specifications, stock levels,
  discounts, and related-product recommendations.
- **Cart and wishlist** — session-backed for guests, with automatic merge into
  the shopper's account when they register or sign in.
- **Checkout** — a multi-step flow (customer details, shipping address,
  delivery method, review) whose progress is persisted as a draft so a
  half-finished order survives navigation.
- **Payments** — a pluggable payment-gateway abstraction with cash-on-delivery
  implemented out of the box.
- **Account area** — profile editing, phone numbers validated against the
  selected country's numbering plan, shipping addresses, preferred language,
  and order history.
- **Contact form** — validated messages persisted and forwarded to the store
  by email.
- **Localisation** — English and Arabic interfaces with RTL support, shared
  language switcher, and translated validation messages.

### Admin panel (Filament)

- **Dashboard** — statistics overview, revenue chart, low-stock products,
  orders requiring attention, and recent orders, with a shared period filter.
- **Resource management** — Users, Products, Categories, Brands, Types,
  Countries, Coupons, Discounts, Banners, Orders, and Contact Messages.
- **Media library** — Spatie Media Library integration with WebP conversion
  and responsive image variants.
- **Exports** — table exports for selected resources.
- **Notifications** — database-backed notifications for administrators.

### Cross-cutting

- **Exact money** — all amounts stored as integer subunits and computed with a
  `Money` value object, eliminating floating-point arithmetic errors.
- **Order lifecycle** — a defined status model with legal transitions between
  states.
- **Scheduled tasks** — a daily command prunes expired carts.
- **Low-stock warnings** — an email notification when product stock falls
  below its threshold.
- **Testing** — a large PHPUnit suite covering both feature behaviour and unit
  logic.

---

## Technology Stack

| Layer            | Technology                                                                                  |
| ---------------- | ------------------------------------------------------------------------------------------- |
| Runtime          | PHP 8.3+ (developed against 8.4)                                                            |
| Framework        | Laravel 13                                                                                  |
| Admin panel      | Filament 5 (built on Livewire 4)                                                            |
| Frontend         | Blade, Livewire, Alpine.js, Tailwind CSS 4, Vite 8                                          |
| Database         | SQLite (default); MySQL, MariaDB, PostgreSQL and SQL Server connections provided by Laravel |
| Media            | Spatie Media Library 11                                                                     |
| Money            | Custom `Money` value object with integer subunit arithmetic                                 |
| Testing          | PHPUnit 12, Mockery                                                                         |
| Formatting       | Laravel Pint                                                                                |
| Static analysis  | Mago                                                                                        |
| JS/Blade tooling | Prettier with Blade and Tailwind plugins                                                    |
| Dev tooling      | Laravel Debugbar, Laravel Pail, Laravel Boost                                               |

Only SQLite has been exercised in this repository; other database drivers are
available through standard Laravel configuration but have not been verified
here.

---

## Architecture

The application follows Laravel conventions with a thin-controller,
service-oriented design.

- **Models** live in `app/Models` and define the domain schema and Eloquent
  relationships. Factories and seeders produce realistic, deterministic demo
  data.
- **Controllers** (`app/Http/Controllers`) read and validate the request, then
  delegate to services. Business logic, multi-step queries, search and filter
  pipelines, and view-data assembly live in `app/Services`.
- **DTOs** (`app/DTOs`) are immutable value objects passed between controllers,
  services, and views.
- **Enums** (`app/Enums`) centralise roles, statuses, and other fixed vocabularies
  shared between the storefront and the admin panel.
- **Policies** (`app/Policies`) enforce authorization, while the admin panel is
  additionally guarded by role-aware middleware.
- **Filament** (`app/Filament`) houses the panel provider, resources, pages,
  widgets, tables, forms, infolists, and exports.
- **Routes** are split into one file per storefront section (`routes/`) and
  loaded into the `web` group; the admin panel is served from `/admin`.
- **Jobs and scheduling** — the `carts:prune` command is scheduled daily; the
  database queue driver and jobs table are configured for background work.

Money is represented as integer subunits throughout the database
(`price_in_fils`, `total_amount_in_fils`, etc.), and converted for display only
at the presentation layer.

---

## Demo

No live deployment or screenshots are included. The seeders produce a complete
demo store — categories, products, customers, orders, and promotions — that can
be inspected locally after running the installation steps below.

Demo administrator accounts created by the seeders:

| Email                  | Password   | Purpose                      |
| ---------------------- | ---------- | ---------------------------- |
| `admin@example.com`    | `password` | English-locale administrator |
| `admin-ar@example.com` | `password` | Arabic-locale administrator  |

The administrator panel is available at `/admin`.

Sanitized screenshots can be added later under a `docs/` directory and linked
from this section.

---

## Requirements & Installation

### Prerequisites

- PHP 8.3 or newer with the extensions required by Laravel
- Composer 2
- Node.js 20 or newer (for Vite)
- A database — SQLite is the default and needs no server

### Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Create the environment file and application key
cp .env.example .env
php artisan key:generate

# 3. Create the SQLite database (skip if using another driver)
touch database/database.sqlite

# 4. Run migrations
php artisan migrate

# 5. (Optional) Seed demo data
php artisan db:seed

# 6. Install frontend dependencies and build assets
npm install
npm run build

# 7. Start the development server
php artisan serve
```

For a live-reloading frontend during development, run `npm run dev` (or
`composer run dev`, which starts the server, queue worker, and Vite together)
instead of `npm run build`.

On Windows, use `copy .env.example .env` in place of `cp`, and `type nul >
database\database.sqlite` in place of `touch`.

### Seeding

`php artisan db:seed` populates the database with reference data (countries,
brands, categories, types, products, coupons, discounts, banners) and demo
people (administrators, customers, addresses, orders, payments). Seeding is
idempotent: running it again updates existing records rather than duplicating
them.

---

## Configuration & Deployment

The application uses a standard Laravel `.env` file. Key settings:

- `APP_NAME` — the application name.
- `APP_ENV` / `APP_DEBUG` — set `APP_ENV=production` and `APP_DEBUG=false` in
  production.
- `DB_CONNECTION` and related `DB_*` variables — the database connection.
- `STORE_PHONE`, `STORE_PHONE_HREF`, `STORE_INSTAGRAM_URL`,
  `STORE_TIKTOK_URL` — storefront contact details shown in the footer and the
  contact page.
- `MAIL_*` — mail configuration for the contact form and low-stock warnings.
  `MAIL_CONTACT_ADDRESS` optionally overrides where contact messages are sent.

### Production checklist

- Serve the `public/` directory as the document root.
- Disable debug mode and never commit `.env` or other secrets.
- Use a dedicated production database and run `php artisan migrate --force`.
- Compile assets with `npm run build`.
- Run `php artisan config:cache`, `route:cache`, and `view:cache` where
  supported.
- Configure the queue worker and scheduler (`php artisan schedule:work`) for
  the `carts:prune` scheduled task.
- The cash-on-delivery gateway requires no external credentials. Adding a card
  or wallet method means implementing the `App\Services\Payments\PaymentGateway`
  interface and registering it in `config/payments.php`; the checkout flow
  itself does not change.

---

## Testing & Code Quality

```bash
# Run the test suite
php artisan test

# Format PHP code
vendor/bin/pint

# Run static analysis
vendor/bin/mago lint

# Format Blade, JavaScript and CSS
npx prettier --write .
```

The repository includes PHPUnit feature tests (HTTP flows, Filament resources,
Livewire components) and unit tests (services, value objects, enums). Formatting
is enforced with Pint and Prettier, and Mago provides static analysis with a
project-specific ruleset in `mago.toml`.

Checks that have passed in the current working tree are noted in the project's
change history; run the commands above to verify against a fresh checkout.

---

## Project Structure

```
app/
  Console/        Artisan commands (scheduled tasks)
  DTOs/           Immutable data-transfer objects
  Enums/          Roles, statuses, and shared vocabularies
  Filament/       Admin panel resources, pages, widgets, tables, forms
  Http/           Controllers, middleware, and form requests
  Mail/           Mailable classes
  Models/         Eloquent models and traits
  Notifications/  Notification classes
  Observers/      Model observers
  Policies/       Authorization policies
  Services/       Business logic, grouped by domain
  Support/        Shared helpers and traits
config/           Application configuration
database/         Migrations, factories, and seeders
lang/             Translation strings (en, ar)
public/           Web root and compiled assets
resources/        Blade views, CSS, and JavaScript
routes/           Route definitions, split by storefront section
tests/            PHPUnit feature and unit tests
```

---

## Limitations & Future Improvements

- **Single payment method** — only cash-on-delivery is implemented; card and
  wallet payments require a new gateway driver.
- **No public HTTP API** — the storefront is server-rendered; an API resource
  layer could be added for headless or mobile clients.
- **SQLite-first** — the database layer is portable, but non-SQLite drivers
  have not been exercised here.
- **No live deployment or screenshots** — the project ships with seeders and a
  local setup instead.
- **Fixed demo market** — the store is denominated in BHD and seeded with
  Bahraini and Gulf localities. Generalising the currency or geography is a
  code and seed-data change, not a configuration toggle.
