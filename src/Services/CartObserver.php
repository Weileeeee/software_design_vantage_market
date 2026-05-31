<?php
// =============================================================
// VantageMarket — CartObserver  (Concrete Observer)
// Observer Pattern: receives stock-change notifications from the Subject
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Observer\StockObserverInterface;

/**
 * Concrete Observer for the stock-level Observer pattern.
 *
 * Registered inside ProductStockSubject and called whenever a product's
 * stock level changes.  When the stock reaches 0 this observer:
 *   1. Removes the out-of-stock item from every watching cart.
 *   2. Deregisters all observer entries for that product so no
 *      further notifications are sent.
 *
 * Design Pattern: Observer (GoF) — Concrete Observer role
 *
 * Dependencies (injected via constructor — DIP):
 *   - CartRepository           — to remove items from carts
 *   - StockObserverRepository  — to deregister observer entries
 */
final class CartObserver implements StockObserverInterface
{
    public function __construct(
        private CartRepository          $cartRepository,
        private StockObserverRepository $observerRepository,
    ) {}

    // ----------------------------------------------------------
    // StockObserverInterface
    // ----------------------------------------------------------

    /**
     * Receive a stock-level change notification from the Subject.
     *
     * Behaviour:
     *  - stock > 0  → no action required (item is still available).
     *  - stock === 0 → remove the product from every watching cart and
     *                  deregister all observer entries for the product.
     *
     * @param int $productId  The product whose stock just changed
     * @param int $newStock   The new (current) stock level
     */
    public function update(int $productId, int $newStock): void
    {
        if ($newStock > 0) {
            // Product still has stock — carts keep their item, no action needed.
            return;
        }

        // Stock has hit zero: gather all carts currently watching this product.
        $cartIds = $this->observerRepository->findCartsByProduct($productId);

        if (empty($cartIds)) {
            return;
        }

        foreach ($cartIds as $cartId) {
            $this->removeItemFromCart((int) $cartId, $productId);
        }

        // Once all carts have been updated, clean up every observer entry
        // for this product — no further notifications are needed.
        $this->observerRepository->deregisterAll($productId);

        $this->log(
            $productId,
            count($cartIds),
            "Product #{$productId} is out of stock — removed from " . count($cartIds) . " cart(s) and deregistered all observers."
        );
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    /**
     * Remove a product from a single cart.
     *
     * Wrapped in a try/catch so a failure for one cart does not
     * prevent the remaining carts from being updated.
     *
     * @param int $cartId
     * @param int $productId
     */
    private function removeItemFromCart(int $cartId, int $productId): void
    {
        try {
            $this->cartRepository->removeItem($cartId, $productId);

            $this->log(
                $productId,
                1,
                "Removed product #{$productId} from cart #{$cartId} (out of stock)."
            );
        } catch (\Throwable $e) {
            // Logging only — observer must not crash the main request flow.
            error_log(
                "[CartObserver] Failed to remove product #{$productId} "
                . "from cart #{$cartId}: " . $e->getMessage()
            );
        }
    }

    /**
     * Write a structured log entry so stock-notification events are traceable.
     *
     * @param int    $productId
     * @param int    $affectedCarts
     * @param string $message
     */
    private function log(int $productId, int $affectedCarts, string $message): void
    {
        error_log(
            sprintf(
                '[CartObserver] product_id=%d affected_carts=%d | %s',
                $productId,
                $affectedCarts,
                $message
            )
        );
    }
}
