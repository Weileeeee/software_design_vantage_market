<?php
// =============================================================
// VantageMarket — ProductStockSubject  (Concrete Subject)
// Observer Pattern: maintains the observer list & dispatches notifications
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Observer\StockSubjectInterface;
use VantageMarket\Observer\StockObserverInterface;

/**
 * Concrete Subject for the stock-level Observer pattern.
 *
 * Implements StockSubjectInterface and acts as the central hub for
 * stock-change events.  The observer list is persisted in the
 * Stock_Observers database table (via StockObserverRepository) so
 * registrations survive across HTTP requests.
 *
 * Typical lifecycle:
 *  1. addItem (CartRepository::addItem)
 *       → ProductStockSubject::attach(productId, cartId)
 *
 *  2. Checkout / purchase (ProductRepository::decrementStock)
 *       → ProductStockSubject::notify(productId, newStock)
 *       → CartObserver::update(productId, 0)   ← if newStock === 0
 *       → CartRepository::removeItem for each watching cart
 *       → StockObserverRepository::deregisterAll(productId)
 *
 *  3. removeItem (CartRepository::removeItem)
 *       → ProductStockSubject::detach(productId, cartId)
 *
 * Design Pattern: Observer (GoF) — Concrete Subject role
 *
 * Dependencies (injected via constructor — DIP):
 *   - StockObserverRepository   — persists the observer list
 *   - StockObserverInterface    — the single concrete observer (CartObserver)
 */
final class ProductStockSubject implements StockSubjectInterface
{
    public function __construct(
        private StockObserverRepository $observerRepository,
        private StockObserverInterface  $observer,
    ) {}

    // ----------------------------------------------------------
    // StockSubjectInterface
    // ----------------------------------------------------------

    /**
     * Register a cart as an observer of a product.
     *
     * Called when a product is added to a shopping cart so the cart
     * will receive a notification if that product's stock drops to 0.
     *
     * Delegates persistence to StockObserverRepository — INSERT IGNORE
     * guarantees idempotency; double-registrations are safe.
     *
     * @param int $productId  Product to watch
     * @param int $cartId     Cart that is adding the product
     */
    public function attach(int $productId, int $cartId): void
    {
        $this->observerRepository->register($productId, $cartId);
    }

    /**
     * Unregister a cart from observing a product.
     *
     * Called when a user manually removes an item from their cart or
     * when checkout is completed (the cart no longer needs alerts).
     *
     * @param int $productId
     * @param int $cartId
     */
    public function detach(int $productId, int $cartId): void
    {
        $this->observerRepository->deregister($productId, $cartId);
    }

    /**
     * Notify the registered observer of a stock-level change.
     *
     * Passes $newStock to the observer; it is the observer's responsibility
     * to decide what action to take (e.g. remove items only when stock === 0).
     *
     * Design note: the observer list is fetched inside CartObserver::update()
     * via StockObserverRepository::findCartsByProduct() so this Subject does
     * not need to duplicate that query — it simply delegates to the observer.
     *
     * @param int $productId  The product whose stock changed
     * @param int $newStock   The new stock level (>= 0)
     */
    public function notify(int $productId, int $newStock): void
    {
        $this->observer->update($productId, $newStock);
    }

    // ----------------------------------------------------------
    // Convenience facade
    // ----------------------------------------------------------

    /**
     * Convenience method: decrement stock and notify in one call.
     *
     * Delegates the actual DB decrement to ProductRepository then
     * immediately calls notify() with the resulting stock level.
     *
     * @param int                $productId
     * @param int                $qty            Units to subtract (> 0)
     * @param ProductRepository  $productRepo    Injected by caller
     * @return int                               New stock level after decrement
     */
    public function decrementAndNotify(
        int               $productId,
        int               $qty,
        ProductRepository $productRepo
    ): int {
        $newStock = $productRepo->decrementStock($productId, $qty);
        $this->notify($productId, $newStock);

        return $newStock;
    }
}
