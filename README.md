# Online Local Mart System (OLMS)

OLMS is a Laravel application for managing products, customers, carts, orders, order items, and payments for a local market.

## Requirements

- PHP 8.4+
- Composer 2
- MySQL / MariaDB
- Node.js and npm (only for Vite asset builds)

## Setup

1. Install dependencies:

   ```powershell
   composer install
   npm install
   ```

2. Copy `.env.example` to `.env` if needed, then set the MySQL connection values. The local configuration uses the `olms` database.

3. Generate an application key and apply migrations:

   ```powershell
   php artisan key:generate
   php artisan migrate
   ```

4. Optionally load demonstration data:

   ```powershell
   php artisan db:seed
   ```

5. Start the application through Laragon or run:

   ```powershell
   php artisan serve
   ```

## Main Modules

- Products: browse, search, create, edit, and delete products. Products already used by an order are protected from deletion.
- Customers: create and edit customer profiles. Passwords are hashed before storage and may be left blank during an edit to keep the existing password.
- Carts and cart items: each customer has one cart. Adding, editing, or removing a cart item automatically recalculates the cart total.
- Orders and order items: orders record a customer, delivery address, date, and status. Order item changes automatically recalculate the order total.
- Payments: one payment may be recorded for each order.

Each module has index, create, show, and edit routes. Create and edit forms include CSRF protection, retained values after validation errors, field-level messages, relationship dropdowns, cancellation links, and success messages.

## Database Relationships

- Customer has one Cart and many Orders.
- Cart belongs to Customer and has many CartItems.
- CartItem belongs to Cart and Product.
- Product has many CartItems and OrderItems.
- Order belongs to Customer, has many OrderItems, and has one Payment.
- OrderItem belongs to Order and Product.
- Payment belongs to Order.

Categories are intentionally outside this project’s scope.

## Tests

Run the automated feature tests with:

```powershell
php artisan test --compact
```

The tests use an in-memory SQLite database, so they do not alter the configured MySQL `olms` data.
