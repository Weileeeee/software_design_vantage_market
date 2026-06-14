<?php
declare(strict_types=1);

namespace VantageMarket\Strategy;

class FPXBankingPayment implements PaymentStrategy {
    public function __construct(private string $bankCode) {}

    public function pay(float $amount): bool {
        echo "[FPX] Routing RM" . number_format($amount, 2) . " via FPX to bank {$this->bankCode}.<br>";
        return true;
    }

    public function getMethodName(): string {
        return 'FPX Online Banking';
    }
}
