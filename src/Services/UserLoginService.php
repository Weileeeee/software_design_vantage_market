<?php
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
