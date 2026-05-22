<?php
// =============================================================
// VantageMarket — SecurityLogger
// SRP: responsible for writing to Security_Log table only
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use PDO;
use VantageMarket\Config\Database;

final class SecurityLogger
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function logFailedLogin(?int $userId, string $ip): void
    {
        $this->write($userId, 'FAILED_LOGIN', "Failed login attempt from {$ip}", $ip);
    }

    public function logAccountLocked(?int $userId, string $ip): void
    {
        $this->write($userId, 'ACCOUNT_LOCKED', "Account locked after max failed attempts", $ip);
    }

    public function logSuccessfulLogin(int $userId, string $ip): void
    {
        $this->write($userId, 'SUCCESSFUL_LOGIN', "Login from {$ip}", $ip);
    }

    public function logSessionMismatch(?int $userId, string $ip): void
    {
        $this->write($userId, 'SESSION_MISMATCH', "Session mismatch detected from {$ip}", $ip);
    }

    public function logRestrictedSearch(?int $userId, string $query, string $ip): void
    {
        $detail = "Restricted keyword search: " . substr($query, 0, 200);
        $this->write($userId, 'RESTRICTED_SEARCH', $detail, $ip);
    }

    private function write(?int $userId, string $eventType, string $detail, string $ip): void
    {
        try {
            $this->db->prepare(
                'INSERT INTO Security_Log (user_id, event_type, detail, ip_address)
                 VALUES (:uid, :type, :detail, :ip)'
            )->execute([
                ':uid'    => $userId,
                ':type'   => $eventType,
                ':detail' => $detail,
                ':ip'     => $ip,
            ]);
        } catch (\Throwable $e) {
            // Logging must never crash the app
            error_log("[SecurityLogger] Failed to write log: " . $e->getMessage());
        }
    }
}
