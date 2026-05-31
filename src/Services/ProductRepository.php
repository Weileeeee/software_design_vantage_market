<?php
// =============================================================
// VantageMarket — ProductRepository
// SRP: responsible for database persistence of Products only
// Observer Pattern: provides stock update + observer query methods
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use PDO;
use VantageMarket\Config\Database;
use VantageMarket\Models\Product;

/**
 * Handles all DB reads and writes for the Products table.
 *
 * In the Observer pattern this repository supports the Subject
 * (ProductStockSubject) by:
 *   1. Retrieving a product so its current stock can be checked.
 *   2. Persisting a new stock_level after a purchase or admin update.
 */
final class ProductRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    // Reads
    // ----------------------------------------------------------

    /**
     * Find a single product by its primary key.
     *
     * @param int $productId
     * @return Product|null  Null when the product does not exist
     */
    public function findById(int $productId): ?Product
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Products WHERE product_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $productId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Return all products that are line-items inside a given cart.
     *
     * Joins Cart_Items → Products so callers don't have to.
     *
     * @param int $cartId
     * @return Product[]
     */
    public function findByCartId(int $cartId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*
             FROM   Products p
             JOIN   Cart_Items ci ON ci.product_id = p.product_id
             WHERE  ci.cart_id = :cart_id'
        );
        $stmt->execute([':cart_id' => $cartId]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    /**
     * Fetch all active products (status = "active").
     *
     * @return Product[]
     */
    public function findAllActive(): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Products WHERE status = :status ORDER BY product_id ASC'
        );
        $stmt->execute([':status' => 'active']);

        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    // ----------------------------------------------------------
    // Writes
    // ----------------------------------------------------------

    /**
     * Overwrite the stock_level for a product.
     *
     * Called by the application layer (e.g. after a successful order)
     * BEFORE triggering ProductStockSubject::notify().
     *
     * @param int $productId
     * @param int $newStock   Must be >= 0
     */
    public function updateStock(int $productId, int $newStock): void
    {
        $this->db->prepare(
            'UPDATE Products
             SET    stock_level = :stock
             WHERE  product_id  = :id'
        )->execute([
            ':stock' => max(0, $newStock),
            ':id'    => $productId,
        ]);
    }

    /**
     * Decrement stock by a given quantity and return the resulting level.
     *
     * Prevents the stock from going below 0 via MySQL GREATEST().
     * The caller should pass the returned value to notify().
     *
     * @param int $productId
     * @param int $qty       Units to subtract (must be > 0)
     * @return int           New stock level after decrement
     */
    public function decrementStock(int $productId, int $qty): int
    {
        $this->db->prepare(
            'UPDATE Products
             SET    stock_level = GREATEST(0, stock_level - :qty)
             WHERE  product_id  = :id'
        )->execute([
            ':qty' => $qty,
            ':id'  => $productId,
        ]);

        // Return the freshly-updated stock level
        $stmt = $this->db->prepare(
            'SELECT stock_level FROM Products WHERE product_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $productId]);

        return (int) $stmt->fetchColumn();
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function hydrate(array $row): Product
    {
        return new Product(
            productId:       (int)  $row['product_id'],
            categoryId:      (int)  $row['category_id'],
            title:                  $row['title'],
            description:            $row['description'] ?? null,
            price:           (float) $row['price'],
            stockLevel:      (int)  $row['stock_level'],
            brand:                  $row['brand'] ?? null,
            sku:                    $row['sku'],
            status:                 $row['status']          ?? 'active',
            isDigital:       (bool) $row['is_digital'],
            isAgeRestricted: (bool) $row['is_age_restricted'],
            createdAt:              $row['created_at']      ?? '',
            updatedAt:              $row['updated_at']      ?? '',
        );
    }
}
