<?php
declare(strict_types=1);

namespace VantageMarket\Strategy;

class EWalletPayment implements PaymentStrategy {
    public function __construct(private string $walletId) {}

    public function pay(float $amount): bool {
        echo "[E-Wallet] Transferring RM" . number_format($amount, 2) . " from wallet {$this->walletId}.<br>";
        return true;
    }

    public function getMethodName(): string {
        return 'E-Wallet';
    }
}
