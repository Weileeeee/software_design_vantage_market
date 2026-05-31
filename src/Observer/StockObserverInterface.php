<?php
// =============================================================
// VantageMarket — StockObserverInterface
// Observer Pattern: Observer (Listener) contract
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Observer;

/**
 * Observer interface for the stock-level Observer pattern.
 *
 * Concrete observers (e.g. CartObserver) implement this interface
 * and are called by the Subject whenever a relevant state change occurs.
 *
 * Design Pattern: Observer (GoF)
 */
interface StockObserverInterface
{
    /**
     * Receive a stock-level update notification from the Subject.
     *
     * The concrete observer decides what action to take.
     * When $newStock === 0 the observer should remove the product
     * from all watching carts and deregister itself.
     *
     * @param int $productId  The product whose stock level changed
     * @param int $newStock   The new (current) stock level after the change
     */
    public function update(int $productId, int $newStock): void;
}
