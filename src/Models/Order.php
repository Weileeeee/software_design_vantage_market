<?php
// =============================================================
// VantageMarket — Order Model (SRP: data container only)
// Represents a row in the Orders table
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Models;

/**
 * Immutable value object representing an Orders row.
 * Holds data only — no business logic (SRP).
 */
final class Order
{
    // Valid status transitions (enforced by OrderRepository)
    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered'];

    public function __construct(
        public readonly int     $orderId,
        public readonly ?int    $userId,
        public readonly ?string $sessionId,
        public readonly string  $status,
        public readonly float   $totalAmount,
        public readonly string  $createdAt    = '',
        public readonly string  $updatedAt    = '',
    ) {}

    /**
     * Returns true if this order can still be cancelled
     * (only pending or processing orders can be cancelled).
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'processing'], true);
    }

    /**
     * Returns true if the order has been fully delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Returns a numeric progress step (1–4) for the tracker UI.
     */
    public function progressStep(): int
    {
        return match ($this->status) {
            'pending'    => 1,
            'processing' => 2,
            'shipped'    => 3,
            'delivered'  => 4,
            default      => 1,
        };
    }

    /** Returns a safe public-facing array (used by API / view responses). */
    public function toPublicArray(): array
    {
        return [
            'order_id'     => $this->orderId,
            'user_id'      => $this->userId,
            'session_id'   => $this->sessionId,
            'status'       => $this->status,
            'total_amount' => $this->totalAmount,
            'progress'     => $this->progressStep(),
            'is_cancellable' => $this->isCancellable(),
            'created_at'   => $this->createdAt,
            'updated_at'   => $this->updatedAt,
        ];
    }
}
