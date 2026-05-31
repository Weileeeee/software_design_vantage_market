<?php
// =============================================================
// VantageMarket — StockObserverRepository
// SRP: manages the Stock_Observers junction table only
// Observer Pattern: persistence layer for Subject's observer list
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use PDO;
use VantageMarket\Config\Database;

/**
 * Handles all DB reads and writes for the Stock_Observers table.
 *
 * Stock_Observers is the persistence-layer representation of the
 * Subject's observer list.  Every (product_id, cart_id) pair in
 * this table means "that cart is currently watching that product".
 *
 * Called by:
 *  - ProductStockSubject::attach()  → register()
 *  - ProductStockSubject::detach()  → deregister()
 *  - ProductStockSubject::notify()  → findCartsByProduct()
 *  - CartObserver::update()         → deregisterAll() when stock hits 0
 */
class StockObserverRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    // Writes
    // ----------------------------------------------------------

    /**
     * Register a cart as an observer of a product.
     *
     * Uses INSERT IGNORE so duplicate registrations are silently skipped
     * (the UNIQUE KEY uq_observer in the schema guarantees idempotency).
     *
     * @param int $productId  Product to watch
     * @param int $cartId     Cart that wants notifications
     */
    public function register(int $productId, int $cartId): void
    {
        $this->db->prepare(
            'INSERT IGNORE INTO Stock_Observers (product_id, cart_id)
             VALUES (:product_id, :cart_id)'
        )->execute([
            ':product_id' => $productId,
            ':cart_id'    => $cartId,
        ]);
    }

    /**
     * Remove a single (product, cart) observer registration.
     *
     * Called when a user manually removes an item from their cart,
     * or when an order is completed (cart checked out).
     *
     * @param int $productId
     * @param int $cartId
     */
    public function deregister(int $productId, int $cartId): void
    {
        $this->db->prepare(
            'DELETE FROM Stock_Observers
             WHERE product_id = :product_id
               AND cart_id   = :cart_id'
        )->execute([
            ':product_id' => $productId,
            ':cart_id'    => $cartId,
        ]);
    }

    /**
     * Remove ALL observer registrations for a product.
     *
     * Called by CartObserver::update() after the product goes out of
     * stock — no cart needs to keep watching a product with 0 units.
     *
     * @param int $productId
     */
    public function deregisterAll(int $productId): void
    {
        $this->db->prepare(
            'DELETE FROM Stock_Observers WHERE product_id = :product_id'
        )->execute([':product_id' => $productId]);
    }

    /**
     * Remove all observer entries for a specific cart.
     *
     * Useful when a cart is fully checked out or abandoned.
     *
     * @param int $cartId
     */
    public function deregisterCart(int $cartId): void
    {
        $this->db->prepare(
            'DELETE FROM Stock_Observers WHERE cart_id = :cart_id'
        )->execute([':cart_id' => $cartId]);
    }

    // ----------------------------------------------------------
    // Reads
    // ----------------------------------------------------------

    /**
     * Return all cart_ids currently observing a product.
     *
     * Used by ProductStockSubject::notify() to fan out the update call.
     *
     * @param int $productId
     * @return int[]  Array of cart IDs
     */
    public function findCartsByProduct(int $productId): array
    {
        $stmt = $this->db->prepare(
            'SELECT cart_id
             FROM   Stock_Observers
             WHERE  product_id = :product_id'
        );
        $stmt->execute([':product_id' => $productId]);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'cart_id');
    }

    /**
     * Return all product_ids being observed by a specific cart.
     *
     * Useful for displaying which items in a cart are "live-watched".
     *
     * @param int $cartId
     * @return int[]  Array of product IDs
     */
    public function findProductsByCart(int $cartId): array
    {
        $stmt = $this->db->prepare(
            'SELECT product_id
             FROM   Stock_Observers
             WHERE  cart_id = :cart_id'
        );
        $stmt->execute([':cart_id' => $cartId]);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'product_id');
    }

    /**
     * Check whether a specific (product, cart) observer pair exists.
     *
     * @param int $productId
     * @param int $cartId
     * @return bool
     */
    public function isRegistered(int $productId, int $cartId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM Stock_Observers
             WHERE product_id = :product_id AND cart_id = :cart_id'
        );
        $stmt->execute([
            ':product_id' => $productId,
            ':cart_id'    => $cartId,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }
}
