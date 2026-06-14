<?php declare(strict_types=1); $pageTitle = 'Order #' . $order['order_id']; include __DIR__ . '/_layout.php'; ?>

<?php if ($success ?? null): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<div style="margin-bottom:16px;"><a href="/admin/orders" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Orders</a></div>

<div class="grid-2">
  <!-- Order info -->
  <div class="card">
    <div class="card-title"><i class="fas fa-info-circle" style="color:#ff6b6b;"></i> Order Details</div>
    <table style="width:100%;font-size:14px;">
      <tr><td style="color:#7f8c8d;padding:6px 0;">Order ID</td><td><strong>#<?= $order['order_id'] ?></strong></td></tr>
      <tr><td style="color:#7f8c8d;padding:6px 0;">Status</td><td><span class="badge badge-<?= $order['status'] ?>"><?= ucfirst($order['status']) ?></span></td></tr>
      <tr><td style="color:#7f8c8d;padding:6px 0;">Total</td><td><strong>RM <?= number_format((float)$order['total_amount'], 2) ?></strong></td></tr>
      <tr><td style="color:#7f8c8d;padding:6px 0;">Placed</td><td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td></tr>
      <tr><td style="color:#7f8c8d;padding:6px 0;">Customer</td><td><?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></td></tr>
      <tr><td style="color:#7f8c8d;padding:6px 0;">Email</td><td><?= htmlspecialchars($order['email_address'] ?? '—') ?></td></tr>
      <tr><td style="color:#7f8c8d;padding:6px 0;">Address</td><td><?= htmlspecialchars(trim(($order['street_address'] ?? '') . ' ' . ($order['city'] ?? '') . ' ' . ($order['postcode'] ?? ''))) ?: '—' ?></td></tr>
    </table>
  </div>

  <!-- Update Status -->
  <div class="card">
    <div class="card-title"><i class="fas fa-edit" style="color:#ff6b6b;"></i> Update Order Status</div>
    <form method="POST" action="/admin/orders/update-status">
      <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
      <div class="form-group">
        <label>New Status</label>
        <select name="status" class="form-control">
          <?php foreach (['pending','confirmed','processing','shipped','delivered','cancelled','refunded'] as $s): ?>
            <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Status</button>
    </form>
  </div>
</div>

<!-- Order Items -->
<div class="card">
  <div class="card-title"><i class="fas fa-list" style="color:#ff6b6b;"></i> Order Items</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
      <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
          <td style="color:#7f8c8d;font-size:12px;"><?= htmlspecialchars($item['sku']) ?></td>
          <td><?= $item['quantity'] ?></td>
          <td>RM <?= number_format((float)$item['purchased_price'], 2) ?></td>
          <td><strong>RM <?= number_format((float)$item['purchased_price'] * (int)$item['quantity'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" style="text-align:right;font-weight:700;padding:12px 14px;">Order Total:</td>
          <td style="font-weight:800;color:#ff6b6b;">RM <?= number_format((float)$order['total_amount'], 2) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
