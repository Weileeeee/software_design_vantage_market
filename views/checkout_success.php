<?php
declare(strict_types=1);
$orderId        = $orderId        ?? 0;
$userName       = $userName       ?? 'Customer';
$successMessage = $successMessage ?? "Your order has been placed successfully!";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed | VantageMarket</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', sans-serif; background: #f5f5f5; color: #333; }
    .topbar { background: #2d3436; padding: 6px 0; font-size: 12px; }
    .topbar .inner { max-width: 1200px; margin: auto; padding: 0 20px; display: flex; justify-content: space-between; color: #aaa; }
    .topbar a { color: #aaa; text-decoration: none; margin-right: 12px; }
    .mid-header { background: #ff6b6b; padding: 14px 0; }
    .mid-header .inner { max-width: 1200px; margin: auto; padding: 0 20px; display: flex; align-items: center; justify-content: space-between; }
    .logo { text-decoration: none; font-size: 22px; font-weight: 800; color: white; letter-spacing: 1px; }
    .logo span { background: #f9ca24; color: #2d3436; padding: 2px 8px; border-radius: 2px; }
    .navbar { background: #2d3436; }
    .navbar .inner { max-width: 1200px; margin: auto; padding: 0 20px; display: flex; align-items: center; }
    .navbar a { color: white; text-decoration: none; padding: 14px 18px; display: inline-block; font-size: 14px; }
    .navbar a:hover { background: rgba(255,255,255,0.1); }
    .page { max-width: 600px; margin: 60px auto; padding: 0 20px; }
    .card { background: white; border-radius: 8px; padding: 48px 40px; text-align: center; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
    .check-circle { width: 80px; height: 80px; background: #e8f5e9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 36px; color: #43a047; }
    h1 { font-size: 26px; font-weight: 700; margin-bottom: 10px; color: #2d3436; }
    .subtitle { color: #666; margin-bottom: 28px; font-size: 15px; line-height: 1.5; }
    .order-badge { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 14px 20px; margin-bottom: 32px; font-size: 14px; color: #555; }
    .order-badge strong { font-size: 20px; color: #2d3436; display: block; margin-top: 4px; }
    .btn-group { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .btn { padding: 12px 28px; border-radius: 4px; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; transition: 0.2s; }
    .btn-primary { background: #ff6b6b; color: white; }
    .btn-primary:hover { background: #ff5252; }
    .btn-secondary { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
    .btn-secondary:hover { background: #e0e0e0; }
    .steps { display: flex; justify-content: center; gap: 0; margin-bottom: 36px; }
    .step { flex: 1; max-width: 140px; text-align: center; position: relative; }
    .step:not(:last-child)::after { content: ''; position: absolute; top: 16px; right: -50%; width: 100%; height: 2px; background: #43a047; z-index: 0; }
    .step-dot { width: 32px; height: 32px; border-radius: 50%; background: #43a047; color: white; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; font-size: 14px; position: relative; z-index: 1; }
    .step-label { font-size: 11px; color: #666; }
  </style>
</head>
<body>
  <div class="topbar"><div class="inner">
    <div><a href="#">About</a><a href="#">Contact</a><a href="#">Help</a></div>
    <div>Account: <?= htmlspecialchars($userName) ?></div>
  </div></div>
  <div class="mid-header"><div class="inner">
    <a href="/" class="logo">VANTAGE <span>MARKET</span></a>
    <div style="color:white;text-align:right;font-size:13px;"><div>Customer Service</div><div style="font-size:18px;font-weight:700;">+012 345 6789</div></div>
  </div></div>
  <div class="navbar"><div class="inner">
    <a href="/">Home</a><a href="/catalog">Shop</a><a href="/cart">Cart</a><a href="/logout">Sign Out</a>
  </div></div>
  <div class="page"><div class="card">
    <div class="check-circle">✓</div>
    <h1>Order Confirmed!</h1>
    <p class="subtitle">Thank you, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>!<br>Your order has been placed and is being processed.</p>
    <?php if ($orderId): ?>
    <div class="order-badge">Order ID<strong>#<?= htmlspecialchars((string)$orderId) ?></strong></div>
    <?php endif; ?>
    <div class="steps">
      <div class="step"><div class="step-dot"><i class="fas fa-check"></i></div><div class="step-label">Order Placed</div></div>
      <div class="step"><div class="step-dot" style="background:#ff6b6b;">⚙</div><div class="step-label">Processing</div></div>
      <div class="step"><div class="step-dot" style="background:#ccc;">🚚</div><div class="step-label">Shipped</div></div>
      <div class="step"><div class="step-dot" style="background:#ccc;">📦</div><div class="step-label">Delivered</div></div>
    </div>
    <div class="btn-group">
      <a href="/orders" class="btn btn-primary"><i class="fas fa-box"></i> Track My Order</a>
        <a href="/catalog" class="btn btn-secondary"><i class="fas fa-shopping-bag"></i> Continue Shopping</a>
      <a href="/" class="btn btn-secondary"><i class="fas fa-home"></i> Back to Home</a>
    </div>
  </div></div>
</body>
</html>
