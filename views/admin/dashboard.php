<?php declare(strict_types=1); $pageTitle = 'Dashboard'; include __DIR__ . '/_layout.php'; ?>

<?php if ($success ?? null): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<!-- Stat Cards -->
<div class="stat-grid">
  <div class="stat-card"><div class="stat-icon" style="color:#ff6b6b;">📦</div><div class="stat-val"><?= number_format($stats['total_orders']) ?></div><div class="stat-label">Total Orders</div></div>
  <div class="stat-card"><div class="stat-icon" style="color:#f39c12;">⏳</div><div class="stat-val"><?= number_format($stats['pending_orders']) ?></div><div class="stat-label">Pending Orders</div></div>
  <div class="stat-card"><div class="stat-icon" style="color:#2980b9;">🛍️</div><div class="stat-val"><?= number_format($stats['total_products']) ?></div><div class="stat-label">Products</div></div>
  <div class="stat-card"><div class="stat-icon" style="color:#e74c3c;">⚠️</div><div class="stat-val"><?= number_format($stats['low_stock']) ?></div><div class="stat-label">Low Stock</div></div>
  <div class="stat-card"><div class="stat-icon" style="color:#27ae60;">👥</div><div class="stat-val"><?= number_format($stats['total_users']) ?></div><div class="stat-label">Users</div></div>
  <div class="stat-card"><div class="stat-icon" style="color:#8e44ad;">💰</div><div class="stat-val">RM <?= number_format((float)$stats['total_revenue'], 2) ?></div><div class="stat-label">Total Revenue</div></div>
  <div class="stat-card"><div class="stat-icon" style="color:#ff6b6b;">🏷️</div><div class="stat-val"><?= number_format($stats['active_promos']) ?></div><div class="stat-label">Active Promos</div></div>
</div>

<div class="grid-2">
  <!-- Recent Orders -->
  <div class="card">
    <div class="card-title"><i class="fas fa-shopping-cart" style="color:#ff6b6b;"></i> Recent Orders</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Customer</th><th>Status</th><th>Total</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($recent_orders as $o): ?>
          <tr>
            <td><strong>#<?= $o['order_id'] ?></strong></td>
            <td><?= htmlspecialchars($o['customer_name'] ?? 'Guest') ?></td>
            <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td>RM <?= number_format((float)$o['total_amount'], 2) ?></td>
            <td><a href="/admin/orders/<?= $o['order_id'] ?>" class="btn btn-sm btn-outline">View</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:12px;"><a href="/admin/orders" class="btn btn-sm btn-dark">All Orders</a></div>
  </div>

  <!-- Low Stock -->
  <div class="card">
    <div class="card-title"><i class="fas fa-exclamation-triangle" style="color:#f39c12;"></i> Low Stock Alert</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Product</th><th>SKU</th><th>Stock</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($low_stock_products as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['title']) ?></td>
            <td style="color:#7f8c8d;font-size:12px;"><?= htmlspecialchars($p['sku']) ?></td>
            <td><span style="color:<?= $p['stock_level'] == 0 ? '#e74c3c' : '#f39c12' ?>;font-weight:700;"><?= $p['stock_level'] ?></span></td>
            <td><a href="/admin/products/edit/<?= $p['product_id'] ?>" class="btn btn-sm btn-outline">Edit</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($low_stock_products)): ?><tr><td colspan="4" style="color:#7f8c8d;text-align:center;padding:20px;">No low stock items 🎉</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:12px;"><a href="/admin/products" class="btn btn-sm btn-dark">All Products</a></div>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
