<?php
// =============================================================
// VantageMarket — Order Tracker View
// =============================================================
declare(strict_types=1);

$userId   = $_SESSION['user_id']   ?? null;
$userName = $_SESSION['user_name'] ?? 'Guest';
$userType = $userId ? 'User' : 'Guest';

/** @var \VantageMarket\Models\Order $order */
/** @var array $items */

$statusLabels = [
    'pending'    => ['label' => 'Order Placed',  'icon' => 'fa-check-circle'],
    'processing' => ['label' => 'Processing',     'icon' => 'fa-cog'],
    'shipped'    => ['label' => 'Shipped',         'icon' => 'fa-shipping-fast'],
    'delivered'  => ['label' => 'Delivered',       'icon' => 'fa-box-open'],
];
$steps    = array_keys($statusLabels);
$progress = $order->progressStep(); // 1–4
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Order #<?= $order->orderId ?> — VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    .tracker-card {
      background: var(--light);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      padding: 35px;
      margin-top: 30px;
    }
    .tracker-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 35px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .tracker-header h2 { margin: 0; font-size: 22px; color: var(--dark); }
    .tracker-header small { color: var(--text-muted); font-size: 13px; }

    /* Progress stepper */
    .stepper {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      position: relative;
      margin-bottom: 40px;
    }
    .stepper::before {
      content: '';
      position: absolute;
      top: 22px;
      left: 10%;
      right: 10%;
      height: 4px;
      background: var(--border-color);
      z-index: 0;
    }
    .stepper-progress {
      position: absolute;
      top: 22px;
      left: 10%;
      height: 4px;
      background: var(--primary);
      z-index: 1;
      transition: width 0.6s ease;
      width: <?= match($progress) { 1 => '0%', 2 => '33%', 3 => '66%', 4 => '90%' } ?>;
    }
    .step {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
      z-index: 2;
      text-align: center;
    }
    .step-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: var(--border-color);
      color: #999;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 10px;
      border: 3px solid #fff;
      box-shadow: 0 0 0 2px var(--border-color);
      transition: 0.3s;
    }
    .step.done .step-icon {
      background: var(--primary);
      color: var(--dark);
      box-shadow: 0 0 0 2px var(--primary);
    }
    .step.active .step-icon {
      background: var(--dark);
      color: var(--primary);
      box-shadow: 0 0 0 2px var(--dark);
    }
    .step-label {
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
    }
    .step.done .step-label,
    .step.active .step-label { color: var(--dark); }

    /* Items table */
    .items-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
      margin-top: 10px;
    }
    .items-table th {
      background: #f8f9fa;
      padding: 12px 16px;
      text-align: left;
      font-weight: 700;
      color: var(--text-dark);
      border-bottom: 2px solid var(--border-color);
      font-size: 13px;
    }
    .items-table td {
      padding: 12px 16px;
      border-bottom: 1px solid var(--border-color);
      color: var(--text-dark);
    }
    .items-table tr:last-child td { border-bottom: none; }

    .order-total-row {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 20px;
      margin-top: 20px;
      padding-top: 15px;
      border-top: 2px solid var(--border-color);
      font-size: 16px;
      font-weight: 700;
      color: var(--dark);
    }

    .status-badge {
      display: inline-block;
      padding: 4px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
    }
    .status-pending    { background: #fff3cd; color: #856404; }
    .status-processing { background: #cfe2ff; color: #084298; }
    .status-shipped    { background: #d1ecf1; color: #0c5460; }
    .status-delivered  { background: #d4edda; color: #155724; }

    .btn-back {
      display: inline-block;
      padding: 8px 20px;
      background: var(--dark);
      color: #fff;
      border-radius: 2px;
      font-weight: 600;
      font-size: 14px;
      text-decoration: none;
      transition: 0.2s;
    }
    .btn-back:hover { background: #333; }

    .section-divider {
      font-size: 15px;
      font-weight: 700;
      color: var(--dark);
      margin: 30px 0 15px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--border-color);
    }
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
        <?php if ($userType === 'Guest'): ?>
          <span class="text-muted">Browsing as: Guest</span>
          <span class="badge">Guest Mode</span>
        <?php else: ?>
          <span class="text-muted">Account: <?= htmlspecialchars($userName) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Middle Header -->
  <div class="mid-header">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo">VANTAGE<span class="logo-highlight">MARKET</span></a>
      <form action="/catalog" method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search for products...">
        <button type="submit"><i class="fa fa-search"></i></button>
      </form>
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
        <a href="/orders">My Orders</a>
        <?php if ($userType === 'Guest'): ?>
          <a href="/login">Sign In</a>
          <a href="/register">Register</a>
        <?php else: ?>
          <a href="/dashboard">Dashboard</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="container">

    <div style="margin-top: 35px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
      <div>
        <a href="/orders" class="btn-back"><i class="fas fa-arrow-left" style="margin-right:6px;"></i>Back to Orders</a>
      </div>
      <div>
        <span style="font-size:13px; color:var(--text-muted);">Placed on <?= date('d M Y, h:i A', strtotime($order->createdAt)) ?></span>
      </div>
    </div>

    <div class="tracker-card">

      <!-- Header -->
      <div class="tracker-header">
        <div>
          <h2>Order #<?= $order->orderId ?></h2>
          <small><?= count($items) ?> item(s) &nbsp;·&nbsp;
            <span class="status-badge status-<?= $order->status ?>"><?= ucfirst($order->status) ?></span>
          </small>
        </div>
        <div style="font-size: 20px; font-weight: 800; color: var(--dark);">
          RM <?= number_format($order->totalAmount, 2) ?>
        </div>
      </div>

      <!-- Stepper -->
      <div class="stepper">
        <div class="stepper-progress"></div>
        <?php foreach ($steps as $i => $key):
          $stepNum  = $i + 1;
          $info     = $statusLabels[$key];
          $isDone   = $stepNum < $progress;
          $isActive = $stepNum === $progress;
          $class    = $isDone ? 'done' : ($isActive ? 'active' : '');
        ?>
          <div class="step <?= $class ?>">
            <div class="step-icon">
              <?php if ($isDone): ?>
                <i class="fas fa-check"></i>
              <?php else: ?>
                <i class="fas <?= $info['icon'] ?>"></i>
              <?php endif; ?>
            </div>
            <div class="step-label"><?= $info['label'] ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Order Items -->
      <div class="section-divider"><i class="fas fa-list" style="margin-right:8px; color:var(--primary);"></i>Order Items</div>
      <table class="items-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr>
              <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
              <td style="color:var(--text-muted);"><?= htmlspecialchars($item['sku']) ?></td>
              <td><?= $item['quantity'] ?></td>
              <td>RM <?= number_format((float)$item['price_at_purchase'], 2) ?></td>
              <td><strong>RM <?= number_format((float)$item['price_at_purchase'] * (int)$item['quantity'], 2) ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div class="order-total-row">
        <span>Order Total:</span>
        <span>RM <?= number_format($order->totalAmount, 2) ?></span>
      </div>

    </div>
  </div>

  <!-- Footer -->
  <div class="footer" style="margin-top: 60px;">
    <div class="container">
      <div class="footer-bottom">
        <p>&copy; VantageMarket. All Rights Reserved. Designed for CSE6234.</p>
      </div>
    </div>
  </div>

</body>
</html>
