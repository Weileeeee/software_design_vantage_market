# VantageMarket — E-Commerce System (CSE6234 Software Design)

An Amazon-inspired, web-based e-commerce platform implementing modern software design principles, robust database schemas, and standard OOP design patterns.

**Course:** CSE6234 Software Design  
**Group:** TT5L_G2  

---

## 🏗️ Design Patterns Implemented

The platform leverages three primary design patterns to achieve loose coupling, high cohesion, and scalable architectures:

| Design Pattern | Component / Module | Implementation Detail | Benefit |
| :--- | :--- | :--- | :--- |
| **Singleton Pattern** | Database Connection Manager (`Database.php`) | Centralized connection instance via `Database::getInstance()`. Restricts instantiation to a single, globally accessible `PDO` database handle. | Prevents connection overhead and database namespace pollution. Uses lazy initialization to save memory. |
| **Strategy Pattern** | Payment Processing Subsystem (`Payments` / Checkout) | Dynamically chooses payment strategies (Credit Card, E-Wallet, FPX Banking) at runtime via an interchangeable gateway interface. | Adheres to the **Open/Closed Principle (OCP)**. New payment gateways can be added without modifying the core checkout engine. |
| **Observer Pattern** | Catalog & Shopping Cart Notifications (`Stock_Observers`) | Automatically registers active shopping carts as observers of product listings. When stock level drops to zero, the Subject (`Product`) triggers notifications to detach/grey out items. | Real-time stock consistency across user carts without polling resources. |

---

## ✨ Features

| Feature | Description |
| :--- | :--- |
| **User Authentication** | Register, login, logout with secure bcrypt-hashed passwords and remember-me cookies |
| **Guest Shopping** | Browse and add items to cart without an account (session-based cart) |
| **Add to Favourites** | Heart button on every product card (homepage & catalogue) — persisted in `localStorage`, synced across pages |
| **Shopping Cart** | Add, remove, and view items; cart persists between pages for both guests and logged-in users |
| **Product Catalogue** | Full-text search, category filter, price range, and stock-level filter |
| **Checkout** | Strategy Pattern payment (Credit Card, E-Wallet, FPX Banking, Cash on Delivery) |
| **Observer Stock Alerts** | Carts auto-detach when a product goes out of stock via the Observer Pattern |
| **Sign In / Sign Out** | Navbar links on every page; guests see Sign In + Register, logged-in users see Sign Out |

---

## 📂 Project Directory Structure

```plaintext
assignment/
├── config/
│   ├── auth.php                  # Authentication policy & cookie settings
│   └── database.php              # Database connection credentials (returns array)
├── public/
│   ├── index.php                 # App entry point, routing table & HTTPS enforcement
│   ├── .htaccess                 # Apache rewrite rules — routes all requests through index.php
│   └── css/
│       ├── homepage.css          # Shared styles for homepage, cart, catalogue
│       └── auth.css              # Styles for login, register, and password-reset pages
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php    # Handles HTTP inputs, validation, and registers session flows
│   │   └── CatalogController.php # Handles product listing, search, and filter logic
│   ├── Middleware/
│   │   └── AuthMiddleware.php    # Redirects/intercepts routes based on authorization state
│   ├── Config/
│   │   └── Database.php          # [SINGLETON] Database PDO Connection wrapper
│   ├── Exceptions/
│   │   └── AuthExceptions.php    # App-specific runtime exceptions (locked, duplicate email, etc.)
│   ├── Models/
│   │   ├── User.php              # Immutable value object representing a User (SRP data container)
│   │   ├── Product.php           # Product model
│   │   └── Cart.php              # Cart model
│   ├── Observer/
│   │   ├── StockObserverInterface.php
│   │   └── StockSubjectInterface.php
│   └── Services/
│       ├── CartObserver.php        # [OBSERVER] Removes out-of-stock items from cart
│       ├── CartRepository.php      # Handles persistence / DB reads & writes for Shopping_Carts
│       ├── CheckoutSession.php     # [STRATEGY] Coordinates payment execution
│       ├── PasswordResetService.php# Manages reset token states
│       ├── ProductRepository.php   # Reads product data from DB
│       ├── ProductStockSubject.php # [OBSERVER Subject] Notifies carts on stock changes
│       ├── SecurityLogger.php      # Logs failed logins, locks, and restricted keyword searches
│       ├── SessionManager.php      # Handles secure cookie generation, remember-me, and session TTL
│       ├── StockObserverRepository.php
│       ├── UserLoginService.php    # Coordinates authentication checks, locking policies, and alerts
│       ├── UserMailer.php          # Sends transaction emails (welcome, reset, locked notification)
│       ├── UserRegistrationService.php # Registration logic, constraints, and persistence
│       ├── UserRepository.php      # Handles persistence / DB reads & writes for the Users table
│       └── UserValidator.php       # Validates user inputs (formatting rules, passwords, required fields)
├── views/
│   ├── homepage.php              # Home page with featured products, favourites, and categories
│   ├── catalogue_view.php        # Product catalogue with search, filters, and add-to-favourite
│   ├── cart.php                  # Shopping cart page
│   ├── checkout.php              # Checkout with payment strategy selection
│   ├── likes.php                 # Saved favourites / liked products page
│   ├── login.php                 # Sign-in form (AJAX + graceful fallback error handling)
│   ├── register.php              # Registration form
│   ├── forgot_password.php       # Request password reset
│   └── reset_password.php        # Set new password via token
├── bootstrap.php                 # Dependency injection manager / PSR-4 autoloader wiring
├── auth_token_tables.sql         # SQL tables for remember-me and reset tokens
├── vantagemarket_schema.sql      # Main database schema with seed data
└── README.md                     # This file
```

---

## 🗄️ Database Schema & Entities

The relational database is constructed in MySQL (`vantagemarket_schema.sql`) and enforces high data integrity.

### Core Tables
1. **Users**: Stores registered customers. Utilizes bcrypt-hashed passwords (SRP).
2. **Admin**: Stores administrative accounts.
3. **Products**: Catalog inventory, price metadata, and status hooks. Supports full-text search.
4. **Shopping_Carts & Cart_Items**: Manages guest (session-based) and registered user carts.
5. **Orders & Order_Items**: Immutable snapshot of prices and quantities at checkout.
6. **Payments**: Logs payment strategy method used per order.
7. **Stock_Observers**: Junction table — maps active carts to product stock (Observer Pattern).
8. **Audit_Log**: Immutable admin action log.
9. **Security_Log**: Failed login attempts, locked accounts, restricted keyword lookups.

### Pre-configured Views
- `v_active_products` — Active products with category names.
- `v_order_summary` — Aggregated order data, payment methods, and shipping numbers.
- `v_low_stock` — Products with stock ≤ 10 for inventory management.
- `v_sales_by_product` — Units sold and revenue per product SKU.

---

## ❤️ Add to Favourites

Each product card on both the **Homepage** (Featured Products) and the **Catalogue** page displays a heart button (❤️). Clicking it:

1. Toggles the product in/out of your favourites list.
2. Shows a toast notification confirming the action.
3. Persists the favourites list in the browser's `localStorage` — state is restored on every page visit.
4. The `/likes` page displays your saved favourites.

No login is required to use favourites — they are stored locally in the browser.

---

## 🚀 Setup & Execution

### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0 or higher
- Apache with `mod_rewrite` enabled (for `.htaccess` URL routing)

### Installation

1. Clone the repository.

2. Import the main database schema:
   ```bash
   mysql -u root -p < vantagemarket_schema.sql
   mysql -u root -p < auth_token_tables.sql
   ```

3. Configure your database credentials in `config/database.php`.

4. **Enable Apache `mod_rewrite`** (required for clean URLs like `/signin`, `/cart`, etc.):
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```
   Ensure your Apache virtual host has `AllowOverride All` set so `.htaccess` is respected.

### Running the App

**Option A — PHP built-in server (development):**
```bash
php -S localhost:8000 -t public
```

**Option B — Apache/Nginx (production):** Point your document root to the `public/` folder. The included `public/.htaccess` handles all URL routing automatically.

### Pages

| URL | Description |
| :--- | :--- |
| `http://localhost:8000/` | Homepage with featured products and categories |
| `http://localhost:8000/catalog` | Full product catalogue with search & filters |
| `http://localhost:8000/cart` | Shopping cart |
| `http://localhost:8000/checkout` | Checkout & payment |
| `http://localhost:8000/likes` | Favourites page |
| `http://localhost:8000/signin` | Sign-in page |
| `http://localhost:8000/register` | Registration page |
| `http://localhost:8000/api/me` | Authenticated user info (JSON) |

---

## 🐛 Known Issues & Fixes Applied

| Issue | Root Cause | Fix |
| :--- | :--- | :--- |
| Cart empty on `/cart` page | `global $cartItems` in `cart.php` overwrote the closure-scoped variable with `null` | Removed `global` declaration; variable is correctly inherited from include scope |
| `/signin` redirecting to homepage | Missing `public/.htaccess` — Apache served files directly, bypassing `index.php` router | Added `public/.htaccess` with `RewriteRule` to route all requests through `index.php` |
| Login showing "Network error" for 401/423 | `catch` block fired on any error; no distinction between HTTP errors and actual network failures | Added per-status-code error messages and a safe `res.json()` parse with try/catch |
| Sign Out link not working | `AuthController::logout()` returned JSON for all requests, but the navbar calls it via a plain GET `<a href>` | Fixed logout to detect request type — redirects directly for plain GET, returns JSON for AJAX |
| No Sign In/Out on cart page | Cart page had a stripped-down header with no navbar | Replaced with full topbar + mid-header + navbar matching homepage |
