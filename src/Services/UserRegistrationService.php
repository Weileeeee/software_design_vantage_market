<?php
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
        try {
            $this->mailer->sendWelcomeEmail($user);
        } catch (\Throwable $e) {
            error_log('[UserRegistrationService] Welcome email failed: ' . $e->getMessage());
        }

        return $user;
    }
}
