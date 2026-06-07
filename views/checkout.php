<?php
declare(strict_types=1);

/** @var array  $cartItems */
/** @var string $userType */
/** @var string $userName */
/** @var string $checkoutLog */
/** @var bool   $success */
// Use session-selected items if set (from /checkout/selected), else use passed $cartItems
$cartItems = $_SESSION['vm_checkout_items'] ?? $cartItems ?? [];
$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0.0);
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
    .checkout-panel { background: white; padding: 25px; box-shadow: 0 0 15px rgba(0,0,0,0.03); margin-bottom: 30px; border-radius: 4px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
    .payment-methods label { display: flex; align-items: center; gap: 10px; padding: 15px; border: 1px solid #ddd; margin-bottom: 10px; cursor: pointer; transition: 0.2s; border-radius: 4px; }
    .payment-methods label:hover { background: #f8f9fa; }
    .payment-details { padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-top: none; margin-top: -10px; margin-bottom: 15px; display: none; border-radius: 0 0 4px 4px; }
    .btn-checkout { width: 100%; background: #ff6b6b; color: white; padding: 15px; border: none; font-size: 16px; font-weight: 700; cursor: pointer; transition: 0.2s; border-radius: 4px; }
    .btn-checkout:hover { background: #ff5252; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e0e0e0; }
    .summary-row:last-of-type { border-bottom: none; }
    .summary-label { color: #666; }
    .summary-value { font-weight: 600; color: #333; }
    .total-row { font-size: 16px; }
    .total-row .summary-value { color: #ff6b6b; font-size: 18px; }
    .section-title { font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #333; }
    @media (max-width: 991px) { .checkout-layout { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <!-- Topbar -->
  <div class="topbar">
    <div class="container d-flex align-center justify-between">
      <div class="topbar-links">
        <a href="#">About</a><a href="#">Contact</a><a href="#">Help</a>
      </div>
      <div class="topbar-actions d-flex align-center">
        <?php if (($userType ?? 'Guest') === 'Guest'): ?>
          <span class="text-muted">Browsing as: Guest</span>
        <?php else: ?>
          <span class="text-muted">Account: <?= htmlspecialchars($userName ?? '') ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- Mid Header -->
  <div class="mid-header">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo">VANTAGE<span class="logo-highlight">MARKET</span></a>
      <div class="customer-service">
        <span class="cs-title">Customer Service</span>
        <span class="cs-phone">+012 345 6789</span>
      </div>
    </div>
  </div>
  <!-- Navbar -->
  <div class="navbar">
    <div class="container d-flex align-center justify-between">
      <div class="nav-links">
        <a href="/">Home</a>
        <a href="/catalog">Shop</a>
        <a href="/cart"><i class="fas fa-angle-left"></i> Back to Cart</a>
        <?php if (($userType ?? 'Guest') === 'Guest'): ?>
          <a href="/signin">Sign In</a>
        <?php else: ?>
          <a href="/logout">Sign Out</a>
        <?php endif; ?>
      </div>
      <div class="nav-icons">
        <a href="/cart" class="nav-icon" title="Cart" style="color: var(--primary);">
          <i class="fas fa-shopping-cart"></i>
          <span class="nav-icon-badge"><?= count($cartItems) ?></span>
        </a>
      </div>
    </div>
  </div>

  <div class="container">
    <?php if (isset($checkoutLog)): ?>
        <div class="checkout-panel" style="margin-top: 40px; text-align: center; max-width: 560px; margin-left: auto; margin-right: auto;">
          <?php if ($success): ?>
            <div style="font-size: 60px; margin-bottom: 16px;">✅</div>
            <h2 style="color: #2e7d32; margin-bottom: 8px;">Order Placed Successfully!</h2>
            <p style="color: #666; margin-bottom: 24px;">Thank you for shopping with VantageMarket. Your order is being processed.</p>
            <div style="background:#f5f5f5; padding:16px; border-radius:4px; text-align:left; margin-bottom:24px; font-size:13px;">
              <strong>Payment Summary</strong><br><br>
              <?= $checkoutLog ?>
            </div>
            <div style="display:flex; gap:12px; justify-content:center;">
              <a href="/" class="btn-checkout" style="display:inline-block;width:auto;padding:12px 28px;text-decoration:none;">Continue Shopping</a>
              <a href="/catalog" class="btn-checkout" style="display:inline-block;width:auto;padding:12px 28px;text-decoration:none;background:#555;">Browse More</a>
            </div>
          <?php else: ?>
            <div style="font-size: 60px; margin-bottom: 16px;">❌</div>
            <h2 style="color: #c62828; margin-bottom: 8px;">Payment Failed</h2>
            <p style="color: #666; margin-bottom: 24px;">Something went wrong processing your payment. Please try again.</p>
            <div style="background:#fff5f5; border:1px solid #ffcdd2; padding:16px; border-radius:4px; text-align:left; margin-bottom:24px; font-size:13px; color:#c62828;">
              <?= $checkoutLog ?>
            </div>
            <a href="/checkout" class="btn-checkout" style="display:inline-block;width:auto;padding:12px 28px;text-decoration:none;">Try Again</a>
          <?php endif; ?>
        </div>
    <?php else: ?>
        <form method="POST" action="/checkout/process" class="checkout-layout">
          <!-- Left: Billing & Payment -->
          <div>
            <!-- Billing Details -->
            <div class="checkout-panel">
                <h3 class="section-title">Billing Details</h3>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="billing_name" class="form-control" value="<?= htmlspecialchars($userName) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="billing_email" class="form-control" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="billing_phone" class="form-control" placeholder="+60 1234 5678" required>
                </div>
                <div class="form-group">
                    <label>Shipping Address</label>
                    <textarea name="billing_address" class="form-control" rows="3" placeholder="Enter your complete address" required></textarea>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="billing_city" class="form-control" placeholder="e.g. Kuala Lumpur" required>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="billing_state" class="form-control" placeholder="e.g. Selangor" required>
                </div>
                <div class="form-group">
                    <label>Postal Code</label>
                    <input type="text" name="billing_postcode" class="form-control" placeholder="e.g. 50000" required>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="checkout-panel payment-methods">
                <h3 class="section-title">Payment Method</h3>
                
                <label>
                    <input type="radio" name="payment_method" value="credit_card" onchange="toggleDetails('cc')" checked>
                    <i class="fas fa-credit-card" style="color: #ff6b6b;"></i> Credit / Debit Card
                </label>
                <div id="details-cc" class="payment-details" style="display:block;">
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" class="form-control" name="card_number" placeholder="1234 5678 9101 1121" required>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" class="form-control" placeholder="MM/YY" required>
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="text" class="form-control" placeholder="123" required>
                        </div>
                    </div>
                </div>

                <label>
                    <input type="radio" name="payment_method" value="fpx" onchange="toggleDetails('fpx')">
                    <i class="fas fa-university" style="color: #ff6b6b;"></i> FPX Online Banking
                </label>
                <div id="details-fpx" class="payment-details">
                    <label style="display: block; font-weight: 500; margin-bottom: 10px;">Select Your Bank</label>
                    <select name="bank_code" class="form-control">
                        <option value="MBB0228">Maybank2U</option>
                        <option value="BCBB0235">CIMB Clicks</option>
                        <option value="RHB0218">RHB Now</option>
                        <option value="HNB0235">Hong Leong Bank</option>
                        <option value="PBB0233">Public Bank</option>
                    </select>
                </div>

                <label>
                    <input type="radio" name="payment_method" value="ewallet" onchange="toggleDetails('ewallet')">
                    <i class="fas fa-wallet" style="color: #ff6b6b;"></i> E-Wallet
                </label>
                <div id="details-ewallet" class="payment-details">
                    <label style="display: block; font-weight: 500; margin-bottom: 10px;">Select Your Wallet</label>
                    <select class="form-control" style="margin-bottom: 10px;">
                        <option value="touch">Touch 'n Go</option>
                        <option value="gcash">GCash</option>
                        <option value="grabpay">GrabPay</option>
                    </select>
                    <input type="text" class="form-control" name="wallet_id" placeholder="Your Wallet ID / Phone Number">
                </div>

                <label>
                    <input type="radio" name="payment_method" value="cod" onchange="toggleDetails('cod')">
                    <i class="fas fa-truck" style="color: #ff6b6b;"></i> Cash on Delivery (COD)
                </label>
                <div id="details-cod" class="payment-details">
                    <p style="color: #666; font-size: 13px; margin: 0;"><i class="fas fa-info-circle"></i> Pay with cash when the delivery arrives. Additional delivery fee may apply.</p>
                </div>
            </div>
          </div>

          <!-- Right: Order Summary -->
          <div>
            <div class="checkout-panel" style="position: sticky; top: 20px;">
                <h3 class="section-title">Order Summary</h3>
                
                <div style="background: #f5f5f5; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <p style="color: #666; font-size: 13px; margin: 0 0 10px 0;"><strong>Items in Cart:</strong></p>
                    <?php foreach ($cartItems as $item): ?>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px;">
                            <span style="color: #333;"><?= htmlspecialchars($item['title']) ?></span>
                            <span style="color: #666;">x<?= $item['quantity'] ?></span>
                            <span style="font-weight: 600; color: #333;">RM <?= number_format((float)$item['price'] * $item['quantity'], 2) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">RM <?= number_format($cartTotal, 2) ?></span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Shipping Fee</span>
                    <span class="summary-value">RM 5.00</span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Discount</span>
                    <span class="summary-value">-RM 0.00</span>
                </div>

                <div class="summary-row total-row">
                    <span class="summary-label">Total</span>
                    <span class="summary-value">RM <?= number_format($cartTotal + 5, 2) ?></span>
                </div>

                <button type="submit" class="btn-checkout">Place Order</button>
                <a href="/cart" style="display: block; text-align: center; margin-top: 10px; color: #ff6b6b; text-decoration: none; font-size: 13px;">Back to Cart</a>
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
