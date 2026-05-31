<?php
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
