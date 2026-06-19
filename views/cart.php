<?php
declare(strict_types=1);

/** @var array $cartItems */
/** @var string $userType */
/** @var string $userName */
$cartItems = $cartItems ?? [];
$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + (($item['stock_level'] > 0 ? $item['price'] * $item['quantity'] : 0)), 0.0);

// Group items by seller
$itemsBySeller = [];
foreach ($cartItems as $item) {
    $seller = $item['seller'] ?? 'VantageMarket';
    if (!isset($itemsBySeller[$seller])) {
        $itemsBySeller[$seller] = [];
    }
    $itemsBySeller[$seller][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shopping Cart - VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    .cart-container { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin: 30px 0; }
    .cart-products { background: white; border-radius: 4px; }
    .cart-section { background: white; margin-bottom: 20px; border-radius: 4px; overflow: hidden; }
    .seller-section { border: 1px solid #e0e0e0; }
    .seller-header { background: #f5f5f5; padding: 15px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #e0e0e0; }
    .seller-header label { margin: 0; cursor: pointer; flex: 1; display: flex; align-items: center; gap: 10px; }
    .seller-name { font-weight: 600; color: #333; }
    .product-item { padding: 15px; border-bottom: 1px solid #e0e0e0; display: grid; grid-template-columns: 60px 1fr 100px 100px 100px 80px; gap: 15px; align-items: center; }
    .product-item:last-child { border-bottom: none; }
    .product-image { width: 60px; height: 60px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .product-image img { width: 100%; height: 100%; object-fit: cover; border-radius: 4px; }
    .product-info { display: flex; flex-direction: column; gap: 5px; }
    .product-title { font-weight: 500; color: #333; font-size: 14px; }
    .product-seller { color: #999; font-size: 12px; }
    .price-cell { text-align: right; }
    .unit-price { color: #333; font-weight: 500; }
    .quantity-control { display: flex; align-items: center; gap: 5px; justify-content: center; }
    .qty-btn { width: 24px; height: 24px; border: 1px solid #ddd; background: white; cursor: pointer; }
    .qty-input { width: 40px; height: 24px; text-align: center; border: 1px solid #ddd; }
    .total-price { font-weight: 600; color: #ff6b6b; text-align: right; }
    .actions-cell { text-align: center; }
    .action-btn { background: none; border: none; color: #999; cursor: pointer; font-size: 14px; transition: 0.2s; }
    .action-btn:hover { color: #ff6b6b; }
    .product-item.unavailable { opacity: 0.55; background: #fafafa; }
    .unavailable-badge { display: inline-block; background: #ffe0e0; color: #c62828; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 12px; margin-top: 4px; letter-spacing: 0.3px; }
    .qty-btn:disabled, .qty-input:disabled { opacity: 0.4; cursor: not-allowed; pointer-events: none; }
    .table-header { display: grid; grid-template-columns: 60px 1fr 100px 100px 100px 80px; gap: 15px; padding: 15px; background: #fafafa; border-bottom: 1px solid #e0e0e0; font-size: 13px; font-weight: 600; color: #666; }
    .cart-summary { background: white; padding: 20px; border-radius: 4px; position: sticky; top: 20px; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e0e0e0; }
    .summary-row:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .summary-label { color: #666; }
    .summary-value { font-weight: 600; color: #333; }
    .total-row { font-size: 18px; }
    .total-row .summary-value { color: #ff6b6b; font-size: 20px; }
    .checkout-btn { width: 100%; background: #ff6b6b; color: white; border: none; padding: 12px; font-weight: 600; cursor: pointer; margin-top: 15px; border-radius: 4px; transition: 0.2s; }
    .checkout-btn:hover { background: #ff5252; }
    .voucher-section { margin-bottom: 15px; }
    .voucher-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; box-sizing: border-box; }
    .empty-cart { text-align: center; padding: 60px 20px; color: #999; }
    .empty-cart i { font-size: 48px; margin-bottom: 15px; color: #ddd; }
    .bulk-actions { padding: 15px; display: flex; gap: 10px; border-top: 1px solid #e0e0e0; }
    .bulk-actions-label { flex: 1; }
    .bulk-btn { padding: 8px 16px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; font-size: 13px; transition: 0.2s; }
    .bulk-btn:hover { border-color: #ff6b6b; color: #ff6b6b; }
    @media (max-width: 991px) { 
      .cart-container { grid-template-columns: 1fr; }
      .product-item { grid-template-columns: 50px 1fr 60px 60px; gap: 10px; }
      .table-header { grid-template-columns: 50px 1fr 60px 60px; }
      .price-cell, .total-price { font-size: 13px; }
      .cart-summary { position: static; }
    }
  </style>
</head>
<body>
  <!-- Topbar -->
  <div class="topbar">
    <div class="container d-flex align-center justify-between">
      <div class="topbar-links">
        <a href="#">About</a>
        <a href="#">Contact</a>
        <a href="#">Help</a>
        <a href="#">FAQs</a>
      </div>
      <div class="topbar-actions d-flex align-center">
        <?php if (($userType ?? 'Guest') === 'Guest'): ?>
          <span class="text-muted">Browsing as: Guest</span>
          <span class="badge">Guest Mode</span>
        <?php else: ?>
          <span class="text-muted">Account: <?= htmlspecialchars($userName ?? '') ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Mid Header -->
  <div class="mid-header">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo">VANTAGE<span class="logo-highlight">MARKET</span></a>
      <form action="/catalog" method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search for products...">
        <button type="submit"><i class="fa fa-search"></i></button>
      </form>
      <div class="customer-service">
        <span class="cs-title">Customer Service</span>
        <span class="cs-phone">+012 345 6789</span>
      </div>
    </div>
  </div>

  <!-- Navbar -->
  <div class="navbar">
    <div class="container d-flex align-center justify-between">
      <div class="d-flex align-center">
        <div class="nav-links">
          <a href="/">Home</a>
          <a href="/">Shop</a>
          <?php if (($userType ?? 'Guest') === 'Guest'): ?>
            <a href="/signin">Sign In</a>
            <a href="/register">Register</a>
          <?php else: ?>
            <a href="/orders">My Orders</a>
            <a href="/logout">Sign Out</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="nav-icons">
        <a href="/likes" class="nav-icon" title="My Likes">
          <i class="fas fa-heart"></i>
          <span class="nav-icon-badge">0</span>
        </a>
        <a href="/cart" class="nav-icon" title="View Cart" style="color: var(--primary);">
          <i class="fas fa-shopping-cart"></i>
          <span class="nav-icon-badge"><?= count($cartItems) ?></span>
        </a>
      </div>
    </div>
  </div>

  <div class="container">
    <?php if (empty($cartItems)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Start shopping to add items to your cart</p>
            <a href="/" class="checkout-btn" style="width: 200px; display: inline-block; text-decoration: none;">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-container">
          <!-- Left: Cart Items -->
          <div class="cart-products">
            <!-- Flash message -->
            <?php if (($_GET['action'] ?? '') === 'removed'): ?>
            <div style="background:#e8f5e9;padding:12px 16px;border-left:4px solid #43a047;margin-bottom:0;display:flex;align-items:center;gap:10px;">
              <i class="fas fa-check-circle" style="color:#43a047;"></i>
              <span style="color:#2e7d32;font-size:13px;">Item removed from cart.</span>
            </div>
            <?php elseif (($_GET['action'] ?? '') === 'noselect'): ?>
            <div style="background:#fff3e0;padding:12px 16px;border-left:4px solid #fb8c00;margin-bottom:0;display:flex;align-items:center;gap:10px;">
              <i class="fas fa-exclamation-circle" style="color:#fb8c00;"></i>
              <span style="color:#e65100;font-size:13px;">Please select at least one item to checkout.</span>
            </div>
            <?php endif; ?>

            <!-- Promo Banner -->
            <div style="background: #ffe8e8; padding: 15px; border-bottom: 1px solid #ffd4d4; display: flex; align-items: center; gap: 10px;">
              <i class="fas fa-tag" style="color: #ff6b6b;"></i>
              <span style="color: #333; font-size: 13px;"><strong>Shop Up to 50% Off 6 Deals Now!</strong></span>
            </div>

            <!-- Items by Seller -->
            <?php foreach ($itemsBySeller as $seller => $items): ?>
                <div class="seller-section">
                    <div class="seller-header">
                        <input type="checkbox" class="seller-check" data-seller="<?= htmlspecialchars($seller) ?>">
                        <label style="margin: 0; cursor: pointer; flex: 1;">
                            <i class="fas fa-store" style="color: #999;"></i>
                            <span class="seller-name"><?= htmlspecialchars($seller) ?></span>
                        </label>
                    </div>

                    <div class="table-header">
                        <div>Product</div>
                        <div></div>
                        <div>Unit Price</div>
                        <div>Quantity</div>
                        <div>Total Price</div>
                        <div>Actions</div>
                    </div>

                    <?php foreach ($items as $index => $item):
                        $outOfStock = (int)($item['stock_level'] ?? 1) <= 0;
                    ?>
                        <div class="product-item<?= $outOfStock ? ' unavailable' : '' ?>">
                            <input type="checkbox" class="item-check"
                                data-item-id="<?= $index ?>"
                                data-product-id="<?= $item['product_id'] ?>"
                                data-price="<?= $item['price'] ?>"
                                data-qty="<?= $item['quantity'] ?>"
                                data-out-of-stock="<?= $outOfStock ? '1' : '0' ?>"
                                <?= $outOfStock ? 'disabled title="Item is out of stock"' : '' ?>>
                            <div class="product-info">
                                <div class="product-title"><?= htmlspecialchars($item['title']) ?></div>
                                <div class="product-seller">by <?= htmlspecialchars($seller) ?></div>
                                <?php if ($outOfStock): ?>
                                    <span class="unavailable-badge">⚠ Unavailable</span>
                                <?php endif; ?>
                            </div>
                            <div class="price-cell">
                                <div class="unit-price">RM <?= number_format((float)$item['price'], 2) ?></div>
                            </div>
                            <div>
                                <div class="quantity-control">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $index ?>, -1)" <?= $outOfStock ? 'disabled' : '' ?>>−</button>
                                    <input type="number" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock_level'] ?>" data-item="<?= $index ?>" <?= $outOfStock ? 'disabled' : '' ?>>
                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $index ?>, 1)" <?= $outOfStock ? 'disabled' : '' ?>>+</button>
                                </div>
                            </div>
                            <div class="price-cell">
                                <?php if ($outOfStock): ?>
                                    <div class="total-price" style="color:#bbb;">—</div>
                                <?php else: ?>
                                    <div class="total-price" data-item-price="<?= $index ?>">RM <?= number_format((float)$item['price'] * $item['quantity'], 2) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="actions-cell">
                                <button type="button" class="action-btn" title="Delete" onclick="deleteItem(<?= $item['product_id'] ?>, this)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <br>
                                <button type="button" class="action-btn" title="Add to Favorites" onclick="addToFavourites(<?= $item['product_id'] ?>, <?= htmlspecialchars(json_encode($item['title'])) ?>, this)" style="margin-top: 5px;">
                                    <i class="fas fa-heart"></i> Like
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="bulk-actions">
                        <div class="bulk-actions-label">
                            <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" class="select-all-seller" data-seller="<?= htmlspecialchars($seller) ?>">
                                <span style="font-size: 13px; color: #666;">Select All</span>
                            </label>
                        </div>
                        <button type="button" class="bulk-btn" onclick="deleteSelected()">Delete</button>
                        <button type="button" class="bulk-btn" onclick="likeSelected()">Move to My Likes</button>
                    </div>
                </div>
            <?php endforeach; ?>
          </div>

          <!-- Right: Summary -->
          <div class="cart-summary">
            <h3 style="margin-top: 0; font-size: 16px;">Order Summary</h3>

            <div class="voucher-section">
                <label style="font-size: 12px; color: #666; display: block; margin-bottom: 5px;">Voucher / Discount</label>
                <input type="text" id="voucher-input" class="voucher-input" placeholder="Add shop voucher code">
                <button type="button" id="voucher-apply-btn" style="background:none; border:none; color:#ff6b6b; font-size:12px; font-weight:600; cursor:pointer; padding:6px 0 0; text-decoration:underline;">Apply Code</button>
                <p id="voucher-feedback" style="font-size:12px; margin:4px 0 0; display:none;"></p>
            </div>

            <div class="summary-row">
                <span class="summary-label">Subtotal (<span id="summary-count">0</span> item<span id="summary-plural"></span>)</span>
                <span class="summary-value" id="summary-subtotal">RM 0.00</span>
            </div>

            <div class="summary-row">
                <span class="summary-label">Shipping</span>
                <span class="summary-value" id="summary-shipping">RM 0.00</span>
            </div>

            <div class="summary-row">
                <span class="summary-label">Discount</span>
                <span class="summary-value" id="summary-discount" style="color:#27ae60;">-RM 0.00</span>
            </div>

            <div class="summary-row total-row">
                <span class="summary-label">Total</span>
                <span class="summary-value" id="summary-total">RM 0.00</span>
            </div>

            <p id="summary-hint" style="font-size:12px;color:#999;text-align:center;margin:0 0 10px;">Select items above to checkout</p>

            <!-- Hidden form: posts selected product_ids to /checkout/process -->
            <form id="checkout-form" method="POST" action="/checkout/selected">
              <div id="checkout-hidden-inputs"></div>
              <button type="submit" id="checkout-btn" class="checkout-btn" disabled style="opacity:0.5;cursor:not-allowed;">
                Check Out
              </button>
            </form>
          </div>
        </div>
    <?php endif; ?>
  </div>

  <script>
    function updateQty(index, change) {
        const input = document.querySelector(`input[data-item="${index}"]`);
        if (!input) return;
        const max = parseInt(input.getAttribute('max')) || Infinity;
        let value = parseInt(input.value) + change;
        
        if (value < 1) value = 1;
        if (value > max) {
            value = max;
            if (typeof showCartToast === 'function') {
                showCartToast('Maximum stock reached for this item.');
            }
        }
        input.value = value;
        
        // Update the visual total price for this row
        const cb = document.querySelector(`.item-check[data-item-id="${index}"]`);
        if (cb) {
            const price = parseFloat(cb.dataset.price) || 0;
            const totalCell = document.querySelector(`.total-price[data-item-price="${index}"]`);
            if (totalCell) {
                totalCell.textContent = 'RM ' + (price * value).toFixed(2);
            }
        }

        // Trigger a change event so other listeners (like recalcSummary) catch it
        input.dispatchEvent(new Event('change'));
    }

    // Attach event listeners to quantity inputs to enforce max limits when typing manually
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max')) || Infinity;
                let val = parseInt(this.value);
                if (isNaN(val) || val < 1) val = 1;
                if (val > max) {
                    val = max;
                    if (typeof showCartToast === 'function') {
                        showCartToast('Maximum stock reached for this item.');
                    }
                }
                this.value = val;
                
                // Update the visual total price for this row
                const index = this.dataset.item;
                const cb = document.querySelector(`.item-check[data-item-id="${index}"]`);
                if (cb) {
                    const price = parseFloat(cb.dataset.price) || 0;
                    const totalCell = document.querySelector(`.total-price[data-item-price="${index}"]`);
                    if (totalCell) {
                        totalCell.textContent = 'RM ' + (price * val).toFixed(2);
                    }
                }
            });
        });
    });

    function deleteItem(productId, btn) {
        if (!confirm('Remove this item from your cart?')) return;

        // Disable button to prevent double-click
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Removing...'; }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/cart/remove';
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'product_id';
        input.value = productId;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    // ── Favourites from localStorage ─────────────────────────
    const FAVES_KEY = 'vm_favourites';
    function getFavourites() {
        try {
            const raw = JSON.parse(localStorage.getItem(FAVES_KEY)) || [];
            // Migrate old format [{id, title}] → flat [id]
            if (raw.length > 0 && typeof raw[0] === 'object' && raw[0] !== null) {
                const migrated = raw.map(f => f.id);
                localStorage.setItem(FAVES_KEY, JSON.stringify(migrated));
                return migrated;
            }
            return raw;
        } catch (_) { return []; }
    }
    function saveFavourites(f) { localStorage.setItem(FAVES_KEY, JSON.stringify(f)); }

    function addToFavourites(productId, title, btn) {
        let faves = getFavourites();
        if (!faves.includes(productId)) {
            faves.push(productId);
            saveFavourites(faves);
        }
        if (btn) {
            btn.style.color = '#ff6b6b';
            btn.innerHTML   = '<i class="fas fa-heart"></i> Liked';
            setTimeout(() => {
                btn.style.color = '';
                btn.innerHTML   = '<i class="fas fa-heart"></i> Like';
            }, 1500);
        }
        showCartToast('❤️ Added to Favourites: ' + title);
    }

    function deleteSelected() {
        const selected = document.querySelectorAll('.item-check:checked');
        if (selected.length === 0) {
            alert('Please select items to delete.');
            return;
        }
        if (!confirm('Remove ' + selected.length + ' selected item(s) from cart?')) return;

        // Submit one-by-one via chained hidden forms (server handles redirect)
        // Collect product IDs from checked rows
        const ids = Array.from(selected).map(cb => cb.dataset?.productId).filter(Boolean);
        if (!ids.length) { alert('Could not identify selected items.'); return; }

        // Delete first item and let server redirect back; repeat on reload via URL param
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/cart/remove';
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = 'product_id';
        input.value = ids[0];
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    function likeSelected() {
        const selected = document.querySelectorAll('.item-check:checked');
        if (selected.length === 0) {
            alert('Please select items to add to favourites.');
            return;
        }
        // Collect titles from selected rows
        selected.forEach(cb => {
            const row = cb.closest('tr') || cb.closest('.cart-row');
            const titleEl = row?.querySelector('.product-title');
            const pid = cb.dataset?.productId || cb.value;
            if (titleEl && pid) addToFavourites(parseInt(pid), titleEl.textContent.trim(), null);
        });
        showCartToast('❤️ ' + selected.length + ' item(s) added to Favourites!');
    }

    function showCartToast(msg) {
        let toast = document.getElementById('cart-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'cart-toast';
            toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#333;color:#fff;padding:12px 20px;border-radius:4px;font-size:14px;z-index:9999;opacity:0;transition:opacity 0.3s;pointer-events:none;max-width:280px;';
            document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.style.opacity = '1';
        clearTimeout(toast._t);
        toast._t = setTimeout(() => { toast.style.opacity = '0'; }, 2500);
    }

    // Seller select all
    document.querySelectorAll('.select-all-seller').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const seller = this.dataset.seller;
            const checks = document.querySelectorAll(`.item-check`);
            checks.forEach(check => {
                if (check.closest('.seller-section').querySelector(`[data-seller="${seller}"]`)) {
                    check.checked = this.checked;
                }
            });
        });
    });

    // ── Selected-items summary recalculator ─────────────────
    let appliedPromo = null; // { code, discount_type, discount_value }

    function recalcSummary() {
      const checked = document.querySelectorAll('.item-check:checked');
      let subtotal = 0;
      let count    = 0;

      // Build hidden inputs for the checkout form
      const hiddenContainer = document.getElementById('checkout-hidden-inputs');
      hiddenContainer.innerHTML = '';

      checked.forEach(cb => {
        // Skip out-of-stock items (should not be checkable, but guard anyway)
        if (cb.dataset.outOfStock === '1') return;

        const price = parseFloat(cb.dataset.price) || 0;
        const qty   = parseInt(cb.dataset.qty)    || 1;
        subtotal += price * qty;
        count    += 1;

        // Add hidden input so the checkout form knows which products are selected
        const inp  = document.createElement('input');
        inp.type   = 'hidden';
        inp.name   = 'selected_products[]';
        inp.value  = cb.dataset.productId;
        hiddenContainer.appendChild(inp);

        // Add hidden input for the quantities as well
        const inpQty = document.createElement('input');
        inpQty.type  = 'hidden';
        inpQty.name  = `quantities[${cb.dataset.productId}]`;
        inpQty.value = qty;
        hiddenContainer.appendChild(inpQty);
      });

      // Carry the applied promo code forward into the checkout flow
      if (appliedPromo) {
        const inpPromo = document.createElement('input');
        inpPromo.type  = 'hidden';
        inpPromo.name  = 'promo_code';
        inpPromo.value = appliedPromo.code;
        hiddenContainer.appendChild(inpPromo);
      }

      const shipping = count > 0 ? 5.00 : 0;

      let discount = 0;
      if (appliedPromo && count > 0) {
        if (appliedPromo.discount_type === 'percentage') {
          discount = subtotal * (appliedPromo.discount_value / 100);
        } else {
          discount = appliedPromo.discount_value;
        }
        discount = Math.min(discount, subtotal); // never go negative
      }

      const total = Math.max(subtotal + shipping - discount, 0);

      document.getElementById('summary-count').textContent    = count;
      document.getElementById('summary-plural').textContent   = count === 1 ? '' : 's';
      document.getElementById('summary-subtotal').textContent = 'RM ' + subtotal.toFixed(2);
      document.getElementById('summary-shipping').textContent = 'RM ' + shipping.toFixed(2);
      document.getElementById('summary-discount').textContent = '-RM ' + discount.toFixed(2);
      document.getElementById('summary-total').textContent    = 'RM ' + total.toFixed(2);

      const btn  = document.getElementById('checkout-btn');
      const hint = document.getElementById('summary-hint');
      if (count > 0) {
        btn.disabled           = false;
        btn.style.opacity      = '1';
        btn.style.cursor       = 'pointer';
        hint.style.display     = 'none';
      } else {
        btn.disabled           = true;
        btn.style.opacity      = '0.5';
        btn.style.cursor       = 'not-allowed';
        hint.style.display     = 'block';
      }
    }

    // ── Voucher / promo code apply button ─────────────────
    document.getElementById('voucher-input').addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('voucher-apply-btn').click();
      }
    });

    document.getElementById('voucher-apply-btn').addEventListener('click', async () => {
      const input    = document.getElementById('voucher-input');
      const feedback = document.getElementById('voucher-feedback');
      const code     = input.value.trim();

      if (!code) {
        feedback.style.display = 'block';
        feedback.style.color   = '#c62828';
        feedback.textContent   = 'Please enter a code.';
        return;
      }

      try {
        const res  = await fetch('/promo/validate', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'code=' + encodeURIComponent(code)
        });
        const data = await res.json();

        feedback.style.display = 'block';
        if (data.valid) {
          appliedPromo = {
            code: data.code,
            discount_type: data.discount_type,
            discount_value: data.discount_value
          };
          feedback.style.color = '#27ae60';
          feedback.textContent = '✓ ' + data.message;
          input.disabled = true;
          document.getElementById('voucher-apply-btn').textContent = 'Remove';
          document.getElementById('voucher-apply-btn').dataset.applied = '1';
        } else {
          appliedPromo = null;
          feedback.style.color = '#c62828';
          feedback.textContent = data.message;
        }
        recalcSummary();
      } catch (e) {
        feedback.style.display = 'block';
        feedback.style.color   = '#c62828';
        feedback.textContent   = 'Could not validate code. Please try again.';
      }
    });

    // Allow removing an applied promo
    document.getElementById('voucher-apply-btn').addEventListener('click', function() {
      if (this.dataset.applied === '1') {
        appliedPromo = null;
        document.getElementById('voucher-input').disabled = false;
        document.getElementById('voucher-input').value = '';
        document.getElementById('voucher-feedback').style.display = 'none';
        this.textContent = 'Apply Code';
        this.dataset.applied = '0';
        recalcSummary();
      }
    }, { capture: true });

    // Attach recalc to every item checkbox
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.item-check').forEach(cb => {
        // Prevent out-of-stock items from ever being checked
        if (cb.dataset.outOfStock === '1') {
          cb.checked = false;
          cb.disabled = true;
          return;
        }
        cb.addEventListener('change', () => {
          // Keep data-qty in sync with the quantity input
          const row = cb.closest('.product-item');
          if (row) {
            const qtyInput = row.querySelector('.qty-input');
            if (qtyInput) cb.dataset.qty = qtyInput.value;
          }
          recalcSummary();
        });
      });
      recalcSummary(); // Initial state
    });

    // Re-sync qty when quantity buttons are clicked
    const origUpdateQty = window.updateQty;
    window.updateQty = function(index, change) {
      if (origUpdateQty) origUpdateQty(index, change);
      // Update data-qty on corresponding checkbox after qty change
      const input = document.querySelector(`.qty-input[data-item="${index}"]`);
      const cb    = document.querySelector(`.item-check[data-item-id="${index}"]`);
      if (input && cb) {
        let val = parseInt(input.value) + change;
        if (val < 1) val = 1;
        input.value  = val;
        cb.dataset.qty = val;
        recalcSummary();
      }
    };
  </script>
</body>
</html>
