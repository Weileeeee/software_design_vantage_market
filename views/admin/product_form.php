<?php
declare(strict_types=1);
$isEdit    = !empty($product);
$pageTitle = $isEdit ? 'Edit Product #' . $product['product_id'] : 'Add New Product';
include __DIR__ . '/_layout.php';
?>

<?php if ($error ?? null): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success ?? null): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

<div style="margin-bottom:16px;"><a href="/admin/products" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to Products</a></div>

<div class="card">
  <form method="POST" action="/admin/products/save">
    <?php if ($isEdit): ?><input type="hidden" name="product_id" value="<?= $product['product_id'] ?>"><?php endif; ?>
    <div class="grid-2">
      <div class="form-group">
        <label>Product Title *</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($product['title'] ?? '') ?>" required placeholder="e.g. Mechanical Keyboard">
      </div>
      <div class="form-group">
        <label>SKU</label>
        <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($product['sku'] ?? '') ?>" placeholder="e.g. MK-001">
      </div>
      <div class="form-group">
        <label>Price (RM) *</label>
        <input type="number" name="price" class="form-control" step="0.01" min="0" value="<?= $product['price'] ?? '' ?>" required placeholder="0.00">
      </div>
      <div class="form-group">
        <label>Stock Level *</label>
        <input type="number" name="stock_level" class="form-control" min="0" value="<?= $product['stock_level'] ?? 0 ?>" required>
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category_id" class="form-control">
          <option value="">— Select Category —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['category_id'] ?>" <?= (($product['category_id'] ?? '') == $c['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Brand</label>
        <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($product['brand'] ?? '') ?>" placeholder="e.g. Logitech">
      </div>
    </div>
    <div class="form-group">
      <label>Description</label>
      <textarea name="description" class="form-control" rows="4" placeholder="Product description…"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
    </div>
    <div class="form-group">
      <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
        <input type="checkbox" name="is_active" value="1" <?= (!$isEdit || ($product['status'] ?? 'active') === 'active') ? 'checked' : '' ?> style="width:16px;height:16px;">
        Active (visible to customers)
      </label>
    </div>
    <div style="display:flex;gap:10px;margin-top:6px;">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <?= $isEdit ? 'Save Changes' : 'Create Product' ?></button>
      <a href="/admin/products" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/_layout_end.php'; ?>
