<?php
// =============================================================
// VantageMarket — Product Catalog & Filters
// Bright E-Commerce Layout
// =============================================================
declare(strict_types=1);

global $cartItems, $userType, $userName;
$cartTotal = array_reduce($cartItems ?? [], fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0.0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Catalog - VantageMarket</title>
  <link rel="stylesheet" href="/css/homepage.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    /* Additional styles for the Catalog page */
    .filter-panel {
      background: var(--light);
      padding: 25px;
      box-shadow: 0 0 15px rgba(0,0,0,0.03);
      margin-bottom: 30px;
    }
    .filter-title {
      font-size: 16px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 20px;
      border-bottom: 1px solid var(--border-color);
      padding-bottom: 10px;
    }
    .filter-section {
      margin-bottom: 25px;
    }
    .filter-label {
      font-size: 14px;
      font-weight: 700;
      color: var(--text-dark);
      margin-bottom: 10px;
      display: block;
    }
    .form-control {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid var(--border-color);
      border-radius: 2px;
      font-family: inherit;
      color: var(--text-dark);
    }
    .checkbox-group {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .checkbox-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: var(--text-muted);
      cursor: pointer;
    }
    .checkbox-item input {
      accent-color: var(--primary);
      width: 16px;
      height: 16px;
    }
    .range-slider {
      width: 100%;
      accent-color: var(--primary);
    }
    .btn-apply {
      width: 100%;
      background: var(--primary);
      color: var(--dark);
      border: none;
      padding: 10px;
      font-weight: 700;
      border-radius: 2px;
      cursor: pointer;
      transition: 0.2s ease;
    }
    .btn-apply:hover {
      background: var(--primary-dark);
    }
    .catalog-layout {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 30px;
      margin-top: 40px;
    }
    @media (max-width: 991px) {
      .catalog-layout { grid-template-columns: 1fr; }
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

  <!-- Middle Header -->
  <div class="mid-header">
    <div class="container d-flex align-center justify-between">
      <a href="/" class="logo">
        VANTAGE<span class="logo-highlight">MARKET</span>
      </a>
      <form action="/catalog" method="GET" class="search-bar">
        <input type="text" name="search" placeholder="Search for products..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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
          <?php
            $db_for_nav = \VantageMarket\Config\Database::getInstance();
            $navCategories = $db_for_nav->query("
              SELECT c.*, COUNT(p.product_id) as product_count
              FROM Categories c
              LEFT JOIN Products p ON c.category_id = p.category_id
              GROUP BY c.category_id
            ")->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <div class="nav-categories-dropdown" id="navCategoriesDropdown">
            <?php foreach ($navCategories as $cat): ?>
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
          <a href="/">Home</a>
          <a href="/catalog" style="color:var(--primary)">Shop</a>
          <a href="#">Shop Detail</a>
          <?php if (($userType ?? 'Guest') === 'Guest'): ?>
            <a href="/login">Sign In</a>
            <a href="/register">Register</a>
          <?php else: ?>
            <a href="/dashboard">Dashboard</a>
          <?php endif; ?>
        </div>
      </div>
      <div class="nav-icons">
        <a href="#" class="nav-icon">
          <i class="fas fa-heart"></i>
          <span class="nav-icon-badge">0</span>
        </a>
        <a href="/" class="nav-icon">
          <i class="fas fa-shopping-cart"></i>
          <span class="nav-icon-badge"><?= count($cartItems ?? []) ?></span>
        </a>
      </div>
    </div>
  </div>

  <div class="container">
    <div class="catalog-layout">
      
      <!-- Sidebar Filters -->
      <aside>
        <div class="filter-panel">
          <div class="d-flex justify-content-between align-center" style="margin-bottom: 20px;">
            <h3 class="filter-title" style="margin: 0; border: none; padding: 0;">Filter By</h3>
            <a href="/catalog" style="font-size: 13px; color: var(--primary);">Clear All</a>
          </div>

          <form action="/catalog" method="GET">
            <!-- Hidden search retention -->
            <?php if (!empty($_GET['search'])): ?>
              <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search']) ?>">
            <?php endif; ?>

            <!-- Price Filter -->
            <div class="filter-section">
              <?php $current_max = htmlspecialchars($_GET['max_price'] ?? '500'); ?>
              <div class="d-flex justify-content-between align-center mb-2">
                <span class="filter-label" style="margin:0;">Max Price</span>
                <span style="font-weight: 700; color: var(--text-dark);">RM <span id="priceValue"><?= $current_max ?></span></span>
              </div>
              <input type="range" class="range-slider" min="0" max="1000" step="10" name="max_price" value="<?= $current_max ?>" oninput="document.getElementById('priceValue').innerText = this.value">
            </div>

            <!-- Categories Filter -->
            <div class="filter-section">
              <span class="filter-label">Categories</span>
              <div class="checkbox-group">
                <label class="checkbox-item">
                  <input type="checkbox" name="category[]" value="1" <?= in_array('1', $_GET['category'] ?? []) ? 'checked' : '' ?>>
                  Audio & Earbuds
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="category[]" value="2" <?= in_array('2', $_GET['category'] ?? []) ? 'checked' : '' ?>>
                  Computer Peripherals
                </label>
                <label class="checkbox-item">
                  <input type="checkbox" name="category[]" value="3" <?= in_array('3', $_GET['category'] ?? []) ? 'checked' : '' ?>>
                  Desk Accessories
                </label>
              </div>
            </div>

            <button type="submit" class="btn-apply">Apply Filters</button>
          </form>
        </div>
      </aside>

      <!-- Main Catalog Grid -->
      <main>
        <div class="d-flex justify-content-between align-center mb-2">
          <h2 class="section-title" style="margin:0; border:none; flex:none;">Shop Products</h2>
          <span class="text-muted">Showing <?= count($products) ?> results</span>
        </div>

        <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
          <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
              <?php 
                $stockClass = 'stock-ok'; $stockText = 'In Stock';
                if ($p['stock_level'] == 0) { $stockClass = 'stock-out'; $stockText = 'Out of Stock'; }
                elseif ($p['stock_level'] <= 10) { $stockClass = 'stock-low'; $stockText = 'Low Stock'; }
                
                $inCart = false;
                foreach (($cartItems ?? []) as $ci) {
                    if ($ci['product_id'] == $p['product_id']) { $inCart = true; break; }
                }
              ?>
              <div class="product-card">
                <div class="product-img">
                  <i class="fas fa-box-open"></i>
                </div>
                <div class="product-body">
                  <h6 class="product-title"><?= htmlspecialchars($p['title']) ?></h6>
                  <h5 class="product-price">RM <?= number_format((float)$p['price'], 2) ?></h5>
                  <span class="product-stock <?= $stockClass ?>"><?= $stockText ?> (<?= $p['stock_level'] ?>)</span>
                  
                  <form method="POST" action="/cart/add" style="margin-top: 15px;">
                    <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                    <button type="submit" class="btn-add" <?= $p['stock_level'] == 0 ? 'disabled' : '' ?>>
                      <?php if ($inCart): ?>
                        <i class="fas fa-plus"></i> Add Another
                      <?php else: ?>
                        <i class="fas fa-shopping-cart text-primary mr-1"></i> Add To Cart
                      <?php endif; ?>
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: var(--light); border: 1px solid var(--border-color);">
              <i class="fas fa-search" style="font-size: 48px; color: var(--border-color); margin-bottom: 20px;"></i>
              <h4 class="text-dark">No Products Found</h4>
              <p class="text-muted">Try adjusting your filters or search keywords.</p>
              <a href="/catalog" class="btn-apply" style="display:inline-block; width:auto; padding: 8px 20px; margin-top: 10px;">Reset Filters</a>
            </div>
          <?php endif; ?>
        </div>
      </main>

    </div>
  </div>

  <!-- Footer -->
  <div class="footer">
    <div class="container">
      <div class="footer-grid">
        <div>
          <h4 class="footer-title">Get In Touch</h4>
          <p class="footer-text">No dolore ipsum accusam no lorem. Invidunt sed clita kasd clita et et dolor sed dolor.</p>
          <p class="footer-text"><i class="fa fa-map-marker-alt text-primary mr-3"></i> 123 Street, New York, USA</p>
          <p class="footer-text"><i class="fa fa-envelope text-primary mr-3"></i> info@example.com</p>
          <p class="footer-text"><i class="fa fa-phone-alt text-primary mr-3"></i> +012 345 67890</p>
        </div>
        <div>
          <h4 class="footer-title">Quick Shop</h4>
          <ul class="footer-links">
            <li><a href="/"><i class="fa fa-angle-right mr-2"></i>Home</a></li>
            <li><a href="/catalog"><i class="fa fa-angle-right mr-2"></i>Our Shop</a></li>
            <li><a href="#"><i class="fa fa-angle-right mr-2"></i>Shop Detail</a></li>
          </ul>
        </div>
        <div>
          <h4 class="footer-title">My Account</h4>
          <ul class="footer-links">
            <li><a href="/login"><i class="fa fa-angle-right mr-2"></i>Sign In</a></li>
            <li><a href="/register"><i class="fa fa-angle-right mr-2"></i>Register</a></li>
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
</body>
</html>

<script>
  function toggleCategoryDropdown() {
    const dropdown = document.getElementById('navCategoriesDropdown');
    const arrow = document.getElementById('navCategoriesArrow');
    const isOpen = dropdown.classList.toggle('open');
    arrow.style.transform = isOpen ? 'rotate(180deg)' : '';
  }
  document.addEventListener('click', function(e) {
    const btn = document.getElementById('navCategoriesBtn');
    const dropdown = document.getElementById('navCategoriesDropdown');
    if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.remove('open');
      document.getElementById('navCategoriesArrow').style.transform = '';
    }
  });
</script>
