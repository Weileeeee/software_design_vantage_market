<?php
// =============================================================
// VantageMarket - UC04 (Login) + UC05 (Register) Auth Module
// Merged: Exceptions, User, Validator, Repository,
//         SessionManager, SecurityLogger, UserMailer,
//         RegistrationService, LoginService,
//         PasswordResetService, AuthMiddleware, AuthController
// =============================================================

// =============================================================
// VantageMarket — Auth Exception Classes
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Exceptions;

use RuntimeException;

/** Thrown when login credentials are invalid */
class InvalidCredentialsException extends RuntimeException {}

/** Thrown when account is temporarily locked (too many failed attempts) */
class AccountLockedException extends RuntimeException {}

/** Thrown when registration input fails validation */
class ValidationException extends RuntimeException
{
    private array $errors;

    public function __construct(array $errors)
    {
        parent::__construct('Validation failed.');
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

/** Thrown when the email is already registered */
class DuplicateEmailException extends RuntimeException {}

/** Thrown when a password reset token is invalid or expired */
class InvalidTokenException extends RuntimeException {}

/** Thrown when session auth fails (mismatch, expired, etc.) */
class AuthenticationException extends RuntimeException {}

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

// =============================================================
// VantageMarket — UserValidator
// SRP: responsible for input validation only
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Exceptions\ValidationException;

final class UserValidator
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = require __DIR__ . '/../../config/auth.php';
    }

    // ----------------------------------------------------------
    // Registration validation (UC05)
    // ----------------------------------------------------------

    /**
     * @throws ValidationException
     */
    public function validateRegistration(array $data): void
    {
        $errors = [];

        // First name
        if (empty(trim($data['first_name'] ?? ''))) {
            $errors['first_name'] = 'First name is required.';
        }

        // Last name
        if (empty(trim($data['last_name'] ?? ''))) {
            $errors['last_name'] = 'Last name is required.';
        }

        // Email
        $emailError = $this->validateEmail($data['email'] ?? '');
        if ($emailError) {
            $errors['email'] = $emailError;
        }

        // Password
        $passwordErrors = $this->validatePassword($data['password'] ?? '');
        if (!empty($passwordErrors)) {
            $errors['password'] = $passwordErrors;
        }

        // Confirm password
        if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        // Terms of service (UC05 business rule)
        if (empty($data['terms_accepted'])) {
            $errors['terms'] = 'You must accept the Terms of Service and Privacy Policy.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    // ----------------------------------------------------------
    // Login validation (UC04) — minimal, intentionally generic
    // ----------------------------------------------------------

    /**
     * @throws ValidationException
     */
    public function validateLogin(array $data): void
    {
        $errors = [];

        if (empty(trim($data['email'] ?? ''))) {
            $errors['email'] = 'Email address is required.';
        }

        if (empty($data['password'] ?? '')) {
            $errors['password'] = 'Password is required.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    // ----------------------------------------------------------
    // Password reset validation
    // ----------------------------------------------------------

    /**
     * @throws ValidationException
     */
    public function validatePasswordReset(array $data): void
    {
        $errors = [];

        $passwordErrors = $this->validatePassword($data['password'] ?? '');
        if (!empty($passwordErrors)) {
            $errors['password'] = $passwordErrors;
        }

        if (($data['password'] ?? '') !== ($data['confirm_password'] ?? '')) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    // ----------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------

    private function validateEmail(string $email): ?string
    {
        if (empty(trim($email))) {
            return 'Email address is required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }
        if (strlen($email) > 255) {
            return 'Email address is too long.';
        }
        return null;
    }

    /** Returns array of error strings (empty = valid) */
    private function validatePassword(string $password): array
    {
        $errors = [];
        $min    = $this->cfg['password_min_length'];

        if (strlen($password) < $min) {
            $errors[] = "Password must be at least {$min} characters.";
        }
        if ($this->cfg['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if ($this->cfg['password_require_number'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        if ($this->cfg['password_require_special'] && !preg_match('/[\W_]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        return $errors;
    }
}

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

// =============================================================
// VantageMarket — SessionManager
// Handles secure PHP session creation, validation, destruction
// UC04: session expiry, session token, remember-me cookie
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Models\User;

final class SessionManager
{
    private int    $lifetimeMinutes;
    private string $cookieName;
    private bool   $cookieSecure;
    private bool   $cookieHttpOnly;
    private string $cookieSameSite;
    private int    $rememberDays;

    public function __construct()
    {
        $cfg = require __DIR__ . '/../../config/auth.php';

        $this->lifetimeMinutes = $cfg['session_lifetime_minutes'];
        $this->cookieName      = $cfg['cookie_name'];
        $this->cookieSecure    = $cfg['cookie_secure'];
        $this->cookieHttpOnly  = $cfg['cookie_httponly'];
        $this->cookieSameSite  = $cfg['cookie_samesite'];
        $this->rememberDays    = $cfg['remember_me_days'];
    }

    // ----------------------------------------------------------
    // Session lifecycle
    // ----------------------------------------------------------

    /** Start or resume a secure session */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'lifetime' => 0,                          // browser session cookie
            'path'     => '/',
            'secure'   => $this->cookieSecure,
            'httponly' => $this->cookieHttpOnly,
            'samesite' => $this->cookieSameSite,
        ]);

        session_start();
    }

    /** Write user identity into session after successful login */
    public function login(User $user): void
    {
        // Regenerate ID to prevent session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user->userId;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_name']  = $user->fullName();
        $_SESSION['logged_in']  = true;
        $_SESSION['last_active'] = time();
    }

    /** Destroy session and clear cookie */
    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /** Returns true if a valid, non-expired session exists */
    public function isAuthenticated(): bool
    {
        if (empty($_SESSION['logged_in']) || empty($_SESSION['last_active'])) {
            return false;
        }

        $idleSeconds = time() - (int) $_SESSION['last_active'];
        if ($idleSeconds > ($this->lifetimeMinutes * 60)) {
            $this->logout();
            return false;
        }

        // Touch the activity timestamp
        $_SESSION['last_active'] = time();
        return true;
    }

    public function currentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public function currentUserEmail(): ?string
    {
        return $_SESSION['user_email'] ?? null;
    }

    // ----------------------------------------------------------
    // Remember-me cookie (UC04)
    // ----------------------------------------------------------

    /** Set a persistent remember-me cookie with a raw token */
    public function setRememberCookie(string $rawToken): void
    {
        setcookie(
            $this->cookieName,
            $rawToken,
            [
                'expires'  => time() + ($this->rememberDays * 24 * 3600),
                'path'     => '/',
                'secure'   => $this->cookieSecure,
                'httponly' => $this->cookieHttpOnly,
                'samesite' => $this->cookieSameSite,
            ]
        );
    }

    public function getRememberCookieValue(): ?string
    {
        return $_COOKIE[$this->cookieName] ?? null;
    }

    public function clearRememberCookie(): void
    {
        setcookie($this->cookieName, '', time() - 3600, '/', '', $this->cookieSecure, $this->cookieHttpOnly);
    }
}

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

// =============================================================
// VantageMarket — UserMailer
// SRP: responsible for transactional email sending only
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Models\User;

final class UserMailer
{
    private string $fromAddress;
    private string $fromName;
    private string $appUrl;

    public function __construct()
    {
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'no-reply@vantagemarket.com';
        $this->fromName    = $_ENV['MAIL_FROM_NAME']    ?? 'VantageMarket';
        $this->appUrl      = $_ENV['APP_URL']           ?? 'https://vantagemarket.com';
    }

    // ----------------------------------------------------------
    // UC05 — Welcome email after registration
    // ----------------------------------------------------------

    public function sendWelcomeEmail(User $user): void
    {
        $subject = 'Welcome to VantageMarket!';
        $body    = $this->wrapHtml(
            $user->fullName(),
            "Thank you for registering with VantageMarket. Your account is ready.",
            "Start Shopping",
            "{$this->appUrl}/catalogue"
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // UC04 — Password reset email
    // ----------------------------------------------------------

    public function sendPasswordResetEmail(User $user, string $rawToken): void
    {
        $resetUrl = "{$this->appUrl}/reset-password?token={$rawToken}";
        $subject  = 'Reset Your VantageMarket Password';
        $body     = $this->wrapHtml(
            $user->fullName(),
            "We received a request to reset your password. This link expires in 60 minutes.",
            "Reset Password",
            $resetUrl
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // UC04 — Security alert for login from new device/location
    // ----------------------------------------------------------

    public function sendSecurityAlert(User $user, string $ipAddress): void
    {
        $subject = 'New Login Detected — VantageMarket';
        $message = "A login to your account was detected from IP: {$ipAddress}. "
                 . "If this was not you, please reset your password immediately.";
        $body    = $this->wrapHtml(
            $user->fullName(),
            $message,
            "Reset Password",
            "{$this->appUrl}/forgot-password"
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // UC04 — Account locked notification
    // ----------------------------------------------------------

    public function sendAccountLockedEmail(User $user): void
    {
        $subject = 'Your VantageMarket Account Has Been Locked';
        $body    = $this->wrapHtml(
            $user->fullName(),
            "Your account has been temporarily locked due to too many failed login attempts. "
            . "It will unlock automatically in 15 minutes, or you can reset your password now.",
            "Reset Password",
            "{$this->appUrl}/forgot-password"
        );

        $this->send($user->email, $subject, $body);
    }

    // ----------------------------------------------------------
    // Internal helpers
    // ----------------------------------------------------------

    private function send(string $to, string $subject, string $htmlBody): void
    {
        // In production: swap mail() for PHPMailer / Symfony Mailer / SES SDK.
        // Using mail() here for zero-dependency illustration.
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromAddress}>\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        $sent = mail($to, $subject, $htmlBody, $headers);

        if (!$sent) {
            error_log("[Mailer] Failed to send '{$subject}' to {$to}");
        }
    }

    /** Minimal branded HTML email wrapper */
    private function wrapHtml(
        string $recipientName,
        string $message,
        string $ctaLabel,
        string $ctaUrl
    ): string {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
        <body style="margin:0;padding:0;background:#f5f5f5;font-family:Georgia,serif;">
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr><td align="center" style="padding:40px 20px;">
              <table width="600" cellpadding="0" cellspacing="0"
                     style="background:#ffffff;border-radius:8px;overflow:hidden;
                            box-shadow:0 2px 8px rgba(0,0,0,.08);">
                <tr>
                  <td style="background:#0a0a0a;padding:28px 40px;">
                    <span style="font-family:Georgia,serif;font-size:22px;
                                 color:#e8c97e;letter-spacing:2px;">VANTAGEMARKET</span>
                  </td>
                </tr>
                <tr>
                  <td style="padding:40px;">
                    <p style="margin:0 0 16px;font-size:16px;color:#333;">
                      Hello, <strong>{$recipientName}</strong>,
                    </p>
                    <p style="margin:0 0 32px;font-size:15px;color:#555;line-height:1.6;">
                      {$message}
                    </p>
                    <a href="{$ctaUrl}"
                       style="display:inline-block;padding:14px 32px;
                              background:#0a0a0a;color:#e8c97e;
                              font-family:Georgia,serif;font-size:14px;
                              letter-spacing:1px;text-decoration:none;
                              border-radius:4px;">
                      {$ctaLabel}
                    </a>
                    <p style="margin:40px 0 0;font-size:12px;color:#aaa;">
                      If you did not request this, please ignore this email.
                      This message was sent by VantageMarket.
                    </p>
                  </td>
                </tr>
              </table>
            </td></tr>
          </table>
        </body>
        </html>
        HTML;
    }
}

// =============================================================
// VantageMarket — UserRegistrationService
// SRP: orchestrates registration workflow only
// UC05: Register as a registered user
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Exceptions\DuplicateEmailException;
use VantageMarket\Exceptions\ValidationException;
use VantageMarket\Models\User;

final class UserRegistrationService
{
    public function __construct(
        private UserValidator  $validator,
        private UserRepository $repository,
        private UserMailer     $mailer,
    ) {}

    /**
     * Register a new customer account.
     *
     * @throws ValidationException      – invalid input
     * @throws DuplicateEmailException  – email already in use
     * @throws \RuntimeException        – DB or mail failure
     *
     * @return User  The newly created user
     */
    public function register(array $data): User
    {
        // 1. Validate input (throws ValidationException on failure)
        $this->validator->validateRegistration($data);

        $email = strtolower(trim($data['email']));

        // 2. Check email uniqueness (UC05: unique email business rule)
        if ($this->repository->emailExists($email)) {
            throw new DuplicateEmailException(
                'An account with this email already exists. Please log in or reset your password.'
            );
        }

        // 3. Hash password — NEVER store plain text (UC05 business rule)
        $cfg          = require __DIR__ . '/../../config/auth.php';
        $passwordHash = password_hash(
            $data['password'],
            PASSWORD_BCRYPT,
            ['cost' => $cfg['bcrypt_cost']]
        );

        // 4. Persist new user record
        $userId = $this->repository->create(
            email:        $email,
            passwordHash: $passwordHash,
            firstName:    trim($data['first_name']),
            lastName:     trim($data['last_name']),
        );

        $user = $this->repository->findById($userId);

        if ($user === null) {
            throw new \RuntimeException(
                'We are experiencing technical difficulties. Please try again later.'
            );
        }

        // 5. Send welcome email (UC05 step 7)
        $this->mailer->sendWelcomeEmail($user);

        return $user;
    }
}

// =============================================================
// VantageMarket — UserLoginService
// SRP: orchestrates login workflow only
// UC04: Login with throttling, lockout, remember-me
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Exceptions\AccountLockedException;
use VantageMarket\Exceptions\InvalidCredentialsException;
use VantageMarket\Exceptions\ValidationException;
use VantageMarket\Models\User;

final class UserLoginService
{
    private int $maxAttempts;
    private int $lockMinutes;

    public function __construct(
        private UserValidator    $validator,
        private UserRepository   $repository,
        private SessionManager   $session,
        private SecurityLogger   $logger,
        private UserMailer       $mailer,
    ) {
        $cfg               = require __DIR__ . '/../../config/auth.php';
        $this->maxAttempts = $cfg['max_failed_attempts'];
        $this->lockMinutes = $cfg['lockout_minutes'];
    }

    /**
     * Authenticate a user and establish a session.
     *
     * @throws ValidationException         – blank fields
     * @throws AccountLockedException      – too many failures
     * @throws InvalidCredentialsException – wrong email/password
     *
     * @return User  The authenticated user
     */
    public function login(array $data, string $ip, bool $rememberMe = false): User
    {
        // 1. Basic field presence check
        $this->validator->validateLogin($data);

        $email = strtolower(trim($data['email']));

        // 2. Look up the user — use generic error if not found
        //    (UC04 business rule: never confirm if email exists)
        $user = $this->repository->findByEmail($email);

        if ($user === null) {
            $this->logger->logFailedLogin(null, $ip);
            throw new InvalidCredentialsException(
                'Invalid email or password. Please try again.'
            );
        }

        // 3. Check lockout status (UC04 business rule)
        if ($user->isLocked) {
            if ($user->lockedUntil !== null && strtotime($user->lockedUntil) > time()) {
                $this->logger->logAccountLocked($user->userId, $ip);
                throw new AccountLockedException(
                    "Your account has been temporarily locked. "
                    . "Please reset your password or try again in {$this->lockMinutes} minutes."
                );
            }
            // Lock period has expired — reset automatically
            $this->repository->resetFailedAttempts($user->userId);
            // Reload fresh user state
            $user = $this->repository->findById($user->userId);
        }

        // 4. Verify password hash
        if (!password_verify($data['password'], $user->passwordHash)) {
            $this->repository->incrementFailedAttempts(
                $user->userId,
                $this->maxAttempts,
                $this->lockMinutes
            );
            $this->logger->logFailedLogin($user->userId, $ip);

            // Reload to check if just-locked
            $fresh = $this->repository->findById($user->userId);
            if ($fresh?->isLocked) {
                $this->mailer->sendAccountLockedEmail($fresh);
                throw new AccountLockedException(
                    "Your account has been temporarily locked. "
                    . "Please reset your password or try again in {$this->lockMinutes} minutes."
                );
            }

            throw new InvalidCredentialsException(
                'Invalid email or password. Please try again.'
            );
        }

        // 5. Successful auth — reset failure counter
        $this->repository->resetFailedAttempts($user->userId);
        $this->logger->logSuccessfulLogin($user->userId, $ip);

        // 6. Establish session (UC04 step 6)
        $this->session->start();
        $this->session->login($user);

        // 7. Handle remember-me (UC04 business rule)
        if ($rememberMe) {
            $rawToken  = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date(
                'Y-m-d H:i:s',
                time() + ($GLOBALS['auth_cfg']['remember_me_days'] ?? 30) * 86400
            );
            $this->repository->storeRememberToken($user->userId, $tokenHash, $expiresAt);
            $this->session->setRememberCookie($rawToken);
        }

        return $user;
    }

    /**
     * Log out the current user and clear remember-me data.
     */
    public function logout(int $userId): void
    {
        $this->repository->deleteRememberToken($userId);
        $this->session->clearRememberCookie();
        $this->session->logout();
    }

    /**
     * Attempt login via a remember-me cookie (persistent session).
     * Returns the User on success, null if token is missing/expired.
     */
    public function loginViaRememberToken(): ?User
    {
        $rawToken = $this->session->getRememberCookieValue();
        if ($rawToken === null) {
            return null;
        }

        $tokenHash = hash('sha256', $rawToken);
        $user      = $this->repository->findByRememberToken($tokenHash);

        if ($user === null) {
            $this->session->clearRememberCookie();
            return null;
        }

        $this->session->start();
        $this->session->login($user);

        return $user;
    }
}

// =============================================================
// VantageMarket — PasswordResetService
// UC04: Forgot Password / Reset Password flow
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Exceptions\InvalidTokenException;
use VantageMarket\Exceptions\ValidationException;

final class PasswordResetService
{
    private int $expiryMinutes;
    private int $bcryptCost;

    public function __construct(
        private UserValidator  $validator,
        private UserRepository $repository,
        private UserMailer     $mailer,
    ) {
        $cfg                 = require __DIR__ . '/../../config/auth.php';
        $this->expiryMinutes = $cfg['reset_token_expiry_minutes'];
        $this->bcryptCost    = $cfg['bcrypt_cost'];
    }

    /**
     * Initiate a password reset — generates a token and emails the user.
     * Always returns true to prevent email enumeration.
     */
    public function requestReset(string $email): void
    {
        $email = strtolower(trim($email));
        $user  = $this->repository->findByEmail($email);

        // UC04: never reveal whether email exists
        if ($user === null) {
            return;
        }

        // Generate a cryptographically secure token
        $rawToken  = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + ($this->expiryMinutes * 60));

        $this->repository->storeResetToken($user->userId, $tokenHash, $expiresAt);
        $this->mailer->sendPasswordResetEmail($user, $rawToken);
    }

    /**
     * Complete a password reset using the token from the email link.
     *
     * @throws InvalidTokenException  – token missing, expired, or not found
     * @throws ValidationException    – new password fails complexity rules
     */
    public function resetPassword(string $rawToken, array $data): void
    {
        if (empty($rawToken)) {
            throw new InvalidTokenException('Invalid or expired password reset link.');
        }

        $tokenHash = hash('sha256', $rawToken);
        $user      = $this->repository->findUserByResetToken($tokenHash);

        if ($user === null) {
            throw new InvalidTokenException(
                'This password reset link is invalid or has expired. Please request a new one.'
            );
        }

        // Validate the new password
        $this->validator->validatePasswordReset($data);

        // Hash and store
        $newHash = password_hash(
            $data['password'],
            PASSWORD_BCRYPT,
            ['cost' => $this->bcryptCost]
        );

        $this->repository->updatePassword($user->userId, $newHash);

        // Invalidate token (single-use)
        $this->repository->deleteResetToken($user->userId);

        // Also clear any remember-me tokens for security
        $this->repository->deleteRememberToken($user->userId);
    }
}

// =============================================================
// VantageMarket — AuthMiddleware
// Protects routes that require an active session
// UC04: redirects unauthenticated requests to /login
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Middleware;

use VantageMarket\Services\SessionManager;
use VantageMarket\Services\UserLoginService;

final class AuthMiddleware
{
    public function __construct(
        private SessionManager  $session,
        private UserLoginService $loginService,
    ) {}

    /**
     * Call at the top of any protected page/route.
     * Tries session → then remember-me cookie → then redirects to login.
     *
     * @param string $redirectTo  URL to send to after login (stored in session)
     */
    public function requireAuth(string $redirectTo = '/dashboard'): void
    {
        $this->session->start();

        // 1. Valid active session
        if ($this->session->isAuthenticated()) {
            return;
        }

        // 2. Try remember-me cookie (UC04 persistent login)
        $user = $this->loginService->loginViaRememberToken();
        if ($user !== null) {
            return;
        }

        // 3. No valid auth — save intended destination and redirect
        $_SESSION['intended_url'] = $redirectTo;
        header('Location: /login');
        exit;
    }

    /**
     * Redirect already-authenticated users away from guest-only pages
     * (e.g. landing on /login while already logged in).
     */
    public function redirectIfAuthenticated(string $destination = '/dashboard'): void
    {
        $this->session->start();

        if ($this->session->isAuthenticated()) {
            header("Location: {$destination}");
            exit;
        }
    }
}

// =============================================================
// VantageMarket — AuthController
// HTTP layer: receives request, calls services, returns response
// Keeps all HTTP concerns out of the service classes (SRP)
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Controllers;

use VantageMarket\Exceptions\AccountLockedException;
use VantageMarket\Exceptions\DuplicateEmailException;
use VantageMarket\Exceptions\InvalidCredentialsException;
use VantageMarket\Exceptions\InvalidTokenException;
use VantageMarket\Exceptions\ValidationException;
use VantageMarket\Services\PasswordResetService;
use VantageMarket\Services\SessionManager;
use VantageMarket\Services\UserLoginService;
use VantageMarket\Services\UserRegistrationService;

final class AuthController
{
    public function __construct(
        private UserRegistrationService $registrationService,
        private UserLoginService        $loginService,
        private PasswordResetService    $resetService,
        private SessionManager          $session,
    ) {}

    // ----------------------------------------------------------
    // POST /register  (UC05)
    // ----------------------------------------------------------

    public function register(): void
    {
        $this->requirePost();

        try {
            $user = $this->registrationService->register($_POST);

            $this->session->start();
            $this->session->login($user);

            $this->jsonSuccess([
                'message' => 'Registration successful! Welcome to VantageMarket.',
                'user'    => $user->toPublicArray(),
                'redirect'=> '/dashboard',
            ], 201);

        } catch (ValidationException $e) {
            $this->jsonError('Validation failed.', 422, $e->getErrors());

        } catch (DuplicateEmailException $e) {
            $this->jsonError($e->getMessage(), 409);

        } catch (\RuntimeException $e) {
            error_log('[AuthController::register] ' . $e->getMessage());
            $this->jsonError('We are experiencing technical difficulties. Please try again later.', 503);
        }
    }

    // ----------------------------------------------------------
    // POST /login  (UC04)
    // ----------------------------------------------------------

    public function login(): void
    {
        $this->requirePost();

        $ip         = $this->clientIp();
        $rememberMe = !empty($_POST['remember_me']);

        try {
            $user = $this->loginService->login($_POST, $ip, $rememberMe);

            $this->jsonSuccess([
                'message'  => 'Login successful.',
                'user'     => $user->toPublicArray(),
                'redirect' => $_SESSION['intended_url'] ?? '/dashboard',
            ]);

        } catch (ValidationException $e) {
            $this->jsonError('Validation failed.', 422, $e->getErrors());

        } catch (AccountLockedException $e) {
            $this->jsonError($e->getMessage(), 423);

        } catch (InvalidCredentialsException $e) {
            // UC04 business rule: generic message, no timing oracle
            $this->jsonError('Invalid email or password. Please try again.', 401);

        } catch (\RuntimeException $e) {
            error_log('[AuthController::login] ' . $e->getMessage());
            $this->jsonError('Service temporarily unavailable. Please try again shortly.', 503);
        }
    }

    // ----------------------------------------------------------
    // POST /logout  (UC04)
    // ----------------------------------------------------------

    public function logout(): void
    {
        $this->session->start();
        $userId = $this->session->currentUserId();

        if ($userId !== null) {
            $this->loginService->logout($userId);
        }

        $this->jsonSuccess(['message' => 'You have been logged out.', 'redirect' => '/']);
    }

    // ----------------------------------------------------------
    // POST /forgot-password  (UC04)
    // ----------------------------------------------------------

    public function forgotPassword(): void
    {
        $this->requirePost();

        $email = trim($_POST['email'] ?? '');

        // Always return 200 — never reveal if email exists
        $this->resetService->requestReset($email);

        $this->jsonSuccess([
            'message' => 'If that email is registered, you will receive a reset link shortly.',
        ]);
    }

    // ----------------------------------------------------------
    // POST /reset-password  (UC04)
    // ----------------------------------------------------------

    public function resetPassword(): void
    {
        $this->requirePost();

        $token = trim($_POST['token'] ?? $_GET['token'] ?? '');

        try {
            $this->resetService->resetPassword($token, $_POST);

            $this->jsonSuccess([
                'message'  => 'Your password has been reset. You can now log in.',
                'redirect' => '/login',
            ]);

        } catch (InvalidTokenException $e) {
            $this->jsonError($e->getMessage(), 400);

        } catch (ValidationException $e) {
            $this->jsonError('Validation failed.', 422, $e->getErrors());

        } catch (\RuntimeException $e) {
            error_log('[AuthController::resetPassword] ' . $e->getMessage());
            $this->jsonError('Service temporarily unavailable. Please try again shortly.', 503);
        }
    }

    // ----------------------------------------------------------
    // GET /me  — returns current auth state
    // ----------------------------------------------------------

    public function me(): void
    {
        $this->session->start();

        if (!$this->session->isAuthenticated()) {
            $this->jsonError('Unauthenticated.', 401);
            return;
        }

        $this->jsonSuccess([
            'user_id' => $this->session->currentUserId(),
            'email'   => $this->session->currentUserEmail(),
        ]);
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed.', 405);
            exit;
        }
    }

    private function clientIp(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    private function jsonSuccess(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, ...$data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $status = 400, array $errors = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $payload = ['success' => false, 'message' => $message];
        if (!empty($errors)) {
            $payload['errors'] = $errors;
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

