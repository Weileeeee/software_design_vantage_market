<?php declare(strict_types=1); $pageTitle = 'Orders'; include __DIR__ . '/_layout.php'; ?>

<div class="page-actions">
  <form method="GET" action="/admin/orders" style="display:flex;gap:8px;flex-wrap:wrap;margin:0;">
    <input type="text" name="search" class="form-control" placeholder="Order ID or customer email…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <select name="status" class="form-control" style="max-width:160px;">
      <option value="">All Statuses</option>
      <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
        <option value="<?= $s ?>" <?= (($_GET['status'] ?? '') === $s) ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
    <a href="/admin/orders" class="btn btn-outline">Reset</a>
  </form>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Status</th><th>Total</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong>#<?= $o['order_id'] ?></strong></td>
          <td>
            <div><?= htmlspecialchars($o['customer_name'] ?? 'Guest') ?></div>
            <div style="font-size:12px;color:#7f8c8d;"><?= htmlspecialchars($o['email_address'] ?? '') ?></div>
          </td>
          <td><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></td>
          <td><span class="badge badge-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
          <td><strong>RM <?= number_format((float)$o['total_amount'], 2) ?></strong></td>
          <td><a href="/admin/orders/<?= $o['order_id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?><tr><td colspan="6" style="text-align:center;color:#7f8c8d;padding:30px;">No orders found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
