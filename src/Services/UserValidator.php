<?php
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
