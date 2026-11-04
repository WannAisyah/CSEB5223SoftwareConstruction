# Online Local Mart System (OLMS)

OLMS is a Laravel storefront for a local mart. Customers can browse and search products, manage a personal cart, check out, and follow their orders. Administrators use a separate account to manage the catalogue, customers, orders, and payments.

## How it works

- Customers register, sign in, update their profile and saved delivery address, and see only their own cart and orders.
- The catalogue shows product details, prices, stock, and images. Administrators can add, edit, and remove products; removal preserves existing cart and order references.
- Cart totals are calculated from product prices. Checkout checks availability, saves the delivery address on the order, creates a full-amount **pending** payment, and clears the cart.
- An administrator marks a payment **paid** after confirming it outside the app. Stock decreases once when payment becomes paid, not when an order moves to processing. A paid payment can be reversed before order completion, restoring stock.
- Customers can view their order history and payment status. Administrators manage order states and line items.

There is no live payment gateway. Category filtering is described in the group's planning document but is not implemented yet.

## Tests

Run the full suite with `php artisan test --compact`, or run the eight module groups and generate a Markdown report with `composer test:modules`. For a single group, use a command such as `composer test:payments`. The generated report is at `storage/app/test-reports/module-report.md` and is Git-ignored.

These are Laravel feature tests using an in-memory SQLite database; they do not change the configured MySQL database. They do not replace browser or live-MySQL integration testing.

## Install and run locally

Requirements: PHP 8.4+, Composer 2, MySQL/MariaDB, Node.js, and npm. Create an empty MySQL database (the example configuration calls it `olms`).

1. Clone the repository and install dependencies:

   ```powershell
   git clone https://github.com/WannAisyah/CSEB5223SoftwareConstruction.git
   cd CSEB5223SoftwareConstruction
   git switch feature/olms-customer-shopping
   composer install
   npm install
   ```

   The checkout step is needed until this feature branch is merged into `master`.

2. Create your local configuration and set `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`. Set `APP_URL` to the address you will use (`http://olms.test` for the example Laragon host, or `http://127.0.0.1:8000` for `php artisan serve`):

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Apply migrations, expose uploaded images, and build frontend assets:

   ```powershell
   php artisan migrate
   php artisan storage:link
   npm run build
   ```

4. Open the configured Laragon host, or start the development server with `php artisan serve`. Customers can register from the storefront. Administrator accounts are separate and must be provisioned by the project team.

On an **existing** database, back it up before running migrations: the stock-reconciliation migration updates inventory for existing paid orders. Only run `php artisan db:seed` on a disposable development database; it creates demonstration accounts, orders, and products.
