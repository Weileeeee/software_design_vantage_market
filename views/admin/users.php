<?php declare(strict_types=1); $pageTitle = 'Users'; include __DIR__ . '/_layout.php'; ?>

<div class="page-actions">
  <form method="GET" action="/admin/users" style="display:flex;gap:8px;margin:0;">
    <input type="text" name="search" class="form-control" placeholder="Search by name or email…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
    <a href="/admin/users" class="btn btn-outline">Reset</a>
  </form>
  <div style="color:#7f8c8d;font-size:13px;"><?= count($users) ?> user(s) found</div>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= $u['user_id'] ?></td>
          <td><strong><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></strong></td>
          <td><?= htmlspecialchars($u['email_address']) ?></td>
          <td><span class="badge <?= !$u['is_locked'] ? 'badge-active' : 'badge-inactive' ?>"><?= !$u['is_locked'] ? 'Active' : 'Suspended' ?></span></td>
          <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <form method="POST" action="/admin/users/toggle" style="display:inline;" onsubmit="return confirm('Toggle this user\'s status?');">
              <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
              <button type="submit" class="btn btn-sm <?= !$u['is_locked'] ? 'btn-warning' : 'btn-success' ?>">
                <?= !$u['is_locked'] ? 'Suspend' : 'Activate' ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?><tr><td colspan="6" style="text-align:center;color:#7f8c8d;padding:30px;">No users found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
