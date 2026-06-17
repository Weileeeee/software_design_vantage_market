-- =============================================================
-- Fix: v_active_products view did not expose image_url
-- Run this in phpMyAdmin's SQL tab to update the view definition
-- =============================================================

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

-- Verify
SELECT product_id, title, image_url FROM v_active_products;
