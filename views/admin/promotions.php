<?php declare(strict_types=1); $pageTitle = 'Promotions'; include __DIR__ . '/_layout.php'; ?>

<?php if ($success ?? null): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error ?? null): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="grid-2" style="align-items:start;">
  <!-- Create Promo -->
  <div class="card">
    <div class="card-title"><i class="fas fa-plus-circle" style="color:#ff6b6b;"></i> Create New Promo Code</div>
    <form method="POST" action="/admin/promotions/save">
      <div class="grid-2">
        <div class="form-group">
          <label>Promo Code *</label>
          <input type="text" name="code" class="form-control" placeholder="e.g. SAVE20" required style="text-transform:uppercase;">
        </div>
        <div class="form-group">
          <label>Discount Type</label>
          <select name="discount_type" class="form-control">
            <option value="percentage">Percentage (%)</option>
            <option value="fixed">Fixed Amount (RM)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Discount Value *</label>
          <input type="number" name="discount_value" class="form-control" step="0.01" min="0.01" required placeholder="e.g. 10">
        </div>
        <div class="form-group">
          <label>Min. Spend (RM)</label>
          <input type="number" name="min_spend" class="form-control" step="0.01" min="0" value="0">
        </div>
        <div class="form-group">
          <label>Usage Limit</label>
          <input type="number" name="usage_limit" class="form-control" min="1" placeholder="Leave blank = unlimited">
        </div>
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group" style="grid-column:span 2;">
          <label>Expiry Date *</label>
          <input type="date" name="expiry_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Promo</button>
    </form>
  </div>

  <!-- Promo List -->
  <div class="card" style="padding:0;">
    <div class="card-title" style="padding:18px 22px 14px;"><i class="fas fa-tag" style="color:#ff6b6b;"></i> Existing Promos</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Code</th><th>Discount</th><th>Expiry</th><th>Used</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($promos as $p): ?>
          <?php $expired = strtotime($p['expiry_date']) < time(); ?>
          <tr style="<?= $expired ? 'opacity:0.5;' : '' ?>">
            <td><strong><?= htmlspecialchars($p['code']) ?></strong><?= $expired ? ' <span style="font-size:11px;color:#e74c3c;">(expired)</span>' : '' ?></td>
            <td><?= $p['discount_type'] === 'percentage' ? $p['discount_value'] . '%' : 'RM ' . number_format((float)$p['discount_value'], 2) ?></td>
            <td><?= date('d M Y', strtotime($p['expiry_date'])) ?></td>
            <td><?= $p['used_count'] ?? 0 ?><?= $p['usage_limit'] ? '/' . $p['usage_limit'] : '' ?></td>
            <td>
              <form method="POST" action="/admin/promotions/delete" style="display:inline;" onsubmit="return confirm('Delete this promo code?');">
                <input type="hidden" name="promo_id" value="<?= $p['promo_id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($promos)): ?><tr><td colspan="5" style="text-align:center;color:#7f8c8d;padding:20px;">No promotions yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
