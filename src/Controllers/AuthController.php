<?php
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
                'redirect'=> '/',
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

            // Check if this user is also an admin (matched by email)
            $db = \VantageMarket\Config\Database::getInstance();
            $adminStmt = $db->prepare(
                'SELECT admin_id, username, email FROM Admin WHERE email = :e AND is_active = 1 LIMIT 1'
            );
            $adminStmt->execute([':e' => $user->emailAddress]);
            $admin = $adminStmt->fetch(\PDO::FETCH_ASSOC);

            if ($admin) {
                // Grant admin session keys so AdminController::requireAdmin() passes
                $_SESSION['admin_id']       = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email']    = $admin['email'];
                // Log the admin login
                $db->prepare(
                    'UPDATE Admin SET last_login = NOW() WHERE admin_id = :id'
                )->execute([':id' => $admin['admin_id']]);
            }

            $redirect = $_SESSION['intended_url'] ?? '/';
            unset($_SESSION['intended_url']);

            $this->jsonSuccess([
                'message'  => 'Login successful.',
                'user'     => $user->toPublicArray(),
                'redirect' => $redirect,
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

        // If called via AJAX/fetch, return JSON; otherwise redirect directly
        $wantsJson = ($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json'
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        if ($wantsJson) {
            $this->jsonSuccess(['message' => 'You have been logged out.', 'redirect' => '/']);
        } else {
            header('Location: /');
            exit;
        }
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
        // Discard any buffered output (PHP warnings/notices) before sending JSON
        while (ob_get_level()) ob_end_clean();
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, ...$data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $status = 400, array $errors = []): void
    {
        while (ob_get_level()) ob_end_clean();
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
