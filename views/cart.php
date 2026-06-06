<?php
declare(strict_types=1);

/** @var array $cartItems */
/** @var string $userType */
/** @var string $userName */
$cartItems = $cartItems ?? [];
$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0.0);

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
    .voucher-input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; }
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
            <!-- Promo Banner -->
            <div style="background: #ffe8e8; padding: 15px; border-bottom: 1px solid #ffd4d4; border-radius: 4px 4px 0 0; display: flex; align-items: center; gap: 10px;">
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

                    <?php foreach ($items as $index => $item): ?>
                        <div class="product-item">
                            <input type="checkbox" class="item-check" data-item-id="<?= $index ?>" data-price="<?= $item['price'] ?>" data-qty="<?= $item['quantity'] ?>">
                            <div class="product-info">
                                <div class="product-title"><?= htmlspecialchars($item['title']) ?></div>
                                <div class="product-seller">by <?= htmlspecialchars($seller) ?></div>
                            </div>
                            <div class="price-cell">
                                <div class="unit-price">RM <?= number_format((float)$item['price'], 2) ?></div>
                            </div>
                            <div>
                                <div class="quantity-control">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $index ?>, -1)">−</button>
                                    <input type="number" class="qty-input" value="<?= $item['quantity'] ?>" min="1" data-item="<?= $index ?>">
                                    <button type="button" class="qty-btn" onclick="updateQty(<?= $index ?>, 1)">+</button>
                                </div>
                            </div>
                            <div class="price-cell">
                                <div class="total-price">RM <?= number_format((float)$item['price'] * $item['quantity'], 2) ?></div>
                            </div>
                            <div class="actions-cell">
                                <button type="button" class="action-btn" title="Delete" onclick="deleteItem(<?= $index ?>)">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                                <br>
                                <button type="button" class="action-btn" title="Add to Favorites" onclick="addToFavorites(<?= $index ?>)" style="margin-top: 5px;">
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
                <input type="text" class="voucher-input" placeholder="Add shop voucher code" readonly>
            </div>

            <div class="summary-row">
                <span class="summary-label">Subtotal</span>
                <span class="summary-value">RM <?= number_format($cartTotal, 2) ?></span>
            </div>

            <div class="summary-row">
                <span class="summary-label">Shipping</span>
                <span class="summary-value">RM 5.00</span>
            </div>

            <div class="summary-row">
                <span class="summary-label">Discount</span>
                <span class="summary-value">-RM 0.00</span>
            </div>

            <div class="summary-row total-row">
                <span class="summary-label">Total (<?= count($cartItems) ?> item<?= count($cartItems) > 1 ? 's' : '' ?>)</span>
                <span class="summary-value">RM <?= number_format($cartTotal + 5, 2) ?></span>
            </div>

            <a href="/checkout" class="checkout-btn" style="text-decoration: none; display: block; text-align: center;">Check Out</a>
          </div>
        </div>
    <?php endif; ?>
  </div>

  <script>
    function updateQty(index, change) {
        const input = document.querySelector(`input[data-item="${index}"]`);
        let value = parseInt(input.value) + change;
        if (value < 1) value = 1;
        input.value = value;
    }

    function deleteItem(index) {
        if (confirm('Remove this item from cart?')) {
            // AJAX call to delete item
            console.log('Delete item:', index);
        }
    }

    function addToFavorites(index) {
        alert('Item added to favorites');
    }

    function deleteSelected() {
        const selected = document.querySelectorAll('.item-check:checked');
        if (selected.length === 0) {
            alert('Please select items to delete');
            return;
        }
        if (confirm('Delete selected items?')) {
            // AJAX call to delete selected items
            console.log('Delete selected:', selected.length);
        }
    }

    function likeSelected() {
        const selected = document.querySelectorAll('.item-check:checked');
        if (selected.length === 0) {
            alert('Please select items to add to favorites');
            return;
        }
        alert(selected.length + ' item(s) added to favorites');
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
  </script>
</body>
</html>
