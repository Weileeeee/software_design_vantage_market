<?php declare(strict_types=1); ?>
<?php
global $userType, $userName;
$userType = $userType ?? 'Guest';
$userName = $userName ?? 'Guest User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Likes — VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    .likes-page { max-width: 1200px; margin: 0 auto; padding: 24px 20px; }
    .likes-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .likes-title { font-size: 22px; font-weight: 700; color: #333; }
    .likes-count { font-size: 13px; color: #999; margin-top: 2px; }
    .likes-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 18px; }
    .like-card { background: white; border: 1px solid #e0e0e0; border-radius: 6px; overflow: hidden; transition: box-shadow 0.2s; }
    .like-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); }
    .like-card-img { width: 100%; height: 180px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden; }
    .like-card-img img { width: 100%; height: 100%; object-fit: cover; }
    .like-card-img .placeholder-icon { font-size: 40px; color: #ddd; }
    .unlike-btn { position: absolute; top: 8px; right: 8px; background: white; border: none; color: #ff6b6b; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(0,0,0,0.15); font-size: 15px; transition: 0.2s; }
    .unlike-btn:hover { background: #ff6b6b; color: white; }
    .like-card-body { padding: 12px; }
    .like-card-title { font-weight: 600; font-size: 13px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-bottom: 2px; }
    .like-card-brand { font-size: 11px; color: #999; margin-bottom: 6px; }
    .like-card-price { color: #ff6b6b; font-weight: 700; font-size: 15px; margin-bottom: 10px; }
    .like-card-actions { display: flex; gap: 6px; }
    .btn-add-cart { flex: 1; padding: 8px; background: #ff6b6b; color: white; border: none; border-radius: 3px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-add-cart:hover { background: #ff5252; }
    .btn-add-cart:disabled { background: #ccc; cursor: not-allowed; }
    .empty-state { text-align: center; padding: 80px 20px; }
    .empty-state i { font-size: 64px; color: #ddd; margin-bottom: 20px; display: block; }
    .empty-state h3 { font-size: 20px; color: #999; margin-bottom: 10px; }
    .empty-state p { color: #bbb; margin-bottom: 24px; font-size: 14px; }
    .empty-state a { display: inline-block; padding: 12px 28px; background: #ff6b6b; color: white; text-decoration: none; border-radius: 4px; font-weight: 600; }
    .loading-state { text-align: center; padding: 60px 20px; color: #999; font-size: 15px; }
    .spinner { display: inline-block; width: 32px; height: 32px; border: 3px solid #eee; border-top-color: #ff6b6b; border-radius: 50%; animation: spin 0.7s linear infinite; margin-bottom: 12px; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .toast { position: fixed; bottom: 24px; right: 24px; background: #333; color: white; padding: 12px 20px; border-radius: 4px; font-size: 14px; z-index: 9999; opacity: 0; transition: opacity 0.3s; pointer-events: none; }
    .out-of-stock { font-size: 11px; color: #e74c3c; font-weight: 600; margin-bottom: 4px; }
  </style>
</head>
<body>

  <!-- Topbar -->
  <div class="topbar">
    <div class="container d-flex justify-between align-center">
      <div><a href="#" style="color:#aaa;text-decoration:none;margin-right:12px;">About</a><a href="#" style="color:#aaa;text-decoration:none;">Help</a></div>
      <div style="color:#aaa;font-size:12px;">Account: <?= htmlspecialchars($userName) ?></div>
    </div>
  </div>

  <!-- Mid Header -->
  <div class="mid-header">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo">VANTAGE<span class="logo-highlight">MARKET</span></a>
      <form action="/catalog" method="GET" class="search-bar" style="flex:1;max-width:500px;margin:0 20px;">
        <input type="text" name="q" placeholder="Search for products..." class="search-input">
        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
      </form>
      <div style="color:white;text-align:right;font-size:13px;">
        <div>Customer Service</div>
        <div style="font-size:16px;font-weight:700;">+012 345 6789</div>
      </div>
    </div>
  </div>

  <!-- Navbar -->
  <div class="navbar">
    <div class="container d-flex align-center justify-between">
      <div class="nav-links">
        <a href="/">Home</a>
        <a href="/catalog">Shop</a>
        <?php if ($userType === 'Guest'): ?>
          <a href="/signin">Sign In</a>
          <a href="/register">Register</a>
        <?php else: ?>
          <a href="/orders">My Orders</a>
          <a href="/logout">Sign Out</a>
        <?php endif; ?>
      </div>
      <div class="nav-icons">
        <a href="/likes" style="color:#ff6b6b;"><i class="fas fa-heart"></i></a>
        <a href="/cart" style="color:white;margin-left:16px;"><i class="fas fa-shopping-cart"></i></a>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="likes-page">
    <div class="likes-header">
      <div>
        <div class="likes-title"><i class="fas fa-heart" style="color:#ff6b6b;margin-right:8px;"></i>My Likes</div>
        <div class="likes-count" id="likes-count">Loading...</div>
      </div>
      <a href="/catalog" style="color:#ff6b6b;text-decoration:none;font-size:13px;font-weight:600;">
        <i class="fas fa-arrow-left"></i> Back to Shop
      </a>
    </div>

    <!-- Loading state -->
    <div id="loading-state" class="loading-state">
      <div class="spinner"></div>
      <div>Loading your liked items...</div>
    </div>

    <!-- Empty state (hidden by default) -->
    <div id="empty-state" class="empty-state" style="display:none;">
      <i class="fas fa-heart"></i>
      <h3>You haven't liked anything yet</h3>
      <p>Tap the ♥ heart on any product to save it here for later.</p>
      <a href="/catalog">Start Shopping</a>
    </div>

    <!-- Products grid (hidden until loaded) -->
    <div id="likes-grid" class="likes-grid" style="display:none;"></div>
  </div>

  <!-- Toast notification -->
  <div class="toast" id="toast"></div>

  <script>
    const STORAGE_KEY = 'vm_favourites';

    function showToast(msg) {
      const t = document.getElementById('toast');
      t.textContent = msg;
      t.style.opacity = '1';
      setTimeout(() => { t.style.opacity = '0'; }, 2500);
    }

    function getFavouriteIds() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : [];
      } catch (_) { return []; }
    }

    function saveFavouriteIds(ids) {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    }

    function removeFavourite(productId) {
      let ids = getFavouriteIds();
      ids = ids.filter(id => String(id) !== String(productId));
      saveFavouriteIds(ids);
      // Remove the card from the DOM
      const card = document.getElementById('like-card-' + productId);
      if (card) card.remove();
      updateCount();
      showToast('Removed from Likes');
      if (getFavouriteIds().length === 0) showEmpty();
    }

    function updateCount() {
      const count = document.querySelectorAll('.like-card').length;
      document.getElementById('likes-count').textContent =
        count + (count === 1 ? ' item' : ' items');
    }

    function showEmpty() {
      document.getElementById('likes-grid').style.display = 'none';
      document.getElementById('empty-state').style.display = 'block';
      document.getElementById('likes-count').textContent = '0 items';
    }

    function renderProduct(p) {
      const inStock = parseInt(p.stock_level) > 0;
      const imgHtml = p.image_url
        ? `<img src="${p.image_url}" alt="${p.title}" onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
           <i class="fas fa-box-open placeholder-icon" style="display:none;"></i>`
        : `<i class="fas fa-box-open placeholder-icon"></i>`;

      return `
        <div class="like-card" id="like-card-${p.product_id}">
          <div class="like-card-img">
            ${imgHtml}
            <button class="unlike-btn" title="Remove from Likes"
                    onclick="removeFavourite(${p.product_id})">
              <i class="fas fa-heart"></i>
            </button>
          </div>
          <div class="like-card-body">
            <div class="like-card-title" title="${p.title}">${p.title}</div>
            <div class="like-card-brand">${p.brand || p.category_name || 'VantageMarket'}</div>
            ${!inStock ? '<div class="out-of-stock">Out of Stock</div>' : ''}
            <div class="like-card-price">RM ${parseFloat(p.price).toFixed(2)}</div>
            <div class="like-card-actions">
              <button class="btn-add-cart" ${!inStock ? 'disabled' : ''}
                      onclick="addToCart(${p.product_id}, '${p.title.replace(/'/g, "\\'")}')">
                <i class="fas fa-cart-plus"></i> ${inStock ? 'Add to Cart' : 'Sold Out'}
              </button>
            </div>
          </div>
        </div>`;
    }

    async function addToCart(productId, title) {
      try {
        const formData = new URLSearchParams();
        formData.append('product_id', productId);
        formData.append('quantity', '1');
        const res  = await fetch('/cart/add', { method: 'POST', body: formData });
        const data = await res.json();
        showToast(data.success ? `✓ ${title} added to cart` : (data.message || 'Could not add to cart'));
      } catch (_) {
        showToast('Could not connect. Please try again.');
      }
    }

    async function loadLikes() {
      const ids = getFavouriteIds();
      const loadingEl = document.getElementById('loading-state');
      const emptyEl   = document.getElementById('empty-state');
      const gridEl    = document.getElementById('likes-grid');

      if (ids.length === 0) {
        loadingEl.style.display = 'none';
        showEmpty();
        return;
      }

      try {
        const res  = await fetch('/api/products-by-ids?ids=' + ids.join(','));
        const data = await res.json();
        loadingEl.style.display = 'none';

        if (!data || data.length === 0) {
          // IDs exist in localStorage but no matching products in DB
          saveFavouriteIds([]); // clear stale IDs
          showEmpty();
          return;
        }

        // Render cards in the same order as localStorage (most-recently-liked first)
        const productMap = {};
        data.forEach(p => { productMap[String(p.product_id)] = p; });

        // Reverse so newest liked shows first
        const orderedIds = [...ids].reverse();
        let html = '';
        orderedIds.forEach(id => {
          const p = productMap[String(id)];
          if (p) html += renderProduct(p);
        });

        gridEl.innerHTML = html;
        gridEl.style.display = 'grid';
        document.getElementById('likes-count').textContent =
          data.length + (data.length === 1 ? ' item' : ' items');
      } catch (e) {
        loadingEl.innerHTML = '<div style="color:#e74c3c;">Failed to load items. <a href="javascript:location.reload()">Try again</a></div>';
      }
    }

    // Run on page load
    loadLikes();
  </script>

</body>
</html>
