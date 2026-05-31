<?php
// =============================================================
// VantageMarket — Cart Model (SRP: data container only)
// Observer Pattern: Observer's identity (cart_id)
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Models;

/**
 * Immutable value object representing a Shopping_Carts row.
 * Holds data only — no business logic (SRP).
 *
 * Used by the Observer pattern: CartObserver targets specific
 * carts (by cart_id) when removing out-of-stock items.
 */
final class Cart
{
    public function __construct(
        public readonly int     $cartId,
        public readonly ?int    $userId,
        public readonly ?string $sessionId,
        public readonly string  $lastUpdated = '',
    ) {}

    /**
     * Returns true if this is a guest (unauthenticated) cart.
     */
    public function isGuestCart(): bool
    {
        return $this->userId === null;
    }

    /** Returns a safe public array for API responses. */
    public function toPublicArray(): array
    {
        return [
            'cart_id'      => $this->cartId,
            'user_id'      => $this->userId,
            'session_id'   => $this->sessionId,
            'is_guest'     => $this->isGuestCart(),
            'last_updated' => $this->lastUpdated,
        ];
    }
}
