<?php
declare(strict_types=1);

namespace VantageMarket\Strategy;

class CashOnDeliveryPayment implements PaymentStrategy {
    public function pay(float $amount): bool {
        echo "[COD] Customer will pay RM" . number_format($amount, 2) . " upon delivery.<br>";
        return true;
    }

    public function getMethodName(): string {
        return 'Cash on Delivery';
    }
}
