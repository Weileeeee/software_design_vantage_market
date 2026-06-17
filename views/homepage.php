<?php
// =============================================================
// VantageMarket — Bright Interactive Guest Homepage
// Demonstrates the Observer Pattern in real-time
// =============================================================
declare(strict_types=1);

/** @var \VantageMarket\Models\Product[] $products */
/** @var array $cartItems */
/** @var array $activeObservers */
/** @var \VantageMarket\Models\Cart $cart */
/** @var string $userType */
/** @var string $userName */

$cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0.0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
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
        <?php if ($userType === 'Guest'): ?>
          <span class="text-muted">Browsing as: Guest</span>
          <span class="badge">Guest Mode</span>
        <?php else: ?>
          <span class="text-muted">Account: <?= htmlspecialchars($userName) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Middle Header -->
  <div class="mid-header">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo">
        VANTAGE<span class="logo-highlight">MARKET</span>
      </a>
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
        <div class="nav-cat-wrapper">
          <div class="nav-categories" id="navCategoriesBtn" onclick="toggleCategoryDropdown()">
            <span><i class="fa fa-bars" style="margin-right:10px;"></i> Categories</span>
            <i class="fa fa-angle-down" id="navCategoriesArrow"></i>
          </div>
          <!-- Category Dropdown -->
          <div class="nav-categories-dropdown" id="navCategoriesDropdown">
            <?php foreach ($categories as $cat): ?>
              <a href="/catalog?category[]=<?= $cat['category_id'] ?>" class="nav-cat-link">
                <i class="fas fa-tag" style="margin-right:8px; color:var(--primary);"></i>
                <?= htmlspecialchars($cat['category_name']) ?>
                <span class="nav-cat-count"><?= $cat['product_count'] ?></span>
              </a>
            <?php endforeach; ?>
            <a href="/catalog" class="nav-cat-link nav-cat-all">
              <i class="fas fa-th" style="margin-right:8px;"></i> All Products
            </a>
          </div>
        </div>
        <div class="nav-links">
          <a href="/" style="color:var(--primary)">Home</a>
          <a href="/catalog">Shop</a>
          <?php if ($userType === 'Guest'): ?>
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
        <a href="/cart" class="nav-icon" title="View Cart">
          <i class="fas fa-shopping-cart"></i>
          <span class="nav-icon-badge"><?= count($cartItems) ?></span>
        </a>
      </div>
    </div>
  </div>

  <div class="container">
    <!-- Hero Banner -->
    <div class="hero-wrapper">
      <div class="hero-banner">
        <div class="hero-content">
          <div class="hero-subtitle">Observer Pattern Demo</div>
          <h1 class="hero-title">VantageMarket Live Simulation</h1>
          <p style="margin-bottom: 25px; font-size: 16px; line-height: 1.6;">
            Add items to your cart to see them register as observers. Use the simulator below to drop stock to 0 and witness the pattern in action!
          </p>
          <a href="#products" class="hero-btn">Shop Now</a>
        </div>
      </div>
    </div>

    <!-- Features Bar -->
    <div class="features-grid">
      <div class="feature-box">
        <i class="fa fa-check feature-icon"></i>
        <h5 class="feature-title">Quality Product</h5>
      </div>
      <div class="feature-box">
        <i class="fa fa-shipping-fast feature-icon"></i>
        <h5 class="feature-title">Free Shipping</h5>
      </div>
      <div class="feature-box">
        <i class="fas fa-exchange-alt feature-icon"></i>
        <h5 class="feature-title">14-Day Return</h5>
      </div>
      <div class="feature-box">
        <i class="fa fa-phone-volume feature-icon"></i>
        <h5 class="feature-title">24/7 Support</h5>
      </div>
    </div>


    <!-- Featured Products -->
    <div style="margin: 40px 0;">
      <h2 class="section-title" id="products">Featured Products</h2>
      <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
        <?php foreach (array_slice($products, 0, 8) as $p): ?>
          <?php
            $cartQty = 0;
            foreach ($cartItems as $ci) {
                if ($ci['product_id'] == $p->productId) { 
                    $cartQty = (int)$ci['quantity'];
                    break; 
                }
            }
            $inCart = $cartQty > 0;
            $maxReached = $p->stockLevel > 0 && $cartQty >= $p->stockLevel;

            $stockClass = 'stock-in'; $stockText = 'In Stock';
            if ($p->stockLevel == 0) { $stockClass = 'stock-out'; $stockText = 'Out of Stock'; }
            elseif ($p->stockLevel <= 10) { $stockClass = 'stock-low'; $stockText = 'Low Stock'; }
          ?>
          <div class="product-card" style="position: relative;">
            <button
              type="button"
              class="btn-favourite-hp"
              onclick="toggleFavourite(this, <?= $p->productId ?>, <?= htmlspecialchars(json_encode($p->title)) ?>)"
              data-product-id="<?= $p->productId ?>"
              title="Add to Favourites"
              style="position:absolute;top:10px;right:10px;border:1px solid #eee;background:white;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#ccc;font-size:15px;transition:0.2s;"
            ><i class="fas fa-heart"></i></button>
            <div class="product-img" style="background:#f8f9fa; display:flex; align-items:center; justify-content:center; height:160px; overflow:hidden;">
              <?php if (!empty($p->imageUrl)): ?>
                <img src="<?= htmlspecialchars($p->imageUrl) ?>" alt="<?= htmlspecialchars($p->title) ?>"
                     style="width:100%;height:100%;object-fit:cover;"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block';">
                <i class="fas fa-box-open" style="font-size:40px; color:#ccc; display:none;"></i>
              <?php else: ?>
                <i class="fas fa-box-open" style="font-size:40px; color:#ccc;"></i>
              <?php endif; ?>
            </div>
            <div class="product-body">
              <h6 class="product-title"><?= htmlspecialchars($p->title) ?></h6>
              <h5 class="product-price">RM <?= number_format((float)$p->price, 2) ?></h5>
              <span class="product-stock <?= $stockClass ?>"><?= $stockText ?></span>
              <div style="display:flex;flex-direction:column;gap:8px;margin-top:12px;">
                <form method="POST" action="/cart/add">
                  <input type="hidden" name="product_id" value="<?= $p->productId ?>">
                  <button type="submit" class="btn-add" style="width:100%;" <?= ($p->stockLevel == 0 || $maxReached) ? 'disabled' : '' ?>>
                    <?php if ($maxReached): ?>
                      <i class="fas fa-ban"></i> Max In Cart
                    <?php elseif ($inCart): ?>
                      <i class="fas fa-plus"></i> Add Another
                    <?php else: ?>
                      <i class="fas fa-shopping-cart"></i> Add To Cart
                    <?php endif; ?>
                  </button>
                </form>
                <?php if ($p->stockLevel > 0): ?>
                <form method="POST" action="/cart/buy-now">
                  <input type="hidden" name="product_id" value="<?= $p->productId ?>">
                  <button type="submit" class="btn-add" style="width:100%;background:#ff6b6b;color:white;border-color:#ff6b6b;">
                    <i class="fas fa-bolt"></i> Buy Now
                  </button>
                </form>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div style="text-align:center; margin-top:20px;">
        <a href="/catalog" class="hero-btn" style="display:inline-block;">View All Products</a>
      </div>
    </div>

    <!-- Categories -->
    <h2 class="section-title" id="categories">Product Categories</h2>
    <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
      <?php foreach ($categories as $cat): ?>
        <?php 
          // Assign a random-looking icon based on category_id for visual flair
          $icons = ['fa-headphones', 'fa-keyboard', 'fa-mouse', 'fa-laptop', 'fa-desktop'];
          $icon = $icons[$cat['category_id'] % count($icons)];
        ?>
        <div class="product-card" style="cursor: pointer; text-align: left; display: flex; align-items: center; padding: 20px; gap: 20px;" onclick="window.location.href='/catalog?category[]=<?= $cat['category_id'] ?>'">
          <div style="width: 60px; height: 60px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 4px;">
            <i class="fas <?= $icon ?>" style="font-size: 24px; color: var(--primary-dark);"></i>
          </div>
          <div>
            <h6 class="product-title" style="margin-bottom: 5px; color: var(--dark); font-weight: 700;"><?= htmlspecialchars($cat['category_name']) ?></h6>
            <small class="text-muted" style="font-size: 13px;"><?= $cat['product_count'] ?> Products</small>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <h4 class="footer-title">Get In Touch</h4>
          <p class="footer-text">No dolore ipsum accusam no lorem. Invidunt sed clita kasd clita et et dolor sed dolor. Rebum tempor no vero est magna amet no</p>
          <p class="footer-text"><i class="fa fa-map-marker-alt text-primary mr-3"></i> 123 Street, New York, USA</p>
          <p class="footer-text"><i class="fa fa-envelope text-primary mr-3"></i> info@example.com</p>
          <p class="footer-text"><i class="fa fa-phone-alt text-primary mr-3"></i> +012 345 67890</p>
        </div>
        <div>
          <h4 class="footer-title">Quick Shop</h4>
          <ul class="footer-links">
            <li><a href="/"><i class="fa fa-angle-right mr-2"></i>Home</a></li>
            <li><a href="/"><i class="fa fa-angle-right mr-2"></i>Our Shop</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Shop Detail</a></li>
            <li><a href="/checkout"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a></li>
            <li><a href="/checkout"><i class="fa fa-angle-right mr-2"></i>Checkout</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Contact Us</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-title">My Account</h4>
          <ul class="footer-links">
            <li><a href="/"><i class="fa fa-angle-right mr-2"></i>Home</a></li>
            <li><a href="/"><i class="fa fa-angle-right mr-2"></i>Our Shop</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Shop Detail</a></li>
            <li><a href="/checkout"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a></li>
            <li><a href="/checkout"><i class="fa fa-angle-right mr-2"></i>Checkout</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Contact Us</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-title">Newsletter</h4>
          <p class="footer-text">Duo stet clita ea ipsum labore et elitr.</p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; VantageMarket. All Rights Reserved. Designed for CSE6234.</p>
      </div>
    </div>
  </div>

  <?php if (isset($_GET['action'])): ?>
    <script>
      // Clean URL after action
      window.history.replaceState({}, document.title, '/');
    </script>
  <?php endif; ?>

  <script>
    function toggleCategoryDropdown() {
      const dropdown = document.getElementById('navCategoriesDropdown');
      const arrow = document.getElementById('navCategoriesArrow');
      const isOpen = dropdown.classList.toggle('open');
      arrow.style.transform = isOpen ? 'rotate(180deg)' : '';
    }
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      const btn = document.getElementById('navCategoriesBtn');
      const dropdown = document.getElementById('navCategoriesDropdown');
      if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('open');
        document.getElementById('navCategoriesArrow').style.transform = '';
      }
    });
  </script>
  <style>
    .btn-favourite-hp:hover, .btn-favourite-hp.active {
      color: #ff6b6b !important;
      border-color: #ff6b6b !important;
    }
    .product-stock { display: inline-block; font-size: 12px; padding: 2px 8px; border-radius: 2px; margin-top: 5px; }
    .stock-in  { background: #e8f5e9; color: #388e3c; }
    .stock-low { background: #fff3e0; color: #f57c00; }
    .stock-out { background: #fce4ec; color: #c62828; }
  </style>

  <script>
    // ── Shared Favourites (localStorage) ─────────────────────
    const FAVES_KEY = 'vm_favourites';
    function getFavourites() {
      try { return JSON.parse(localStorage.getItem(FAVES_KEY)) || []; } catch (_) { return []; }
    }
    function saveFavourites(faves) { localStorage.setItem(FAVES_KEY, JSON.stringify(faves)); }

    function toggleFavourite(btn, productId, title) {
      let faves = getFavourites();
      const idx = faves.findIndex(f => f.id === productId);
      if (idx === -1) {
        faves.push({ id: productId, title });
        btn.classList.add('active');
        showFaveToast('❤️ Added to Favourites: ' + title);
      } else {
        faves.splice(idx, 1);
        btn.classList.remove('active');
        showFaveToast('🤍 Removed from Favourites: ' + title);
      }
      saveFavourites(faves);
    }

    function showFaveToast(msg) {
      let toast = document.getElementById('fave-toast');
      if (!toast) {
        toast = document.createElement('div');
        toast.id = 'fave-toast';
        toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#333;color:#fff;padding:12px 20px;border-radius:4px;font-size:14px;z-index:9999;opacity:0;transition:opacity 0.3s;pointer-events:none;max-width:280px;';
        document.body.appendChild(toast);
      }
      toast.textContent = msg;
      toast.style.opacity = '1';
      clearTimeout(toast._t);
      toast._t = setTimeout(() => { toast.style.opacity = '0'; }, 2500);
    }

    // Restore active state on load
    document.addEventListener('DOMContentLoaded', () => {
      const faves = getFavourites();
      document.querySelectorAll('[data-product-id]').forEach(btn => {
        if (faves.some(f => f.id === parseInt(btn.dataset.productId))) {
          btn.classList.add('active');
        }
      });
    });
  </script>

</body>
</html>
