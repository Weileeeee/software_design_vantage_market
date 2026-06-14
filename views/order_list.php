<?php
// =============================================================
// VantageMarket — Order List View
// =============================================================
declare(strict_types=1);

$userId   = $_SESSION['user_id']   ?? null;
$userName = $_SESSION['user_name'] ?? 'Guest';
$userType = $userId ? 'User' : 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Orders — VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    .orders-table-wrap {
      background: var(--light);
      border: 1px solid var(--border-color);
      border-radius: 4px;
      overflow: hidden;
      margin-top: 30px;
    }
    .orders-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 14px;
    }
    .orders-table th {
      background: var(--dark);
      color: #fff;
      padding: 14px 18px;
      text-align: left;
      font-weight: 600;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .orders-table td {
      padding: 14px 18px;
      border-bottom: 1px solid var(--border-color);
      color: var(--text-dark);
      vertical-align: middle;
    }
    .orders-table tr:last-child td { border-bottom: none; }
    .orders-table tr:hover td { background: #fafafa; }

    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .status-pending    { background: #fff3cd; color: #856404; }
    .status-processing { background: #cfe2ff; color: #084298; }
    .status-shipped    { background: #d1ecf1; color: #0c5460; }
    .status-delivered  { background: #d4edda; color: #155724; }

    .btn-track {
      display: inline-block;
      padding: 6px 16px;
      background: var(--primary);
      color: var(--dark);
      border-radius: 2px;
      font-weight: 700;
      font-size: 13px;
      text-decoration: none;
      transition: 0.2s;
    }
    .btn-track:hover { background: var(--primary-dark); }

    .empty-orders {
      text-align: center;
      padding: 80px 20px;
      color: var(--text-muted);
    }
    .empty-orders i {
      font-size: 56px;
      color: var(--border-color);
      margin-bottom: 20px;
      display: block;
    }
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 40px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--primary);
    }
  </style>
</head>
<body>

  <!-- Topbar -->
  <div class="topbar">
    <div class="container d-flex align-center justify-between">
      <div class="topbar-links">
        <a href="#">About</a>
        <a href="#">Contact</a>
        <a href="#">Help</a>
        <a href="#">FAQs</a>
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
        <a href="/orders" style="color:var(--primary)">My Orders</a>
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
    <div class="page-header">
      <h2 class="section-title" style="margin:0; border:none;">My Orders</h2>
      <a href="/catalog" class="btn-track"><i class="fas fa-shopping-bag" style="margin-right:6px;"></i>Continue Shopping</a>
    </div>

    <?php if (empty($orderData)): ?>
      <div class="orders-table-wrap">
        <div class="empty-orders">
          <i class="fas fa-box-open"></i>
          <h4 style="color:var(--text-dark); margin-bottom: 10px;">No orders yet</h4>
          <p style="margin-bottom: 25px;">Looks like you haven't placed any orders. Start shopping!</p>
          <a href="/catalog" class="btn-track">Browse Products</a>
        </div>
      </div>
    <?php else: ?>
      <div class="orders-table-wrap">
        <table class="orders-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Date</th>
              <th>Status</th>
              <th>Total</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orderData as $order): ?>
              <tr>
                <td><strong>#<?= $order['order_id'] ?></strong></td>
                <td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                <td>
                  <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                    <?= ucfirst($order['status']) ?>
                  </span>
                </td>
                <td><strong>RM <?= number_format($order['total_amount'], 2) ?></strong></td>
                <td>
                  <a href="/orders/<?= $order['order_id'] ?>" class="btn-track">
                    <i class="fas fa-map-marker-alt" style="margin-right:5px;"></i>Track
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
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
