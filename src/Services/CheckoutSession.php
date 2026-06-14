<?php
declare(strict_types=1);

namespace VantageMarket\Services;

use VantageMarket\Strategy\PaymentStrategy;

class CheckoutSession {
    private ?PaymentStrategy $currentStrategy = null;
    private float $cartTotal;

    public function __construct(float $total) {
        $this->cartTotal = $total;
    }

    public function setPaymentStrategy(PaymentStrategy $strategy): void {
        $this->currentStrategy = $strategy;
    }

    public function executeCheckout(): bool {
        if ($this->currentStrategy === null) {
            echo "<div style='color:red; font-weight:bold; margin-bottom: 15px;'>[Checkout] Error: No payment method selected.</div>";
            return false;
        }

        echo "<div style='margin-bottom: 15px;'>[Checkout] Processing via " . htmlspecialchars($this->currentStrategy->getMethodName()) . "...</div>";
        
        $result = $this->currentStrategy->pay($this->cartTotal);
        
        if ($result) {
            echo "<div style='color:green; font-weight:bold; margin-top: 15px;'>[Checkout] Order confirmed. Receipt emailed.</div>";
            return true;
        }
        
        return false;
    }
}
