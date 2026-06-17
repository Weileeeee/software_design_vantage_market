-- =============================================================
-- Migration: Add image_url to Products
-- Run this once in phpMyAdmin if your Products table doesn't
-- already have an image_url column.
-- =============================================================

ALTER TABLE Products
ADD COLUMN image_url VARCHAR(512) DEFAULT NULL
COMMENT 'Direct URL to product image (e.g. https://example.com/photo.jpg)'
AFTER sku;

-- Optional: seed a few sample images
-- UPDATE Products SET image_url = 'https://via.placeholder.com/400x400.png?text=Product' WHERE image_url IS NULL;
