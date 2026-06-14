<?php

namespace VantageMarket\Exceptions;

class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = "Validation failed", int $code = 0)
    {
        $this->errors = $errors;
        parent::__construct($message, $code);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}