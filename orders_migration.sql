-- =============================================================
-- VantageMarket — Orders Migration
-- Run this in phpMyAdmin → SQL tab (or import as .sql file)
-- =============================================================

CREATE TABLE IF NOT EXISTS Orders (
    order_id     INT          UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          UNSIGNED NULL,          -- NULL for guest orders
    session_id   VARCHAR(128) NULL,                   -- guest identifier
    status       ENUM('pending','processing','shipped','delivered')
                              NOT NULL DEFAULT 'pending',
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS Order_Items (
    order_item_id      INT     UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id           INT     UNSIGNED NOT NULL,
    product_id         INT     UNSIGNED NOT NULL,
    quantity           INT     UNSIGNED NOT NULL DEFAULT 1,
    price_at_purchase  DECIMAL(10,2) NOT NULL,   -- snapshot of price at checkout

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)   REFERENCES Orders(order_id)  ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE RESTRICT
);

-- Index for fast per-user and per-session order lookups
CREATE INDEX idx_orders_user_id    ON Orders (user_id);
CREATE INDEX idx_orders_session_id ON Orders (session_id);
CREATE INDEX idx_order_items_order ON Order_Items (order_id);
