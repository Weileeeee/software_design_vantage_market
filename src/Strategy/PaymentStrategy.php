<?php
declare(strict_types=1);

namespace VantageMarket\Strategy;

interface PaymentStrategy {
    public function pay(float $amount): bool;
    public function getMethodName(): string;
}
