<?php
// =============================================================
// VantageMarket — UserRepository
// SRP: responsible for database persistence only
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use PDO;
use VantageMarket\Config\Database;
use VantageMarket\Models\User;

final class UserRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    // Reads
    // ----------------------------------------------------------

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Users WHERE email_address = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findById(int $userId): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Users WHERE user_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $userId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM Users WHERE email_address = :email'
        );
        $stmt->execute([':email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ----------------------------------------------------------
    // Writes
    // ----------------------------------------------------------

    /** Persists a new user and returns the new user_id */
    public function create(
        string $email,
        string $passwordHash,
        string $firstName,
        string $lastName,
        bool   $isGuest = false
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO Users
                (email_address, password_hash, first_name, last_name, is_guest)
             VALUES
                (:email, :hash, :first, :last, :guest)'
        );
        $stmt->execute([
            ':email'  => $email,
            ':hash'   => $passwordHash,
            ':first'  => $firstName,
            ':last'   => $lastName,
            ':guest'  => (int) $isGuest,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /** Increment failed login counter; lock account if threshold reached */
    public function incrementFailedAttempts(int $userId, int $maxAttempts, int $lockMinutes): void
    {
        $this->db->prepare(
            'UPDATE Users
             SET failed_attempts = failed_attempts + 1,
                 is_locked       = IF(failed_attempts + 1 >= :max, TRUE, FALSE),
                 locked_until    = IF(failed_attempts + 1 >= :max,
                                      DATE_ADD(NOW(), INTERVAL :mins MINUTE),
                                      locked_until)
             WHERE user_id = :id'
        )->execute([
            ':max'  => $maxAttempts,
            ':mins' => $lockMinutes,
            ':id'   => $userId,
        ]);
    }

    /** Reset failed attempts and unlock after a successful login */
    public function resetFailedAttempts(int $userId): void
    {
        $this->db->prepare(
            'UPDATE Users
             SET failed_attempts = 0,
                 is_locked       = FALSE,
                 locked_until    = NULL
             WHERE user_id = :id'
        )->execute([':id' => $userId]);
    }

    /** Update password hash (used by reset flow) */
    public function updatePassword(int $userId, string $newHash): void
    {
        $this->db->prepare(
            'UPDATE Users SET password_hash = :hash WHERE user_id = :id'
        )->execute([':hash' => $newHash, ':id' => $userId]);
    }

    // ----------------------------------------------------------
    // Remember-me tokens
    // ----------------------------------------------------------

    /** Persist a hashed remember-me token against the user */
    public function storeRememberToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        // Upsert: one token per user (replace on duplicate)
        $this->db->prepare(
            'INSERT INTO Remember_Tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, :exp)
             ON DUPLICATE KEY UPDATE token_hash = :hash, expires_at = :exp'
        )->execute([
            ':uid'  => $userId,
            ':hash' => $tokenHash,
            ':exp'  => $expiresAt,
        ]);
    }

    public function findByRememberToken(string $tokenHash): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT u.* FROM Users u
             JOIN Remember_Tokens rt ON rt.user_id = u.user_id
             WHERE rt.token_hash = :hash AND rt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function deleteRememberToken(int $userId): void
    {
        $this->db->prepare(
            'DELETE FROM Remember_Tokens WHERE user_id = :uid'
        )->execute([':uid' => $userId]);
    }

    // ----------------------------------------------------------
    // Password reset tokens
    // ----------------------------------------------------------

    /** Store hashed reset token with expiry */
    public function storeResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $this->db->prepare(
            'INSERT INTO Password_Reset_Tokens (user_id, token_hash, expires_at)
             VALUES (:uid, :hash, :exp)
             ON DUPLICATE KEY UPDATE token_hash = :hash, expires_at = :exp'
        )->execute([
            ':uid'  => $userId,
            ':hash' => $tokenHash,
            ':exp'  => $expiresAt,
        ]);
    }

    public function findUserByResetToken(string $tokenHash): ?User
    {
        $stmt = $this->db->prepare(
            'SELECT u.* FROM Users u
             JOIN Password_Reset_Tokens prt ON prt.user_id = u.user_id
             WHERE prt.token_hash = :hash AND prt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function deleteResetToken(int $userId): void
    {
        $this->db->prepare(
            'DELETE FROM Password_Reset_Tokens WHERE user_id = :uid'
        )->execute([':uid' => $userId]);
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function hydrate(array $row): User
    {
        return new User(
            userId:         (int) $row['user_id'],
            email:          $row['email_address'],
            passwordHash:   $row['password_hash'],
            firstName:      $row['first_name'],
            lastName:       $row['last_name'],
            isGuest:        (bool) $row['is_guest'],
            isLocked:       (bool) $row['is_locked'],
            failedAttempts: (int) $row['failed_attempts'],
            lockedUntil:    $row['locked_until'] ?? null,
            createdAt:      $row['created_at'] ?? '',
        );
    }
}
