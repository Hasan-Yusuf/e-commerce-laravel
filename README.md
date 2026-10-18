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

## Design Deep Dive

The sections below explain the structural decisions behind the codebase and the
trade-offs each one makes. They assume familiarity with the directory layout
described under [Architecture](#architecture).

### Exact money in integer subunits

Every amount is an integer. Money is stored in _fils_ — Bahraini dinars, where
1 BHD = 1,000 fils — so arithmetic is whole-number arithmetic that cannot drift
the way binary floating point does when `0.1 + 0.2` shows up on an invoice.

The single `Money` value object in `app/Support/Money.php` owns that convention:

```php
final class Money implements Stringable
{
    public const int FILS_PER_UNIT = 1000;

    public function add(self $other): self
    {
        return new self($this->fils + $other->fils);
    }
}
```

Models expose money columns (`price_in_fils`, `total_amount_in_fils`, and so on)
through the `App\Casts\AsMoney` cast, so a subtotal can be summed, have tax and
shipping added, and be read back with a currency without any view doing the
conversion. Percentages are centralised too: `Money::percentage()` converts a
rate to basis points and rounds half-up, and it is the one routine shared by
tax, discounts, and coupons so they all round identically. Display is likewise a
single point — `toBHD()` formats the number and appends the localised currency
string — so every price on the site agrees with every other because there is
exactly one place that decides how money reads.

### Pluggable payment gateways

Adding a payment method never touches checkout. A gateway is a small contract in
`app/Services/Payments/PaymentGateway.php`:

```php
interface PaymentGateway
{
    public function method(): PaymentMethod;

    public function createPaymentFor(Order $order): Payment;
}
```

Cash on delivery (`CodGateway`) is the only driver shipped, but a card or wallet
provider is just a new class implementing that interface plus one entry in
`config/payments.php`:

```php
'gateways' => [
    CodGateway::class,
],
```

`PaymentGatewayRegistry` resolves those drivers lazily — a driver is only
instantiated when its method is actually rendered or charged. Checkout validates
the submitted method against `methodValues()` and then asks the registry's
`gateway()` for the driver that writes the payment. Order creation therefore has
zero knowledge of which methods exist: adding Stripe, Tap, or a wallet later is
a driver plus a config line, never a checkout change.

### Order status as a state machine

An order status is not a bare string. `OrderStatus` (`app/Enums/OrderStatus.php`)
defines the legal moves in one place:

```php
private const array LEGAL_TRANSITIONS = [
    self::PENDING->value    => [self::PROCESSING, self::SHIPPED, self::DELIVERED, self::CANCELLED],
    self::PROCESSING->value => [self::SHIPPED, self::DELIVERED, self::CANCELLED],
    self::SHIPPED->value    => [self::REFUNDED],
    self::DELIVERED->value  => [self::REFUNDED],
    self::CANCELLED->value  => [self::PENDING],
    self::REFUNDED->value   => [self::PENDING],
];

public function canTransitionTo(self $to): bool
{
    return in_array($to, self::LEGAL_TRANSITIONS[$this->value] ?? [], true);
}
```

Both the admin panel's action buttons and the backend order pipelines ask
`canTransitionTo()` before doing anything. An illegal move is a no-op rather
than an error, so a double-click cannot corrupt state and the UI and backend can
never disagree. The enum also implements Filament's `HasColor`, `HasIcon`, and
`HasLabel`, pulling its display strings through the shared `HasTranslationKeys`
trait.

### Order placement as a pipeline

Order placement is a pipeline of focused steps, not one large method. Each
concern is a pipe in a Laravel pipeline:

```php
final class OrderPlacer extends OrderPipeline
{
    protected const array PIPES = [
        LockProducts::class,
        VerifyStock::class,
        VerifyOrderData::class,
        CreateOrder::class,
        RecordCouponUsage::class,
        RecalculateReservedStock::class,
        RecordPayment::class,
        NotifyLowStock::class,
    ];
}
```

`OrderPipeline::run()` sends a context through those pipes using Laravel's
`Pipeline`, wrapped in `withinTransaction()`, so the order row and the stock it
consumes commit or roll back together. A `PipesRan` guard then asserts the chain
did not stop short: it may only end early at a pipe boundary when a pipe said so
on purpose (for example, `VerifyStock` when the stock moved under the shopper).
Confirming, fulfilling, cancelling, refunding, reinstating, and reserving are all
subclasses contributing only their own list of pipes and one `__invoke()` — the
same shape for a different operation. `App\Services\Order\OrderService` is the
entry point to all of them.

### Guest-cart merge across session rotation

When a guest signs in, their session cart is merged into their account cart. The
trap is that authentication regenerates the session: by the time the user is
authenticated, `session()` no longer resolves the row the guest cart was stored
against. The merge therefore captures the session id before authentication and
passes it explicitly:

```php
public function merge(User $user, ?string $sessionId = null): void
{
    $guestCart = $this->resolver->forSession(
        $sessionId ?? $this->resolver->sessionId(),
    );
    // ...
}
```

`CartMerger` runs the fold inside one database transaction: duplicate products
are combined, quantities are clamped to what is actually sellable, and the guest
cart is deleted afterwards.

### Durable checkout state

A half-finished checkout survives navigation — and a dead session.
`CheckoutStateStore` treats the session as the working copy and mirrors it to the
database for signed-in shoppers:

```php
session()->put(self::SESSION_KEY, $payload);

CheckoutDraft::query()->updateOrCreate(
    ['user_id' => $userId],
    ['state' => $payload],
);
```

A shopper who abandons checkout and returns days later — after the session that
started it has expired — still gets their draft back, provided it is within the
cart's own lifetime (`STORED_LIFETIME_DAYS` follows `CartService::USER_LIFETIME_DAYS`).
The state itself is the immutable `CheckoutState` DTO; the store is responsible
only for _where_ it lives, never for _what_ it contains.

### Data-driven product specifications

Specifications are data, not code. A product type (`TypeSpec`) declares its specs
— name, unit, value type, and filter type — and products store their values in
localised JSON columns (`specs_en` / `specs_ar`) keyed by spec name. The value
type is a `SpecValueType` enum (`text`, `number`, `options`, `boolean`), and the
filter type is a `SpecFilterType` enum (`list` for exact matches, `range` for a
numeric min/max).

The storefront's filter UI and its query both read a value through the same
`SpecValueQuery`, so they cannot disagree about how a stored value is shaped.
When an admin edits a type's schema, `ProductSpecSyncService` detects whether the
products are now out of step: a rename is carried over automatically, but a
re-typed or removed value is flagged (`specs_need_review`) for a human to rebuild
on the "update the products" page rather than being silently guessed.

### Pluggable catalog filters

Search-and-filter code tends to become one large `where()` soup. Here each filter
is a class behind a one-method interface:

```php
interface ProductFilter
{
    public function params(): array;

    public function apply(Builder $query, array $params): void;
}
```

`BrandFilter`, `CategoryFilter`, `PriceRangeFilter`, `AvailabilityFilter`,
`DiscountedFilter`, and `SpecFilter` each declare the request parameters that
activate them and apply themselves to the query. Adding a filter is a new class;
the central `ProductQueryService` never changes. Each filter is individually
unit-testable, which keeps the filtering surface readable as it grows.

### Bilingual and RTL localisation

The storefront runs in English and Arabic, which means full right-to-left layout,
not just a translated header. Localisation is threaded through the domain rather
than bolted on: products carry specs in both `specs_en` and `specs_ar`;
validation messages are translated; and even the enums that drive the admin
panel pull their labels from translation files through the shared
`HasTranslationKeys` trait. Money renders the same way everywhere because the
`Money` value object is the single formatting point. The behaviour — the
language switcher and translated routes — is exercised by tests as a first-class
concern, not merely by the presence of translation files.

### Layered architecture

The structural lesson of the codebase is unglamorous but durable: keep the HTTP
layer thin and put thought into the domain.

- **Controllers** read and validate the request, then delegate.
- **Services** hold the business logic — search, pricing, checkout, cart, and
  orders — grouped by domain under `app/Services`.
- **DTOs** (`app/DTOs`) are immutable value objects passed between controllers,
  services, and views, so no raw array flows across those boundaries.
- **Enums** (`app/Enums`) centralise fixed vocabularies — roles, statuses, and
  delivery methods — shared by storefront and admin.
- **Policies** (`app/Policies`) enforce authorization, with role-aware middleware
  guarding the panel.
- **Routes** are split one file per storefront section under `routes/`.

The result is a codebase where each idea lives in an obvious place and can be
understood in isolation.

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
