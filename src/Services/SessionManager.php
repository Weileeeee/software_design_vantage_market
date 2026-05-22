<?php
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
