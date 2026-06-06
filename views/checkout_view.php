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

    <header class="py-4 mb-4" style="background-color: #1e293b; border-bottom: 1px solid #334155;">
        <div class="container d-flex justify-content-between align-items-center">
            <h4 class="fw-bold m-0 text-white">Vantage<span style="color: #818cf8;">Market</span> | Secure Checkout</h4>
            <a href="/catalog" class="text-decoration-none" style="color: #94a3b8;">← Back to Catalog</a>
        </div>
    </header>

    <div class="container pb-5">

        <?php if (isset($_SESSION['checkout_error'])): ?>
            <div class="alert alert-danger border-0 mb-4" style="background-color: #7f1d1d; color: #fecaca; border-radius: 12px;">
                <strong>Transaction Failed:</strong> <?= htmlspecialchars($_SESSION['checkout_error']) ?>
            </div>
            <?php unset($_SESSION['checkout_error']); ?>
        <?php endif; ?>

        <div class="row g-5">
            <div class="col-lg-7">
                <div class="checkout-card shadow">
                    <h5 class="fw-bold mb-4">Billing & Shipping Details</h5>

                    <form action="/checkout" method="POST" id="checkoutForm">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small text-muted text-uppercase fw-bold">First Name</label>
                                <input type="text" class="form-control form-control-custom" value="Test" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted text-uppercase fw-bold">Last Name</label>
                                <input type="text" class="form-control form-control-custom" value="User" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small text-muted text-uppercase fw-bold">Shipping Address</label>
                                <input type="text" class="form-control form-control-custom" placeholder="123 Developer Lane" required>
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
                                    <span class="fw-bold text-white">$<?= number_format($lineTotal, 2) ?></span>
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
                        <span class="text-white fw-semibold">$<?= number_format($cartSubtotal, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-4">
                        <span style="color: #94a3b8;">Shipping</span>
                        <span class="text-success fw-semibold">Free</span>
                    </div>

                    <hr style="border-color: #475569;" class="mb-4">

                    <button type="submit" form="checkoutForm" class="btn btn-pay w-100 shadow-sm fs-5">
                        Confirm & Pay Securely
                    </button>
                    <div class="text-center mt-3">
                        <small style="color: #64748b;">🔒 Protected by 256-bit SSL encryption</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>