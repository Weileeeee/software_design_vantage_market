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
          <a href="#" style="color:var(--primary)">Home</a>
          <a href="#">Shop</a>
          <a href="#">Shop Detail</a>
          <?php if ($userType === 'Guest'): ?>
            <a href="/login">Sign In</a>
            <a href="/register">Register</a>
          <?php else: ?>
            <a href="/dashboard">Dashboard</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="nav-icons">
        <a href="/#cart" class="nav-icon">
          <i class="fas fa-heart"></i>
          <span class="nav-icon-badge">0</span>
        </a>
        <a href="#" class="nav-icon" class="nav-icon">
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

    <!-- Main Content & Sidebar -->
    <div class="sidebar-layout">
      <!-- Left: Products -->
      <div>
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

      <!-- Right: Sidebar -->
      <div>
        <!-- Cart Panel -->
        <div class="panel" id="cart">
          <h3 class="panel-title">My Cart</h3>
          <?php if (empty($cartItems)): ?>
            <p class="text-muted" style="text-align: center; margin: 30px 0;">Cart is empty.</p>
          <?php else: ?>
            <div class="cart-list">
              <?php foreach ($cartItems as $ci): ?>
                <div class="cart-item">
                  <div class="cart-info">
                    <h6><?= htmlspecialchars($ci['title']) ?></h6>
                    <small>RM <?= number_format((float)$ci['price'], 2) ?> x <?= $ci['quantity'] ?></small>
                  </div>
                  <div class="cart-actions">
                    <form method="POST" action="/cart/remove">
                      <input type="hidden" name="product_id" value="<?= $ci['product_id'] ?>">
                      <button type="submit" class="btn-remove"><i class="fas fa-times"></i></button>
                    </form>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <div style="border-top: 1px solid var(--border-color); margin-top: 20px; padding-top: 15px; display:flex; justify-content:space-between; font-weight:700;">
              <span>Total</span>
              <span class="text-dark">RM <?= number_format($cartTotal, 2) ?></span>
            </div>
            <?php if (!empty($cartItems)): ?>
              <form method="POST" action="/orders/place" style="margin-top: 15px;">
                <button type="submit" style="width:100%; background:var(--primary); border:none; padding:12px; font-weight:700; font-size:15px; cursor:pointer; border-radius:2px;">
                  <i class="fas fa-credit-card" style="margin-right:8px;"></i>Checkout
                </button>
              </form>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <!-- Observer Panel -->
        <div class="panel">
          <h3 class="panel-title">Observer Subsystem</h3>
          <div class="obs-badge">
            <i class="fas fa-eye"></i> Active Observers: <?= count($activeObservers) ?>
          </div>
          <div class="obs-list">
            <?php if (empty($activeObservers)): ?>
              <p class="text-muted">Add an item to the cart to attach an observer.</p>
            <?php else: ?>
              <?php foreach ($activeObservers as $obs): ?>
                <div class="obs-item">
                  <strong>ID <?= $obs['product_id'] ?></strong> watching by Cart #<?= $obs['cart_id'] ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Simulator Panel -->
        <div class="panel">
          <h3 class="panel-title">Stock Simulator</h3>
          <p style="font-size: 13px; margin-bottom: 15px; color: var(--text-muted);">
            Test the Observer Pattern by forcing a product's stock to 0. Watch it disappear from the cart!
          </p>
          <div class="sim-grid">
            <?php foreach ($products as $p): ?>
              <form method="POST" action="/product/update-stock" class="sim-row">
                <input type="hidden" name="product_id" value="<?= $p->productId ?>">
                <div class="sim-title" title="<?= htmlspecialchars($p->title) ?>"><?= htmlspecialchars($p->title) ?></div>
                <input type="number" name="stock_level" value="<?= $p->stockLevel ?>" min="0" class="sim-input" required>
                <div>
                  <button type="submit" class="btn-sim">Set</button>
                  <?php if ($p->stockLevel > 0): ?>
                    <button type="button" class="btn-sim-zero" onclick="this.form.stock_level.value=0; this.form.submit();">0</button>
                  <?php endif; ?>
                </div>
              </form>
            <?php endforeach; ?>
          </div>
        </div>

      </div>
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
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Home</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Our Shop</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Shop Detail</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Checkout</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Contact Us</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-title">My Account</h4>
          <ul class="footer-links">
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Home</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Our Shop</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Shop Detail</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Checkout</a></li>
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
</body>
</html>
