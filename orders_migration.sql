-- =============================================================
-- VantageMarket — Orders Migration (fixed foreign keys)
-- Run this in phpMyAdmin → SQL tab (or import as .sql file)
-- =============================================================

CREATE TABLE IF NOT EXISTS Orders (
    order_id     INT          AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NULL,                       -- NULL for guest orders
    session_id   VARCHAR(128) NULL,                       -- guest identifier
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
    order_item_id      INT     AUTO_INCREMENT PRIMARY KEY,
    order_id           INT     NOT NULL,
    product_id         INT     NOT NULL,
    quantity           INT     NOT NULL DEFAULT 1,
    price_at_purchase  DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)   REFERENCES Orders(order_id)  ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE RESTRICT
);

-- Indexes
CREATE INDEX idx_orders_user_id    ON Orders (user_id);
CREATE INDEX idx_orders_session_id ON Orders (session_id(64));
CREATE INDEX idx_order_items_order ON Order_Items (order_id);
