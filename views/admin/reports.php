<?php declare(strict_types=1); $pageTitle = 'Reports'; include __DIR__ . '/_layout.php'; ?>

<div class="grid-2">
  <!-- Revenue by Month -->
  <div class="card">
    <div class="card-title"><i class="fas fa-chart-line" style="color:#ff6b6b;"></i> Revenue by Month</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Month</th><th>Orders</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php foreach ($revenue_by_month as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['month']) ?></td>
            <td><?= $row['order_count'] ?></td>
            <td><strong>RM <?= number_format((float)$row['revenue'], 2) ?></strong></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($revenue_by_month)): ?><tr><td colspan="3" style="text-align:center;color:#7f8c8d;padding:20px;">No revenue data yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Orders by Status -->
  <div class="card">
    <div class="card-title"><i class="fas fa-chart-pie" style="color:#ff6b6b;"></i> Orders by Status</div>
    <?php foreach ($orders_by_status as $row): ?>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
      <span class="badge badge-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span>
      <div style="flex:1;margin:0 12px;background:#f0f0f0;border-radius:4px;height:8px;overflow:hidden;">
        <?php
          $total_cnt = array_sum(array_column($orders_by_status, 'cnt'));
          $pct = $total_cnt > 0 ? round($row['cnt'] / $total_cnt * 100) : 0;
        ?>
        <div style="width:<?= $pct ?>%;height:100%;background:#ff6b6b;border-radius:4px;"></div>
      </div>
      <strong><?= $row['cnt'] ?></strong>
    </div>
    <?php endforeach; ?>
    <?php if (empty($orders_by_status)): ?><p style="color:#7f8c8d;text-align:center;">No orders yet.</p><?php endif; ?>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f0f0f0;font-size:13px;color:#7f8c8d;">
      New users (last 30 days): <strong style="color:#2d3436;"><?= number_format($new_users_30d) ?></strong>
    </div>
  </div>
</div>

<!-- Top Products -->
<div class="card">
  <div class="card-title"><i class="fas fa-trophy" style="color:#ff6b6b;"></i> Top 10 Products by Units Sold</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Product</th><th>SKU</th><th>Units Sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($top_products as $i => $p): ?>
        <tr>
          <td style="color:#7f8c8d;"><?= $i + 1 ?></td>
          <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
          <td style="color:#7f8c8d;font-size:12px;"><?= htmlspecialchars($p['sku']) ?></td>
          <td><?= number_format((int)$p['units_sold']) ?></td>
          <td><strong>RM <?= number_format((float)$p['revenue'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($top_products)): ?><tr><td colspan="5" style="text-align:center;color:#7f8c8d;padding:20px;">No sales data yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
