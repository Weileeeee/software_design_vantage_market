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

## 📂 Project Directory Structure

```plaintext
assignment/
├── config/
│   ├── auth.php                  # Authentication policy & cookie settings
│   └── database.php              # Database connection credentials (returns array)
├── public/
│   └── index.php                 # App entry point, routing table & HTTPS enforcement
├── src/
│   ├── Controllers/
│   │   └── AuthController.php    # Handles HTTP inputs, validation, and registers session flows
│   ├── Middleware/
│   │   └── AuthMiddleware.php    # Redirects/intercepts routes based on authorization state
│   ├── Config/
│   │   └── Database.php          # [SINGLETON] Database PDO Connection wrapper
│   ├── Exceptions/
│   │   └── AuthExceptions.php    # App-specific runtime exceptions (locked, duplicate email, etc.)
│   ├── Models/
│   │   └── User.php              # Immutable value object representing a User (SRP data container)
│   └── Services/
│       ├── SecurityLogger.php    # Logs failed logins, locks, and restricted keyword searches
│       ├── SessionManager.php    # Handles secure cookie generation, remember-me features, and session TTL
│       ├── UserLoginService.php  # Coordinates authentication checks, locking policies, and alerts
│       ├── UserMailer.php        # Sends transaction emails (welcome, reset, locked notification)
│       ├── UserRegistrationService.php # Standardizes registration logic, constraints, and persistence
│       ├── UserRepository.php    # Handles persistence / DB reads & writes for the Users table
│       └── UserValidator.php     # Validates user inputs (formatting rules, passwords, required fields)
├── UC04_UC05_Auth.php            # Merged bundle of authentication classes for simple testing/packaging
├── actors_table.txt              # Unified Actor-to-Use-Case table list
├── actor_user_stories_use_cases.txt # User Stories & detailed Use Case descriptions
├── auth_token_tables.sql         # SQL tables specifically for tracking tokens (remember-me, resets)
├── bootstrap.php                 # Dependency injection manager / PSR-4 autoloader wiring
├── PasswordResetService.php      # Independent service managing reset token states
├── vantagemarket_schema.sql      # Main Database Schema with seed data (Categories, Admin, Products)
└── README.md                     # This file
```

---

## 🗄️ Database Schema & Entities

The relational database is constructed in MySQL (`vantagemarket_schema.sql`) and enforces high data integrity.

### Core Tables
1. **Users**: Stores registered customers. Utilizes bcrypt-hashed passwords to fulfill the Single Responsibility Principle (SRP).
2. **Admin**: Stores administrative accounts. Used by the single administrative user/manager session.
3. **Products**: Contains the catalog inventory, price metadata, and status hooks. Supports full-text search indexing for queries.
4. **Shopping_Carts & Cart_Items**: Manages active guest cart sessions and persisted registered customer carts.
5. **Orders & Order_Items**: Captures snapshots of prices and quantities at checkout to ensure historical data immutability.
6. **Payments**: Connects transactional outcomes to orders, logging the payment strategy method used.
7. **Stock_Observers**: Junction table mapping which active carts are listening to stock changes of specific products (Observer Pattern link).
8. **Audit_Log**: Secure, immutable system logging changes made by administrators.
9. **Security_Log**: Logs malicious activities (failed login attempts, locked accounts, restricted keyword lookups).

### Pre-configured Views for Reporting
- `v_active_products`: Returns active products with their primary image and category names.
- `v_order_summary`: Aggregates order data, payment methods, transaction states, and shipping numbers.
- `v_low_stock`: Lists products with stock level $\le 10$ for easy inventory restoration.
- `v_sales_by_product`: Summarizes units sold and total revenues generated per product SKU.

---

## 🚀 Setup & Execution

### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0 or higher

### Installation
1. Clone the project workspace.
2. Import the main database schema to initialize the `vantagemarket` database:
   ```bash
   mysql -u root -p < vantagemarket_schema.sql
   ```
3. Set your environment variables in your server configure script or edit the default fallback settings directly inside `config/database.php`.

### Running the App
Start a local PHP development server from the root directory:
```bash
php -S localhost:8000 -t public
```
Navigate to:
- `http://localhost:8000/login` — Account Login Page
- `http://localhost:8000/register` — Registration Page
- `http://localhost:8000/dashboard` — Secure Customer Dashboard
- `http://localhost:8000/api/me` — Authenticated User Info API Endpoint
"# software_design_vantage_market" 
