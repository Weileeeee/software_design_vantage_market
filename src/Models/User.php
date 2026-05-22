<?php
// =============================================================
// VantageMarket — User Model (SRP: data container only)
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Models;

/**
 * Immutable value object representing a User record.
 * Holds data only — no business logic (SRP).
 */
final class User
{
    public function __construct(
        public readonly int     $userId,
        public readonly string  $email,
        public readonly string  $passwordHash,
        public readonly string  $firstName,
        public readonly string  $lastName,
        public readonly bool    $isGuest      = false,
        public readonly bool    $isLocked     = false,
        public readonly int     $failedAttempts = 0,
        public readonly ?string $lockedUntil  = null,
        public readonly string  $createdAt    = '',
    ) {}

    public function fullName(): string
    {
        return trim("{$this->firstName} {$this->lastName}");
    }

    /** Returns a safe array (never exposes passwordHash) */
    public function toPublicArray(): array
    {
        return [
            'user_id'    => $this->userId,
            'email'      => $this->email,
            'first_name' => $this->firstName,
            'last_name'  => $this->lastName,
            'full_name'  => $this->fullName(),
            'is_guest'   => $this->isGuest,
            'created_at' => $this->createdAt,
        ];
    }
}
