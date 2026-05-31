<?php
// =============================================================
// VantageMarket — StockSubjectInterface
// Observer Pattern: Subject (Observable) contract
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Observer;

/**
 * Subject interface for the stock-level Observer pattern.
 *
 * A Subject (Product) maintains a list of registered observers
 * (Shopping Carts) and notifies them when its state changes.
 *
 * Design Pattern: Observer (GoF)
 */
interface StockSubjectInterface
{
    /**
     * Register a shopping cart as an observer of a specific product.
     *
     * Called when a cart item is added so the cart is alerted
     * if the product goes out of stock.
     *
     * @param int $productId  The product being observed
     * @param int $cartId     The cart that wishes to observe it
     */
    public function attach(int $productId, int $cartId): void;

    /**
     * Unregister a shopping cart from observing a product.
     *
     * Called when a cart item is removed or the cart is checked out.
     *
     * @param int $productId  The product to stop observing
     * @param int $cartId     The cart to remove from the observer list
     */
    public function detach(int $productId, int $cartId): void;

    /**
     * Notify all registered observers that a product's stock has changed.
     *
     * Iterates over every cart registered in Stock_Observers for this
     * product and calls StockObserverInterface::update() on each.
     *
     * @param int $productId  The product whose stock level just changed
     * @param int $newStock   The new (current) stock level
     */
    public function notify(int $productId, int $newStock): void;
}
