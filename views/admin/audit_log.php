<?php declare(strict_types=1); $pageTitle = 'Audit Log'; include __DIR__ . '/_layout.php'; ?>

<div class="card" style="padding:0;">
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Admin</th><th>Action</th><th>Table</th><th>Target ID</th><th>IP</th><th>Timestamp</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td style="color:#7f8c8d;"><?= $log['log_id'] ?></td>
          <td><strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong></td>
          <td>
            <code style="background:#f8f9fa;padding:2px 8px;border-radius:3px;font-size:12px;color:#e74c3c;">
              <?= htmlspecialchars($log['action_type']) ?>
            </code>
          </td>
          <td style="color:#7f8c8d;font-size:12px;"><?= htmlspecialchars($log['target_table'] ?? '—') ?></td>
          <td style="color:#7f8c8d;"><?= $log['target_id'] ?? '—' ?></td>
          <td style="font-size:12px;color:#7f8c8d;"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
          <td style="font-size:12px;"><?= date('d M Y, H:i:s', strtotime($log['logged_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?><tr><td colspan="7" style="text-align:center;color:#7f8c8d;padding:30px;">No audit log entries yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
