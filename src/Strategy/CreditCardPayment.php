<?php
declare(strict_types=1);

namespace VantageMarket\Strategy;

class CreditCardPayment implements PaymentStrategy {
    public function __construct(private string $cardNumber) {}

    public function pay(float $amount): bool {
        $last4 = strlen($this->cardNumber) >= 4 ? substr($this->cardNumber, -4) : '****';
        echo "[Credit Card] Charging RM" . number_format($amount, 2) . " to card ending {$last4}.<br>";
        return true;
    }

    public function getMethodName(): string {
        return 'Credit/Debit Card';
    }
}
