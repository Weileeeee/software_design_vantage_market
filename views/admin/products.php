<?php declare(strict_types=1); $pageTitle = 'Products'; include __DIR__ . '/_layout.php'; ?>

<?php if ($success ?? null): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="page-actions">
  <form method="GET" action="/admin/products" class="search-bar" style="margin:0;flex-wrap:wrap;gap:8px;">
    <input type="text" name="search" class="form-control" placeholder="Search title or SKU…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <select name="category" class="form-control" style="max-width:180px;">
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= $c['category_id'] ?>" <?= (($_GET['category'] ?? '') == $c['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
    <a href="/admin/products" class="btn btn-outline">Reset</a>
  </form>
  <a href="/admin/products/new" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</a>
</div>

<div class="card" style="padding:0;">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Image</th><th>ID</th><th>Title</th><th>SKU</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= htmlspecialchars($p['image_url']) ?>" style="width:42px;height:42px;object-fit:cover;border-radius:4px;border:1px solid #e0e0e0;" onerror="this.style.display='none';">
            <?php else: ?>
              <div style="width:42px;height:42px;background:#f0f0f0;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#bbb;"><i class="fas fa-image"></i></div>
            <?php endif; ?>
          </td>
          <td><?= $p['product_id'] ?></td>
          <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
          <td style="color:#7f8c8d;font-size:12px;"><?= htmlspecialchars($p['sku']) ?></td>
          <td><?= htmlspecialchars($p['category_name'] ?? '—') ?></td>
          <td>RM <?= number_format((float)$p['price'], 2) ?></td>
          <td>
            <?php $sl = (int)$p['stock_level']; ?>
            <span style="font-weight:700;color:<?= $sl == 0 ? '#e74c3c' : ($sl <= 10 ? '#f39c12' : '#27ae60') ?>;"><?= $sl ?></span>
          </td>
          <td><span class="badge <?= $p['status'] === 'active' ? 'badge-active' : 'badge-inactive' ?>"><?= ucfirst($p['status']) ?></span></td>
          <td>
            <a href="/admin/products/edit/<?= $p['product_id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
            <?php if ($p['status'] === 'active'): ?>
            <form method="POST" action="/admin/products/delete" style="display:inline;" onsubmit="return confirm('Archive this product?');">
              <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-archive"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?><tr><td colspan="9" style="text-align:center;color:#7f8c8d;padding:30px;">No products found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
