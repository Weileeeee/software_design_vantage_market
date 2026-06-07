<?php
// =============================================================
// VantageMarket — Dashboard View
// Protected route: AuthMiddleware::requireAuth() called first
// =============================================================
declare(strict_types=1);

$userId    = $_SESSION['user_id']    ?? 'Unknown';
$userName  = $_SESSION['user_name']  ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$initials  = implode('', array_map(fn($p) => strtoupper($p[0] ?? ''), explode(' ', trim($userName))));
$initials  = substr($initials, 0, 2) ?: 'U';

// Fetch real order stats for this user
$totalOrders = 0;
$totalSpent  = 0.0;
$cartCount   = 0;

try {
    $db = \VantageMarket\Config\Database::getInstance();

    $stmt = $db->prepare('SELECT COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total FROM Orders WHERE user_id = :uid');
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch();
    $totalOrders = (int)   ($row['cnt']   ?? 0);
    $totalSpent  = (float) ($row['total'] ?? 0.0);

    $cartStmt = $db->prepare('SELECT COUNT(*) as cnt FROM Shopping_Carts sc JOIN Cart_Items ci ON ci.cart_id = sc.cart_id WHERE sc.user_id = :uid');
    $cartStmt->execute([':uid' => $userId]);
    $cartCount = (int) ($cartStmt->fetch()['cnt'] ?? 0);
} catch (\Throwable) {
    // silently fallback to zeros
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — VantageMarket</title>
  <meta name="description" content="Your VantageMarket account dashboard." />
  <link rel="stylesheet" href="/css/auth.css" />
</head>
<body>
<div class="dashboard-page">

  <!-- Navigation -->
  <nav class="dash-nav">
    <div class="dash-brand">
      <div class="dash-brand-icon">🛒</div>
      <span class="dash-brand-name">VantageMarket</span>
    </div>
    <div class="dash-nav-actions">
      <a href="/orders" style="color:#fff; text-decoration:none; font-size:14px; font-weight:600; margin-right:16px; opacity:0.85;">
        📦 My Orders
      </a>
      <a href="/catalog" style="color:#fff; text-decoration:none; font-size:14px; font-weight:600; margin-right:16px; opacity:0.85;">
        🛍️ Shop
      </a>
      <div class="dash-user-chip">
        <div class="dash-avatar"><?= htmlspecialchars($initials) ?></div>
        <span><?= htmlspecialchars($userName) ?></span>
      </div>
      <form id="logout-form" method="POST" action="/logout">
        <button type="submit" class="btn-logout" id="logout-btn">Sign Out</button>
      </form>
    </div>
  </nav>

  <!-- Main content -->
  <main class="dash-main">

    <div class="dash-welcome">
      <h1>Good to see you, <span><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span> 👋</h1>
      <p>Here's a summary of your VantageMarket account activity.</p>
    </div>

    <!-- Stat cards -->
    <div class="dash-cards">
      <div class="dash-card" style="cursor:pointer;" onclick="window.location.href='/orders'">
        <div class="card-icon icon-blue">🛍️</div>
        <h3>Total Orders</h3>
        <div class="metric"><?= $totalOrders ?></div>
        <div class="metric-sub"><?= $totalOrders === 0 ? 'No orders placed yet' : 'Click to view all orders' ?></div>
      </div>
      <div class="dash-card">
        <div class="card-icon icon-green">💰</div>
        <h3>Total Spent</h3>
        <div class="metric">RM <?= number_format($totalSpent, 2) ?></div>
        <div class="metric-sub">Lifetime purchases</div>
      </div>
      <div class="dash-card" style="cursor:pointer;" onclick="window.location.href='/#cart'">
        <div class="card-icon icon-purple">🛒</div>
        <h3>Cart Items</h3>
        <div class="metric"><?= $cartCount ?></div>
        <div class="metric-sub"><?= $cartCount === 0 ? 'Your cart is empty' : 'Items in your cart' ?></div>
      </div>
      <div class="dash-card">
        <div class="card-icon icon-orange">⭐</div>
        <h3>Reviews</h3>
        <div class="metric">0</div>
        <div class="metric-sub">Products reviewed</div>
      </div>
    </div>

    <!-- Quick links -->
    <?php if ($totalOrders > 0): ?>
    <div class="session-info" style="margin-bottom: 24px;">
      <h2>Recent Orders</h2>
      <?php
        $recentStmt = $db->prepare('SELECT * FROM Orders WHERE user_id = :uid ORDER BY created_at DESC LIMIT 3');
        $recentStmt->execute([':uid' => $userId]);
        $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
        $statusColors = ['pending'=>'#856404','processing'=>'#084298','shipped'=>'#0c5460','delivered'=>'#155724'];
        $statusBg     = ['pending'=>'#fff3cd','processing'=>'#cfe2ff','shipped'=>'#d1ecf1','delivered'=>'#d4edda'];
      ?>
      <?php foreach ($recentOrders as $ro): ?>
        <div class="info-row" style="cursor:pointer;" onclick="window.location.href='/orders/<?= $ro['order_id'] ?>'">
          <span class="label">Order #<?= $ro['order_id'] ?> &nbsp;
            <span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:11px;font-weight:700;
              background:<?= $statusBg[$ro['status']] ?? '#eee' ?>;color:<?= $statusColors[$ro['status']] ?? '#333' ?>;">
              <?= ucfirst($ro['status']) ?>
            </span>
          </span>
          <span class="value">RM <?= number_format($ro['total_amount'], 2) ?> &nbsp;
            <span style="font-size:12px;color:#999;"><?= date('d M Y', strtotime($ro['created_at'])) ?></span>
          </span>
        </div>
      <?php endforeach; ?>
      <div style="margin-top:12px;">
        <a href="/orders" style="font-size:13px; color:var(--accent); font-weight:600; text-decoration:none;">View all orders →</a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Session info -->
    <div class="session-info">
      <h2>Session Information</h2>
      <div class="info-row">
        <span class="label">User ID</span>
        <span class="value">#<?= htmlspecialchars((string)$userId) ?></span>
      </div>
      <div class="info-row">
        <span class="label">Full Name</span>
        <span class="value"><?= htmlspecialchars($userName) ?></span>
      </div>
      <div class="info-row">
        <span class="label">Email</span>
        <span class="value"><?= htmlspecialchars($userEmail) ?></span>
      </div>
      <div class="info-row">
        <span class="label">Session Status</span>
        <span class="value"><span class="badge-active">● Active</span></span>
      </div>
      <div class="info-row">
        <span class="label">Last Active</span>
        <span class="value" id="last-active-ts">Just now</span>
      </div>
    </div>

  </main>
</div>

<script>
(function(){
  const ts = new Date();
  document.getElementById('last-active-ts').textContent =
    ts.toLocaleDateString('en-MY', {weekday:'long',hour:'2-digit',minute:'2-digit'});

  const logoutForm = document.getElementById('logout-form');
  const logoutBtn  = document.getElementById('logout-btn');
  logoutForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    logoutBtn.disabled = true;
    logoutBtn.textContent = 'Signing out…';
    try {
      await fetch('/logout', { method: 'POST', headers: { 'Accept': 'application/json' } });
    } finally {
      window.location.href = '/login';
    }
  });
})();
</script>
</body>
</html>
