<?php
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
    public function requireAuth(string $redirectTo = '/'): void
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
    public function redirectIfAuthenticated(string $destination = '/'): void
    {
        $this->session->start();

        if ($this->session->isAuthenticated()) {
            header("Location: {$destination}");
            exit;
        }
    }
}
