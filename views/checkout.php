<?php
declare(strict_types=1);

global $cartItems, $userType, $userName, $checkoutLog, $success;
$cartTotal = array_reduce($cartItems ?? [], fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0.0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout - VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    .checkout-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 40px; }
    .checkout-panel { background: var(--light); padding: 25px; box-shadow: 0 0 15px rgba(0,0,0,0.03); margin-bottom: 30px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-dark); }
    .form-control { width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 2px; }
    .payment-methods label { display: flex; align-items: center; gap: 10px; padding: 15px; border: 1px solid var(--border-color); margin-bottom: 10px; cursor: pointer; transition: 0.2s; }
    .payment-methods label:hover { background: #f8f9fa; }
    .payment-details { padding: 15px; background: #f8f9fa; border: 1px solid var(--border-color); border-top: none; margin-top: -10px; margin-bottom: 15px; display: none; }
    .btn-checkout { width: 100%; background: var(--primary); color: var(--dark); padding: 15px; border: none; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-checkout:hover { background: var(--primary-dark); }
    @media (max-width: 991px) { .checkout-layout { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <!-- Simplified Header for checkout -->
  <div class="mid-header" style="border-bottom: 1px solid var(--border-color);">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo">VANTAGE<span class="logo-highlight">MARKET</span></a>
      <div><a href="/" class="text-muted"><i class="fa fa-angle-left"></i> Back to Shop</a></div>
    </div>
  </div>

  <div class="container">
    <?php if (isset($checkoutLog)): ?>
        <div class="checkout-panel" style="margin-top: 40px;">
            <h3 class="section-title">Order Status</h3>
            <?= $checkoutLog ?>
            <?php if ($success): ?>
                <a href="/" class="btn-checkout" style="display:inline-block; text-align:center; width:auto; padding: 10px 30px;">Continue Shopping</a>
            <?php else: ?>
                <a href="/checkout" class="btn-checkout" style="display:inline-block; text-align:center; width:auto; padding: 10px 30px; background:#dc3545; color:white;">Try Again</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <form method="POST" action="/checkout/process" class="checkout-layout">
          <!-- Left: Details -->
          <div>
            <div class="checkout-panel">
                <h3 class="section-title">Billing Details</h3>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($userName) ?>" required>
                </div>
                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea class="form-control" rows="3" required></textarea>
                </div>
            </div>

            <div class="checkout-panel payment-methods">
                <h3 class="section-title">Payment Strategy Selection</h3>
                
                <label>
                    <input type="radio" name="payment_method" value="credit_card" onchange="toggleDetails('cc')" checked>
                    <i class="fas fa-credit-card text-primary"></i> Credit / Debit Card
                </label>
                <div id="details-cc" class="payment-details" style="display:block;">
                    <input type="text" name="card_number" class="form-control" placeholder="Card Number (e.g. 1234 5678 9101 1121)">
                </div>

                <label>
                    <input type="radio" name="payment_method" value="fpx" onchange="toggleDetails('fpx')">
                    <i class="fas fa-university text-primary"></i> FPX Online Banking
                </label>
                <div id="details-fpx" class="payment-details">
                    <select name="bank_code" class="form-control">
                        <option value="MBB0228">Maybank2U</option>
                        <option value="BCBB0235">CIMB Clicks</option>
                        <option value="RHB0218">RHB Now</option>
                    </select>
                </div>

                <label>
                    <input type="radio" name="payment_method" value="ewallet" onchange="toggleDetails('ewallet')">
                    <i class="fas fa-wallet text-primary"></i> E-Wallet
                </label>
                <div id="details-ewallet" class="payment-details">
                    <input type="text" name="wallet_id" class="form-control" placeholder="Wallet ID / Phone Number">
                </div>

                <label>
                    <input type="radio" name="payment_method" value="cod" onchange="toggleDetails('cod')">
                    <i class="fas fa-truck text-primary"></i> Cash on Delivery
                </label>
                <div id="details-cod" class="payment-details">
                    <p class="text-muted" style="margin:0;">Pay with cash upon delivery.</p>
                </div>
            </div>
          </div>

          <!-- Right: Summary -->
          <div>
            <div class="checkout-panel">
                <h3 class="section-title">Order Summary</h3>
                <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-between" style="margin-bottom: 10px;">
                            <span class="text-dark"><?= htmlspecialchars($item['title']) ?> <small class="text-muted">x<?= $item['quantity'] ?></small></span>
                            <span class="text-dark">RM <?= number_format((float)$item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-between font-weight-bold" style="font-size: 18px; margin-bottom: 25px;">
                    <span class="text-dark">Total</span>
                    <span class="text-primary" style="color:var(--primary-dark);">RM <?= number_format($cartTotal, 2) ?></span>
                </div>
                <button type="submit" class="btn-checkout">Place Order</button>
            </div>
          </div>
        </form>
    <?php endif; ?>
  </div>

  <script>
    function toggleDetails(activeId) {
        document.querySelectorAll('.payment-details').forEach(el => el.style.display = 'none');
        document.getElementById('details-' + activeId).style.display = 'block';
    }
  </script>
</body>
</html>
