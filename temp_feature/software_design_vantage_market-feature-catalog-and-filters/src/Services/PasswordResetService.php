<?php
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
