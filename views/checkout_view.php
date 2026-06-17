<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Vantage Market</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            /* Midnight slate */
            color: #f1f5f9;
        }

        .checkout-card {
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #334155;
            padding: 2rem;
        }

        .form-control-custom {
            background-color: #0f172a;
            border: 1px solid #475569;
            color: #f1f5f9;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }

        .form-control-custom:focus {
            background-color: #0f172a;
            color: #ffffff;
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
        }

        .item-row {
            border-bottom: 1px solid #334155;
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .item-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
            margin-bottom: 0;
        }

        .btn-pay {
            background: #6366f1;
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.8rem;
            transition: all 0.2s;
            border: none;
        }

        .btn-pay:hover {
            background: #4f46e5;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
        }

        .summary-box {
            background: #0f172a;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #334155;
        }
    </style>
</head>

<body>

    <!-- Topbar -->
    <div class="topbar" style="background:#2d3436;padding:6px 0;font-size:12px;">
      <div class="container d-flex justify-content-between align-items-center" style="max-width:1200px;margin:auto;padding:0 20px;">
        <div style="color:#aaa;">
          <a href="#" style="color:#aaa;text-decoration:none;margin-right:12px;">About</a>
          <a href="#" style="color:#aaa;text-decoration:none;margin-right:12px;">Contact</a>
          <a href="#" style="color:#aaa;text-decoration:none;">Help</a>
        </div>
        <div style="color:#aaa;">
          Account: <?= htmlspecialchars($userName ?? 'Guest') ?>
        </div>
      </div>
    </div>
    <!-- Mid Header -->
    <div style="background:#ff6b6b;padding:14px 0;">
      <div class="container" style="max-width:1200px;margin:auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between;">
        <a href="/" style="text-decoration:none;font-size:22px;font-weight:800;color:white;letter-spacing:1px;">
          VANTAGE <span style="background:#f9ca24;color:#2d3436;padding:2px 8px;border-radius:2px;">MARKET</span>
        </a>
        <div style="color:white;text-align:right;font-size:13px;">
          <div>Customer Service</div>
          <div style="font-size:18px;font-weight:700;">+012 345 6789</div>
        </div>
      </div>
    </div>
    <!-- Navbar -->
    <div style="background:#2d3436;padding:0;">
      <div class="container" style="max-width:1200px;margin:auto;padding:0 20px;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;gap:0;">
          <a href="/" style="color:white;text-decoration:none;padding:14px 18px;display:inline-block;font-size:14px;">Home</a>
          <a href="/catalog" style="color:white;text-decoration:none;padding:14px 18px;display:inline-block;font-size:14px;">Shop</a>
          <a href="/cart" style="color:#aaa;text-decoration:none;padding:14px 18px;display:inline-block;font-size:14px;">← Back to Cart</a>
          <a href="/logout" style="color:white;text-decoration:none;padding:14px 18px;display:inline-block;font-size:14px;">Sign Out</a>
        </div>
        <div style="display:flex;align-items:center;gap:6px;padding:10px 0;">
          <span style="color:white;font-size:13px;">🔒 Secure Checkout</span>
        </div>
      </div>
    </div>

    <div class="container pb-5">

        <?php if (!empty($checkoutError)): ?>
            <div class="alert alert-danger border-0 mb-4" style="background-color: #7f1d1d; color: #fecaca; border-radius: 12px;">
                <strong>⚠️ Transaction Failed:</strong> <?= htmlspecialchars($checkoutError) ?>
            </div>
        <?php endif; ?>

        <div class="row g-5">
            <div class="col-lg-7">
                <div class="checkout-card shadow">
                    <h5 class="fw-bold mb-4">Billing & Shipping Details</h5>

                    <form action="/checkout/process" method="POST" id="checkoutForm">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small text-muted text-uppercase fw-bold">First Name</label>
                                <input type="text" name="billing_first_name" class="form-control form-control-custom" value="<?= htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? 'Test')[0]) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted text-uppercase fw-bold">Last Name</label>
                                <input type="text" name="billing_last_name" class="form-control form-control-custom" value="<?= htmlspecialchars(explode(' ', $_SESSION['user_name'] ?? ' User')[1] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted text-uppercase fw-bold">Shipping Address</label>
                                <input type="text" name="billing_address" class="form-control form-control-custom" placeholder="123 Developer Lane" required>
                            </div>
                        </div>

                        <hr style="border-color: #475569;" class="my-4">

                        <h5 class="fw-bold mb-4">Apply Promotions (UC09)</h5>
                        <div class="mb-4">
                            <label class="form-label small text-muted text-uppercase fw-bold">Promo Code</label>
                            <input type="text" name="promo_code" class="form-control form-control-custom mb-2" placeholder="e.g. VANTAGE20">
                            <small style="color: #94a3b8;">Try using 'VANTAGE20' for 20% off your entire order.</small>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="checkout-card shadow position-sticky" style="top: 2rem;">
                    <h5 class="fw-bold mb-4">Order Summary</h5>

                    <div class="summary-box mb-4">
                        <?php
                        $cartSubtotal = 0;
                        if (!empty($cartItems)):
                            foreach ($cartItems as $item):
                                // Assuming your cart_items join brings in the product price
                                // Fallback to 0 if price isn't joined yet to prevent errors
                                $price = $item['price'] ?? 50.00;
                                $lineTotal = $price * $item['quantity'];
                                $cartSubtotal += $lineTotal;
                        ?>
                                <div class="item-row d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="m-0 fw-semibold text-white"><?= htmlspecialchars($item['title'] ?? 'Product #' . $item['product_id']) ?></h6>
                                        <small style="color: #94a3b8;">Qty: <?= htmlspecialchars($item['quantity']) ?></small>
                                    </div>
                                    <span class="fw-bold text-white">RM <?= number_format($lineTotal, 2) ?></span>
                                </div>
                            <?php
                            endforeach;
                        else:
                            ?>
                            <p class="text-muted small m-0">Your cart is unexpectedly empty.</p>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span style="color: #94a3b8;">Subtotal</span>
                        <span class="text-white fw-semibold">RM <?= number_format($cartSubtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="color: #94a3b8;">Shipping</span>
                        <span class="text-white fw-semibold">RM 5.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span style="color: #94a3b8;">Total</span>
                        <span style="color:#ff6b6b;font-weight:700;font-size:18px;">RM <?= number_format($cartSubtotal + 5, 2) ?></span>
                    </div>

                    <hr style="border-color: #475569;" class="mb-4">

                    <div class="mb-3">
                        <label class="form-label small text-muted text-uppercase fw-bold">Payment Method</label>
                        <select name="payment_method" form="checkoutForm" class="form-control form-control-custom" required>
                            <option value="" disabled selected>Select a payment method...</option>
                            <option value="credit_card">💳 Credit / Debit Card</option>
                            <option value="fpx">🏦 FPX Online Banking</option>
                            <option value="ewallet">📱 E-Wallet (Touch 'n Go / GrabPay)</option>
                            <option value="cod">🚚 Cash on Delivery</option>
                        </select>
                    </div>

                    <button type="submit" form="checkoutForm" class="btn btn-pay w-100 shadow-sm fs-5">
                        🔒 Place Order
                    </button>
                    <div class="text-center mt-3">
                        <small style="color: #64748b;">🔒 Protected by 256-bit SSL encryption</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            // Because the button is outside the form, HTML5 validation might fail silently on some browsers.
            // This manually checks if the form is valid.
            if (!this.checkValidity()) {
                e.preventDefault();
                alert('Please fill out all required fields, including Shipping Address and Payment Method.');
            }
        });

        // Also catch clicks on the external submit button to ensure validity is checked properly
        document.querySelector('button[form="checkoutForm"]').addEventListener('click', function(e) {
            const form = document.getElementById('checkoutForm');
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity(); // Tries to show native tooltip
                alert('Please make sure you have filled out the Shipping Address and selected a Payment Method.');
            }
        });
    </script>
</body>

</html>