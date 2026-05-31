<?php
// =============================================================
// VantageMarket — Product Model (SRP: data container only)
// Observer Pattern: Subject's state (stock_level)
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Models;

/**
 * Immutable value object representing a Products row.
 * Holds data only — no business logic (SRP).
 *
 * Used by the Observer pattern: ProductStockSubject reads and
 * updates the stock_level field, then notifies cart observers.
 */
final class Product
{
    public function __construct(
        public readonly int     $productId,
        public readonly int     $categoryId,
        public readonly string  $title,
        public readonly ?string $description,
        public readonly float   $price,
        public readonly int     $stockLevel,
        public readonly ?string $brand,
        public readonly string  $sku,
        public readonly string  $status        = 'active',
        public readonly bool    $isDigital      = false,
        public readonly bool    $isAgeRestricted = false,
        public readonly string  $createdAt      = '',
        public readonly string  $updatedAt      = '',
    ) {}

    /**
     * Returns true when this product has no remaining stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->stockLevel <= 0;
    }

    /**
     * Returns a safe public-facing array (used by API / cart responses).
     */
    public function toPublicArray(): array
    {
        return [
            'product_id'       => $this->productId,
            'category_id'      => $this->categoryId,
            'title'            => $this->title,
            'description'      => $this->description,
            'price'            => $this->price,
            'stock_level'      => $this->stockLevel,
            'brand'            => $this->brand,
            'sku'              => $this->sku,
            'status'           => $this->status,
            'is_digital'       => $this->isDigital,
            'is_age_restricted'=> $this->isAgeRestricted,
            'is_out_of_stock'  => $this->isOutOfStock(),
        ];
    }
}
