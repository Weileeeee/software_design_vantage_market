-- =============================================================
-- VantageMarket E-Commerce System
-- Complete Database Schema (Consolidated)
-- Course: CSE6234 Software Design | Group: TT5L_G2
--
-- This is the single source of truth for the database. It folds
-- 
--
-- Run this single file against an empty database to get a fully
-- working schema, no other .sql files required.
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- =============================================================
-- DATABASE
-- =============================================================

CREATE DATABASE IF NOT EXISTS vantagemarket
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vantagemarket;

-- =============================================================
-- TABLE: Categories
-- Stores product categories (e.g. Electronics, Clothing)
-- =============================================================

CREATE TABLE IF NOT EXISTS Categories (
    category_id   INT             NOT NULL AUTO_INCREMENT,
    category_name VARCHAR(100)    NOT NULL,

    PRIMARY KEY (category_id),
    UNIQUE KEY uq_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Users
-- Stores registered customer accounts
-- Password stored as bcrypt/Argon2 hash (SRP principle)
-- =============================================================

CREATE TABLE IF NOT EXISTS Users (
    user_id        INT             NOT NULL AUTO_INCREMENT,
    email_address  VARCHAR(255)    NOT NULL,
    password_hash  VARCHAR(255)    NOT NULL,
    first_name     VARCHAR(100)    NOT NULL,
    last_name      VARCHAR(100)    NOT NULL,
    is_guest       BOOLEAN         NOT NULL DEFAULT FALSE,
    is_locked      BOOLEAN         NOT NULL DEFAULT FALSE,
    failed_attempts TINYINT        NOT NULL DEFAULT 0,
    locked_until   DATETIME                 DEFAULT NULL,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    UNIQUE KEY uq_email (email_address),
    INDEX idx_email_lookup (email_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Remember_Tokens
-- UC04: Persistent "Remember Me" login tokens
-- Token stored as SHA-256 hash — raw token only goes in cookie
-- =============================================================

CREATE TABLE IF NOT EXISTS Remember_Tokens (
    token_id    INT             NOT NULL AUTO_INCREMENT,
    user_id     INT             NOT NULL,
    token_hash  VARCHAR(64)     NOT NULL,
    expires_at  DATETIME        NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (token_id),
    UNIQUE KEY uq_remember_user (user_id),       -- one token per user
    UNIQUE KEY uq_remember_hash (token_hash),
    CONSTRAINT fk_remember_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_remember_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Password_Reset_Tokens
-- UC04: Forgot-password flow — single-use, time-limited
-- =============================================================

CREATE TABLE IF NOT EXISTS Password_Reset_Tokens (
    token_id    INT             NOT NULL AUTO_INCREMENT,
    user_id     INT             NOT NULL,
    token_hash  VARCHAR(64)     NOT NULL,
    expires_at  DATETIME        NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (token_id),
    UNIQUE KEY uq_reset_user (user_id),          -- one pending reset per user
    UNIQUE KEY uq_reset_hash (token_hash),
    CONSTRAINT fk_reset_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reset_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Address
-- Stores shipping / billing addresses linked to users
-- One user can have multiple saved addresses
-- =============================================================

CREATE TABLE IF NOT EXISTS Address (
    address_id      INT             NOT NULL AUTO_INCREMENT,
    user_id         INT             NOT NULL,
    address_type    VARCHAR(20)     NOT NULL DEFAULT 'shipping'
                                    COMMENT 'shipping | billing',
    street_address  VARCHAR(255)    NOT NULL,
    city            VARCHAR(100)    NOT NULL,
    state           VARCHAR(100)    NOT NULL,
    postcode        INT             NOT NULL,
    country         VARCHAR(100)    NOT NULL DEFAULT 'Malaysia',
    is_default      BOOLEAN         NOT NULL DEFAULT FALSE,

    PRIMARY KEY (address_id),
    CONSTRAINT fk_address_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_address_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Admin
-- Singleton pattern: system enforces one active admin session
-- Separate table from Users for strict privilege separation
-- =============================================================

CREATE TABLE IF NOT EXISTS Admin (
    admin_id       INT             NOT NULL AUTO_INCREMENT,
    username       VARCHAR(100)    NOT NULL,
    password_hash  VARCHAR(255)    NOT NULL,
    email          VARCHAR(255)    NOT NULL,
    is_active      BOOLEAN         NOT NULL DEFAULT TRUE,
    last_login     DATETIME                 DEFAULT NULL,
    created_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (admin_id),
    UNIQUE KEY uq_admin_username (username),
    UNIQUE KEY uq_admin_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Products
-- Core product catalogue with inventory tracking
-- Observer pattern: stock changes notify Shopping Carts
-- =============================================================

CREATE TABLE IF NOT EXISTS Products (
    product_id          INT             NOT NULL AUTO_INCREMENT,
    category_id         INT             NOT NULL,
    title               VARCHAR(255)    NOT NULL,
    description         TEXT                     DEFAULT NULL,
    price               DECIMAL(10, 2)  NOT NULL,
    stock_level         INT             NOT NULL DEFAULT 0,
    brand               VARCHAR(100)             DEFAULT NULL,
    sku                 VARCHAR(100)    NOT NULL
                                        COMMENT 'Stock Keeping Unit — must be unique',
    image_url           VARCHAR(512)             DEFAULT NULL
                                        COMMENT 'Direct URL to product image (e.g. https://example.com/photo.jpg)',
    customer_rating     FLOAT                    DEFAULT NULL
                                        COMMENT 'Average rating 0.0 – 5.0',
    is_digital          BOOLEAN         NOT NULL DEFAULT FALSE,
    is_age_restricted   BOOLEAN         NOT NULL DEFAULT FALSE,
    status              VARCHAR(20)     NOT NULL DEFAULT 'active'
                                        COMMENT 'active | archived | inactive',
    created_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (product_id),
    UNIQUE KEY uq_sku (sku),
    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id) REFERENCES Categories (category_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_product_category (category_id),
    INDEX idx_product_status (status),
    INDEX idx_product_stock (stock_level),
    FULLTEXT INDEX ft_product_search (title, description, brand)
        COMMENT 'Supports keyword search (UC02)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Product_Images
-- Supports multiple high-resolution images per product (UC01)
-- =============================================================

CREATE TABLE IF NOT EXISTS Product_Images (
    image_id    INT             NOT NULL AUTO_INCREMENT,
    product_id  INT             NOT NULL,
    image_url   VARCHAR(512)    NOT NULL,
    is_primary  BOOLEAN         NOT NULL DEFAULT FALSE,
    sort_order  TINYINT         NOT NULL DEFAULT 0,

    PRIMARY KEY (image_id),
    CONSTRAINT fk_image_product
        FOREIGN KEY (product_id) REFERENCES Products (product_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_image_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Reviews
-- Customer product reviews linked to Users and Products
-- =============================================================

CREATE TABLE IF NOT EXISTS Reviews (
    review_id   INT             NOT NULL AUTO_INCREMENT,
    user_id     INT             NOT NULL,
    product_id  INT             NOT NULL,
    rating      TINYINT         NOT NULL COMMENT '1 – 5 stars',
    comment     VARCHAR(2000)            DEFAULT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (review_id),
    CONSTRAINT fk_review_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_review_product
        FOREIGN KEY (product_id) REFERENCES Products (product_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_review_product (product_id),
    INDEX idx_review_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Promotions
-- Discount codes managed by Admin (UC09)
-- Non-stacking rule enforced at application level
-- =============================================================

CREATE TABLE IF NOT EXISTS Promotions (
    promo_id        INT             NOT NULL AUTO_INCREMENT,
    code            VARCHAR(50)     NOT NULL COMMENT 'Auto-uppercased at app level',
    discount_value  DECIMAL(10, 2)  NOT NULL,
    discount_type   VARCHAR(20)     NOT NULL DEFAULT 'percentage'
                                    COMMENT 'percentage | flat_amount',
    min_spend       DECIMAL(10, 2)            DEFAULT NULL
                                    COMMENT 'Minimum cart subtotal to qualify',
    usage_limit     INT                       DEFAULT NULL
                                    COMMENT 'NULL = unlimited',
    usage_count     INT             NOT NULL DEFAULT 0,
    start_date      DATETIME        NOT NULL,
    expiry_date     DATETIME        NOT NULL,
    is_active       BOOLEAN         NOT NULL DEFAULT TRUE,
    created_by      INT                       DEFAULT NULL
                                    COMMENT 'FK to Admin.admin_id',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (promo_id),
    UNIQUE KEY uq_promo_code (code),
    CONSTRAINT fk_promo_admin
        FOREIGN KEY (created_by) REFERENCES Admin (admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_promo_dates CHECK (expiry_date > start_date),
    CONSTRAINT chk_discount_value CHECK (discount_value > 0),
    INDEX idx_promo_active (is_active, expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Promo_Category_Exclusions
-- Maps which categories are excluded from a promotion
-- Supports "New Arrivals / Gift Cards excluded" business rule
-- =============================================================

CREATE TABLE IF NOT EXISTS Promo_Category_Exclusions (
    exclusion_id  INT NOT NULL AUTO_INCREMENT,
    promo_id      INT NOT NULL,
    category_id   INT NOT NULL,

    PRIMARY KEY (exclusion_id),
    CONSTRAINT fk_excl_promo
        FOREIGN KEY (promo_id) REFERENCES Promotions (promo_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_excl_category
        FOREIGN KEY (category_id) REFERENCES Categories (category_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_promo_category (promo_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Shopping_Carts
-- Tracks active cart sessions for both guests and registered users
-- Guest carts use session_id; registered carts persist indefinitely
-- =============================================================

CREATE TABLE IF NOT EXISTS Shopping_Carts (
    cart_id      INT             NOT NULL AUTO_INCREMENT,
    user_id      INT                       DEFAULT NULL
                                 COMMENT 'NULL for guest carts',
    session_id   VARCHAR(255)             DEFAULT NULL
                                 COMMENT 'Browser session for guest carts',
    last_updated TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (cart_id),
    CONSTRAINT fk_cart_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_cart_user (user_id),
    INDEX idx_cart_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Cart_Items
-- Line items inside a shopping cart
-- Adding to cart does NOT reduce stock (UC03 business rule)
-- =============================================================

CREATE TABLE IF NOT EXISTS Cart_Items (
    cart_item_id  INT             NOT NULL AUTO_INCREMENT,
    cart_id       INT             NOT NULL,
    product_id    INT             NOT NULL,
    quantity      INT             NOT NULL DEFAULT 1,
    added_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (cart_item_id),
    CONSTRAINT fk_cartitem_cart
        FOREIGN KEY (cart_id) REFERENCES Shopping_Carts (cart_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_cartitem_product
        FOREIGN KEY (product_id) REFERENCES Products (product_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_cart_qty CHECK (quantity > 0),
    UNIQUE KEY uq_cart_product (cart_id, product_id),
    INDEX idx_cartitem_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Orders
-- Finalised orders created after successful payment (UC06)
-- Order details are immutable once status = 'confirmed'
-- =============================================================

CREATE TABLE IF NOT EXISTS Orders (
    order_id      INT             NOT NULL AUTO_INCREMENT,
    user_id       INT                       DEFAULT NULL
                                  COMMENT 'NULL if guest checkout',
    promo_id      INT                       DEFAULT NULL,
    address_id    INT             NOT NULL,
    subtotal      DECIMAL(10, 2)  NOT NULL,
    tax_amount    DECIMAL(10, 2)  NOT NULL DEFAULT 2.00,
    shipping_fee  DECIMAL(10, 2)  NOT NULL DEFAULT 4.00,
    total_amount  DECIMAL(10, 2)  NOT NULL,
    status        VARCHAR(30)     NOT NULL DEFAULT 'pending'
                                  COMMENT 'pending | confirmed | processing | shipped | delivered | cancelled | refunded',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (order_id),
    CONSTRAINT fk_order_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_order_promo
        FOREIGN KEY (promo_id) REFERENCES Promotions (promo_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_order_address
        FOREIGN KEY (address_id) REFERENCES Address (address_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_order_user (user_id),
    INDEX idx_order_status (status),
    INDEX idx_order_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Order_Items
-- Snapshot of items at time of purchase — prices are locked
-- Preserves order history even if product price changes later
-- =============================================================

CREATE TABLE IF NOT EXISTS Order_Items (
    order_item_id    INT             NOT NULL AUTO_INCREMENT,
    order_id         INT             NOT NULL,
    product_id       INT             NOT NULL,
    quantity         INT             NOT NULL,
    purchased_price  DECIMAL(10, 2)  NOT NULL
                                     COMMENT 'Price locked at time of purchase',

    PRIMARY KEY (order_item_id),
    CONSTRAINT fk_orderitem_order
        FOREIGN KEY (order_id) REFERENCES Orders (order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orderitem_product
        FOREIGN KEY (product_id) REFERENCES Products (product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_order_qty CHECK (quantity > 0),
    CONSTRAINT chk_purchased_price CHECK (purchased_price >= 0),
    INDEX idx_orderitem_order (order_id),
    INDEX idx_orderitem_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Payments
-- Records payment transaction per order
-- Strategy pattern: payment_method maps to the strategy class used
-- =============================================================

CREATE TABLE IF NOT EXISTS Payments (
    payment_id      INT             NOT NULL AUTO_INCREMENT,
    order_id        INT             NOT NULL,
    payment_method  VARCHAR(50)     NOT NULL
                                    COMMENT 'credit_card | ewallet | fpx | cod',
    amount          DECIMAL(10, 2)  NOT NULL,
    transaction_id  VARCHAR(255)             DEFAULT NULL
                                    COMMENT 'Reference ID from external gateway',
    status          VARCHAR(20)     NOT NULL DEFAULT 'pending'
                                    COMMENT 'pending | authorized | declined | refunded',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (payment_id),
    CONSTRAINT fk_payment_order
        FOREIGN KEY (order_id) REFERENCES Orders (order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_payment_order (order_id),
    INDEX idx_payment_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Shipping
-- Tracks shipment status per order via Courier API (UC07)
-- =============================================================

CREATE TABLE IF NOT EXISTS Shipping (
    shipping_id        INT             NOT NULL AUTO_INCREMENT,
    order_id           INT             NOT NULL,
    address_id         INT             NOT NULL,
    shipping_method    VARCHAR(100)    NOT NULL
                                       COMMENT 'e.g. NinjaVan Standard, J&T Express',
    tracking_number    VARCHAR(100)             DEFAULT NULL,
    courier_code       VARCHAR(50)              DEFAULT NULL
                                       COMMENT 'Internal code: NINJAVAN | JNT | DHL',
    status             VARCHAR(50)     NOT NULL DEFAULT 'processing'
                                       COMMENT 'processing | in_transit | out_for_delivery | delivered | failed',
    last_known_status  VARCHAR(255)             DEFAULT NULL
                                       COMMENT 'Cached from last Courier API call',
    estimated_delivery DATE                     DEFAULT NULL,
    delivered_at       DATETIME                 DEFAULT NULL,
    updated_at         TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (shipping_id),
    CONSTRAINT fk_shipping_order
        FOREIGN KEY (order_id) REFERENCES Orders (order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_shipping_address
        FOREIGN KEY (address_id) REFERENCES Address (address_id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY uq_shipping_order (order_id),
    INDEX idx_shipping_tracking (tracking_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Stock_Observers
-- Supports Observer pattern: tracks which carts are observing
-- which products so the app layer can notify on stock changes
-- =============================================================

CREATE TABLE IF NOT EXISTS Stock_Observers (
    observer_id  INT             NOT NULL AUTO_INCREMENT,
    product_id   INT             NOT NULL,
    cart_id      INT             NOT NULL,
    registered_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (observer_id),
    CONSTRAINT fk_observer_product
        FOREIGN KEY (product_id) REFERENCES Products (product_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_observer_cart
        FOREIGN KEY (cart_id) REFERENCES Shopping_Carts (cart_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_observer (product_id, cart_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Audit_Log
-- Immutable log of all Admin actions (UC08 business rule)
-- =============================================================

CREATE TABLE IF NOT EXISTS Audit_Log (
    log_id       INT             NOT NULL AUTO_INCREMENT,
    admin_id     INT                       DEFAULT NULL,
    action_type  VARCHAR(50)     NOT NULL
                                 COMMENT 'ADD_PRODUCT | EDIT_PRODUCT | ARCHIVE_PRODUCT | CREATE_PROMO | GENERATE_REPORT | ...',
    target_table VARCHAR(100)             DEFAULT NULL,
    target_id    INT                       DEFAULT NULL,
    changes_json JSON                      DEFAULT NULL
                                 COMMENT 'JSON diff of before/after values',
    ip_address   VARCHAR(45)              DEFAULT NULL,
    logged_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (log_id),
    CONSTRAINT fk_audit_admin
        FOREIGN KEY (admin_id) REFERENCES Admin (admin_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_audit_admin (admin_id),
    INDEX idx_audit_type (action_type),
    INDEX idx_audit_logged (logged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- TABLE: Security_Log
-- Records suspicious login attempts and restricted searches (UC02)
-- =============================================================

CREATE TABLE IF NOT EXISTS Security_Log (
    log_id      INT             NOT NULL AUTO_INCREMENT,
    user_id     INT                       DEFAULT NULL,
    event_type  VARCHAR(50)     NOT NULL
                                COMMENT 'FAILED_LOGIN | ACCOUNT_LOCKED | RESTRICTED_SEARCH | SESSION_MISMATCH',
    detail      VARCHAR(500)             DEFAULT NULL,
    ip_address  VARCHAR(45)              DEFAULT NULL,
    logged_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (log_id),
    CONSTRAINT fk_seclog_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_seclog_event (event_type),
    INDEX idx_seclog_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- =============================================================
-- VIEWS
-- Pre-built queries for common reporting needs (UC10)
-- =============================================================

-- View: Active products with category name (storefront catalogue)
CREATE OR REPLACE VIEW v_active_products AS
SELECT
    p.product_id,
    p.sku,
    p.title,
    p.description,
    p.price,
    p.stock_level,
    p.brand,
    p.customer_rating,
    p.is_digital,
    p.is_age_restricted,
    c.category_name,
    COALESCE(
        (SELECT pi.image_url
         FROM Product_Images pi
         WHERE pi.product_id = p.product_id AND pi.is_primary = TRUE
         LIMIT 1),
        p.image_url
    ) AS image_url,
    COALESCE(
        (SELECT pi.image_url
         FROM Product_Images pi
         WHERE pi.product_id = p.product_id AND pi.is_primary = TRUE
         LIMIT 1),
        p.image_url
    ) AS primary_image
FROM Products p
JOIN Categories c ON p.category_id = c.category_id
WHERE p.status = 'active';


-- View: Order summary with payment and shipping status (Admin reports)
CREATE OR REPLACE VIEW v_order_summary AS
SELECT
    o.order_id,
    o.created_at,
    o.status          AS order_status,
    o.total_amount,
    u.email_address   AS customer_email,
    CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
    pay.payment_method,
    pay.status        AS payment_status,
    pay.transaction_id,
    sh.status         AS shipping_status,
    sh.tracking_number,
    pr.code           AS promo_code,
    pr.discount_value AS discount_applied
FROM Orders o
LEFT JOIN Users           u   ON o.user_id    = u.user_id
LEFT JOIN Payments        pay ON o.order_id   = pay.order_id
LEFT JOIN Shipping        sh  ON o.order_id   = sh.order_id
LEFT JOIN Promotions      pr  ON o.promo_id   = pr.promo_id;


-- View: Low-stock products (Inventory Shortage Report)
CREATE OR REPLACE VIEW v_low_stock AS
SELECT
    p.product_id,
    p.sku,
    p.title,
    p.stock_level,
    c.category_name
FROM Products p
JOIN Categories c ON p.category_id = c.category_id
WHERE p.stock_level <= 10 AND p.status = 'active'
ORDER BY p.stock_level ASC;


-- View: Sales by product (Sales Report)
CREATE OR REPLACE VIEW v_sales_by_product AS
SELECT
    p.product_id,
    p.sku,
    p.title,
    SUM(oi.quantity)                      AS total_units_sold,
    SUM(oi.quantity * oi.purchased_price) AS total_revenue
FROM Order_Items oi
JOIN Products p ON oi.product_id = p.product_id
JOIN Orders   o ON oi.order_id   = o.order_id
WHERE o.status NOT IN ('cancelled', 'refunded')
GROUP BY p.product_id, p.sku, p.title
ORDER BY total_revenue DESC;


-- =============================================================
-- SEED DATA — Categories
-- =============================================================

INSERT INTO Categories (category_name) VALUES
    ('Electronics'),
    ('Computers & Accessories'),
    ('Mobile & Tablets'),
    ('Clothing & Apparel'),
    ('Books'),
    ('Home & Kitchen'),
    ('Sports & Outdoors'),
    ('Toys & Games'),
    ('Health & Beauty'),
    ('Gift Cards');


-- =============================================================
-- SEED DATA — Admin (Singleton)
-- Password: Admin@1234 (bcrypt hash — change before deployment)
-- =============================================================

-- Admin account: email must also exist in Users table for login to work.
-- Login at /signin with: admin@vantagemarket.com / Admin@1234
INSERT INTO Admin (username, password_hash, email) VALUES
    ('admin_leong',
     '$2y$10$xV1Fx0r3lNDiydwkqMqqKezwUa7lBjme3fJSxPYAAUcNTFD8cYoXe',
     'admin@vantagemarket.com');


-- =============================================================
-- SEED DATA — Sample Users
-- Passwords below:
--   admin@vantagemarket.com → Admin@1234  (also grants admin access)
--   alice@example.com       → User@1234
--   bob@example.com         → User@1234
-- =============================================================

INSERT INTO Users (email_address, password_hash, first_name, last_name) VALUES
    ('admin@vantagemarket.com',
     '$2y$10$xV1Fx0r3lNDiydwkqMqqKezwUa7lBjme3fJSxPYAAUcNTFD8cYoXe',
     'Admin', 'Leong'),
    ('alice@example.com',
     '$2y$12$K8GpQ4VbWZ3nP0YxX1z2BeO5j4LwCdT7sM8rN6hA9uFmE3iD1cVqI',
     'Alice', 'Tan'),
    ('bob@example.com',
     '$2y$12$K8GpQ4VbWZ3nP0YxX1z2BeO5j4LwCdT7sM8rN6hA9uFmE3iD1cVqI',
     'Bob', 'Chong');


-- =============================================================
-- SEED DATA — Sample Addresses
-- =============================================================

INSERT INTO Address (user_id, address_type, street_address, city, state, postcode, is_default) VALUES
    (1, 'shipping', '12 Jalan Cyber', 'Cyberjaya', 'Selangor', 63000, TRUE),
    (2, 'shipping', '5 Jalan Bukit Bintang', 'Kuala Lumpur', 'Wilayah Persekutuan', 55100, TRUE);


-- =============================================================
-- SEED DATA — Sample Products
-- =============================================================

INSERT INTO Products (category_id, title, description, price, stock_level, brand, sku) VALUES
    (2, 'Mechanical Keyboard',
        'Compact TKL mechanical keyboard with blue switches and RGB backlighting.',
        299.90, 12, 'KeyTech', 'KB-104'),
    (2, 'USB-C Hub',
        '7-in-1 USB-C hub with HDMI 4K, 3x USB-A, SD card reader, and PD charging.',
        49.90, 35, 'NexHub', 'HUB-07'),
    (1, 'Wireless Earbuds',
        'True wireless earbuds with active noise cancellation and 30hr battery life.',
        189.00, 20, 'SoundWave', 'EAR-201'),
    (3, 'Smartphone Stand',
        'Adjustable aluminium desk stand compatible with all smartphones and tablets.',
        39.90, 50, 'DeskMate', 'STD-015');


-- =============================================================
-- SEED DATA — Sample Promotions
-- =============================================================

INSERT INTO Promotions (code, discount_value, discount_type, min_spend, usage_limit, start_date, expiry_date, created_by) VALUES
    ('WELCOME10', 10.00, 'percentage',  50.00, 500,  '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1),
    ('SUMMER20',  20.00, 'percentage', 100.00, 200,  '2026-06-01 00:00:00', '2026-08-31 23:59:59', 1),
    ('FLAT15',    15.00, 'flat_amount', 80.00, NULL, '2026-01-01 00:00:00', '2026-12-31 23:59:59', 1);

-- Exclude Gift Cards category from all promotions
INSERT INTO Promo_Category_Exclusions (promo_id, category_id)
SELECT p.promo_id, c.category_id
FROM Promotions p, Categories c
WHERE c.category_name = 'Gift Cards';


-- =============================================================
-- RE-ENABLE FOREIGN KEY CHECKS
-- =============================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- END OF SCHEMA — VantageMarket (consolidated)
-- =============================================================
