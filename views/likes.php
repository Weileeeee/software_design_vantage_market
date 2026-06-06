<?php
declare(strict_types=1);

global $favoriteItems, $userType, $userName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Likes - VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    .likes-container { margin: 30px 0; }
    .likes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
    .product-card { background: white; border-radius: 4px; overflow: hidden; border: 1px solid #e0e0e0; transition: 0.3s; }
    .product-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,0.1); }
    .product-card-image { width: 100%; height: 180px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
    .product-card-image img { width: 100%; height: 100%; object-fit: cover; }
    .product-card-image .like-btn { position: absolute; top: 10px; right: 10px; background: white; border: none; color: #ff6b6b; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); transition: 0.2s; }
    .product-card-image .like-btn:hover { background: #ff6b6b; color: white; }
    .product-card-content { padding: 12px; }
    .product-card-title { font-weight: 600; color: #333; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .product-card-seller { color: #999; font-size: 11px; margin: 4px 0; }
    .product-card-price { color: #ff6b6b; font-weight: 700; font-size: 14px; margin: 8px 0; }
    .product-card-rating { color: #ffa500; font-size: 12px; margin-bottom: 8px; }
    .product-card-actions { display: flex; gap: 8px; }
    .action-btn { flex: 1; padding: 8px; border: 1px solid #ddd; background: white; color: #ff6b6b; cursor: pointer; border-radius: 3px; font-size: 12px; font-weight: 600; transition: 0.2s; }
    .action-btn:hover { border-color: #ff6b6b; }
    .action-btn.add-cart { background: #ff6b6b; color: white; border-color: #ff6b6b; }
    .action-btn.add-cart:hover { background: #ff5252; }
    .empty-state { text-align: center; padding: 60px 20px; }
    .empty-state i { font-size: 64px; color: #ddd; margin-bottom: 20px; }
    .empty-state h3 { color: #999; font-size: 18px; margin-bottom: 10px; }
    .empty-state p { color: #bbb; margin-bottom: 30px; }
    .filter-section { background: white; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; gap: 15px; align-items: center; }
    .filter-btn { padding: 8px 16px; border: 1px solid #ddd; background: white; cursor: pointer; border-radius: 4px; font-size: 13px; transition: 0.2s; }
    .filter-btn.active { background: #ff6b6b; color: white; border-color: #ff6b6b; }
    .filter-btn:hover { border-color: #ff6b6b; color: #ff6b6b; }
    @media (max-width: 768px) {
      .likes-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
      .product-card-image { height: 140px; }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <div class="mid-header" style="border-bottom: 1px solid #e0e0e0; background: #ff6b6b; color: white;">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo" style="color: white;">VANTAGE<span class="logo-highlight">MARKET</span></a>
      <div style="color: white; font-size: 14px;">My Likes</div>
    </div>
  </div>

  <div class="container likes-container">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 20px; font-size: 13px; color: #666;">
      <a href="/" style="color: #ff6b6b; text-decoration: none;">Home</a>
      <span style="margin: 0 5px;">/</span>
      <span>My Likes</span>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <span style="font-weight: 600; color: #333;">Filter:</span>
      <button class="filter-btn active" onclick="filterLikes('all')">All Items</button>
      <button class="filter-btn" onclick="filterLikes('price')">Price: Low to High</button>
      <button class="filter-btn" onclick="filterLikes('rating')">Highest Rated</button>
      <button class="filter-btn" onclick="filterLikes('newest')">Newest</button>
    </div>

    <?php if (empty($favoriteItems)): ?>
      <div class="empty-state">
        <i class="fas fa-heart"></i>
        <h3>You haven't liked anything yet</h3>
        <p>Start liking products to save them for later</p>
        <a href="/" class="checkout-btn" style="width: 200px; display: inline-block; text-decoration: none;">Continue Shopping</a>
      </div>
    <?php else: ?>
      <div class="likes-grid">
        <?php foreach ($favoriteItems as $item): ?>
          <div class="product-card">
            <div class="product-card-image">
              <img src="/images/product-placeholder.jpg" alt="<?= htmlspecialchars($item['title']) ?>">
              <button class="like-btn" title="Remove from likes" onclick="removeLike(<?= $item['id'] ?>)">
                <i class="fas fa-heart"></i>
              </button>
            </div>
            <div class="product-card-content">
              <div class="product-card-title" title="<?= htmlspecialchars($item['title']) ?>">
                <?= htmlspecialchars($item['title']) ?>
              </div>
              <div class="product-card-seller">by <?= htmlspecialchars($item['seller'] ?? 'VantageMarket') ?></div>
              <div class="product-card-rating">
                <i class="fas fa-star"></i>
                <span><?= $item['rating'] ?? '4.5' ?> (<?= $item['reviews'] ?? '0' ?> reviews)</span>
              </div>
              <div class="product-card-price">RM <?= number_format((float)$item['price'], 2) ?></div>
              <div class="product-card-actions">
                <button class="action-btn" onclick="viewProduct(<?= $item['id'] ?>)">View</button>
                <button class="action-btn add-cart" onclick="addToCart(<?= $item['id'] ?>)">Add to Cart</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <script>
    function removeLike(productId) {
      if (confirm('Remove this item from your likes?')) {
        // AJAX call to remove from favorites
        console.log('Remove like:', productId);
        window.location.reload();
      }
    }

    function addToCart(productId) {
      // AJAX call to add to cart
      alert('Added to cart');
      console.log('Add to cart:', productId);
    }

    function viewProduct(productId) {
      // Navigate to product detail page
      window.location.href = '/product/' + productId;
    }

    function filterLikes(type) {
      document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
      console.log('Filter by:', type);
    }
  </script>
</body>
</html>
