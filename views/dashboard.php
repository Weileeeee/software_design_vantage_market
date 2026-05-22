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
      <div class="dash-card">
        <div class="card-icon icon-blue">🛍️</div>
        <h3>Total Orders</h3>
        <div class="metric">0</div>
        <div class="metric-sub">No orders placed yet</div>
      </div>
      <div class="dash-card">
        <div class="card-icon icon-green">💰</div>
        <h3>Total Spent</h3>
        <div class="metric">RM 0.00</div>
        <div class="metric-sub">Lifetime purchases</div>
      </div>
      <div class="dash-card">
        <div class="card-icon icon-purple">🛒</div>
        <h3>Cart Items</h3>
        <div class="metric">0</div>
        <div class="metric-sub">Items in your cart</div>
      </div>
      <div class="dash-card">
        <div class="card-icon icon-orange">⭐</div>
        <h3>Reviews</h3>
        <div class="metric">0</div>
        <div class="metric-sub">Products reviewed</div>
      </div>
    </div>

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
  // Show a human-readable last-active timestamp
  const ts = new Date();
  document.getElementById('last-active-ts').textContent =
    ts.toLocaleDateString('en-MY', {weekday:'long',hour:'2-digit',minute:'2-digit'});

  // Intercept logout to call POST /logout via fetch, then redirect
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
