<?php
// =============================================================
// VantageMarket — Public Router (public/index.php)
// Entry point for all HTTP requests
// Put this file inside your web server's document root
// =============================================================

declare(strict_types=1);

// Buffer all output so PHP errors don't corrupt JSON API responses
ob_start();

use VantageMarket\Controllers\CheckoutController;

$container = require_once __DIR__ . '/../bootstrap.php';
$auth = $container['auth'];
$middleware = $container['middleware'];

// Force HTTPS in production (UC04: secure transmission)
if (
    ($_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development') === 'production' &&
    (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off')
) {
    $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: {$redirect}", true, 301);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path   = rtrim($path, '/') ?: '/';

/** @var \VantageMarket\Controllers\AuthController $auth */
/** @var \VantageMarket\Middleware\AuthMiddleware  $middleware */

// ----------------------------------------------------------
// Route table
// ----------------------------------------------------------
match (true) {

    // ----------------------------------------------------------
    // Homepage & Observer Interactive Demo
    // ----------------------------------------------------------
    $path === '/' && $method === 'GET'
        => (function () use ($container): void {
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            
            $productRepo = $container['productRepository'];
            $cartRepo = $container['cartRepository'];
            
            // Get or create guest cart if not logged in
            if ($session->isAuthenticated()) {
                $cart = $cartRepo->findOrCreateForUser($session->currentUserId());
                $userType = 'User';
                $userName = $_SESSION['user_name'];
            } else {
                $cart = $cartRepo->findOrCreateForSession(session_id());
                $userType = 'Guest';
                $userName = 'Guest User';
            }
            
            $products = $productRepo->findAllActive();
            $cartItems = $cartRepo->getItems($cart->cartId);
            
            // Fetch categories for the homepage
            $db = \VantageMarket\Config\Database::getInstance();
            $categories = $db->query("
                SELECT c.*, COUNT(p.product_id) as product_count 
                FROM Categories c 
                LEFT JOIN Products p ON c.category_id = p.category_id 
                GROUP BY c.category_id
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Fetch observer subsystem data (simulate query to Stock_Observers)
            $stmt = $db->query('
                SELECT so.*, p.title as product_title, sc.session_id, sc.user_id, u.first_name, u.last_name
                FROM Stock_Observers so
                JOIN Products p ON p.product_id = so.product_id
                JOIN Shopping_Carts sc ON sc.cart_id = so.cart_id
                LEFT JOIN Users u ON u.user_id = sc.user_id
            ');
            $activeObservers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            include __DIR__ . '/../views/homepage.php';
        })(),

    $path === '/cart/add' && $method === 'POST'
        => (function () use ($container): void {
            $productId = (int) ($_POST['product_id'] ?? 0);
            if (!$productId) { header('Location: /'); exit; }
            
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            
            $cartRepo = $container['cartRepository'];
            $cart = $session->isAuthenticated() 
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());
            
            // Add item to cart
            $cartRepo->addItem($cart->cartId, $productId, 1);
            
            // -------------------------------------------------------
            // OBSERVER PATTERN IN ACTION: Attach cart as observer
            // -------------------------------------------------------
            /** @var \VantageMarket\Services\ProductStockSubject $stockSubject */
            $stockSubject = $container['stockSubject'];
            $stockSubject->attach($productId, $cart->cartId);
            
            header('Location: /?action=added');
        })(),

    $path === '/cart/buy-now' && $method === 'POST'
        => (function () use ($container): void {
            $productId = (int) ($_POST['product_id'] ?? 0);
            if (!$productId) { header('Location: /'); exit; }

            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();

            $cartRepo = $container['cartRepository'];
            $cart = $session->isAuthenticated()
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());

            // Add item then go straight to checkout
            $cartRepo->addItem($cart->cartId, $productId, 1);

            /** @var \VantageMarket\Services\ProductStockSubject $stockSubject */
            $stockSubject = $container['stockSubject'];
            $stockSubject->attach($productId, $cart->cartId);

            header('Location: /checkout');
            exit;
        })(),

    $path === '/cart/remove' && $method === 'POST'
        => (function () use ($container): void {
            $productId = (int) ($_POST['product_id'] ?? 0);
            if (!$productId) { header('Location: /'); exit; }
            
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            
            $cartRepo = $container['cartRepository'];
            $cart = $session->isAuthenticated() 
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());
            
            // Remove item from cart
            $cartRepo->removeItem($cart->cartId, $productId);
            
            // -------------------------------------------------------
            // OBSERVER PATTERN IN ACTION: Detach cart observer
            // -------------------------------------------------------
            /** @var \VantageMarket\Services\ProductStockSubject $stockSubject */
            $stockSubject = $container['stockSubject'];
            $stockSubject->detach($productId, $cart->cartId);
            
            header('Location: /cart?action=removed');
        })(),

    $path === '/cart' && $method === 'GET'
        => (function () use ($container): void {
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            $cartRepo = $container['cartRepository'];
            
            $cart = $session->isAuthenticated() 
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());
            $cartItems = $cartRepo->getItems($cart->cartId);
            
            $userType = $session->isAuthenticated() ? 'User' : 'Guest';
            $userName = $_SESSION['user_name'] ?? 'Guest User';
            
            include __DIR__ . '/../views/cart.php';
        })(),

    $path === '/checkout/selected' && $method === 'POST'
        => (function () use ($container): void {
            // Filter cart to selected products, store in session, then hand off to CheckoutController
            $session = $container['session'];
            $session->start();
            $cartRepo = $container['cartRepository'];

            $cart = $session->isAuthenticated()
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());

            // Save quantity modifications from the cart page back to the database
            $selectedIds = array_map('intval', $_POST['selected_products'] ?? []);
            $quantities  = $_POST['quantities'] ?? [];

            foreach ($selectedIds as $pid) {
                if (isset($quantities[$pid])) {
                    $newQty = (int)$quantities[$pid];
                    if ($newQty > 0) {
                        $cartRepo->updateItemQuantity($cart->cartId, $pid, $newQty);
                    }
                }
            }

            // Now fetch the updated items from DB
            $allItems = $cartRepo->getItems($cart->cartId);
            $filtered = array_values(array_filter(
                $allItems,
                fn($item) => in_array((int)$item['product_id'], $selectedIds, true)
            ));

            if (empty($filtered)) {
                header('Location: /cart?action=noselect');
                exit;
            }

            // Persist selected items so CheckoutController can use them
            $_SESSION['vm_checkout_items'] = $filtered;

            // Carry the promo code forward (if one was applied on the cart page)
            $promoCode = trim($_POST['promo_code'] ?? '');
            $redirectUrl = '/checkout' . ($promoCode !== '' ? '?promo_code=' . urlencode($promoCode) : '');
            header("Location: $redirectUrl");
            exit;
        })(),

    $path === '/checkout' && $method === 'GET'
        => (function () use ($container, $middleware): void {
            $middleware->requireAuth('/checkout');
            $db = \VantageMarket\Config\Database::getInstance();
            $controller = new CheckoutController($db, $container['session'], $container['cartRepository']);
            $controller->index();
        })(),

    $path === '/checkout/process' && $method === 'POST'
        => (function () use ($container, $middleware): void {
            $middleware->requireAuth('/checkout');
            $db = \VantageMarket\Config\Database::getInstance();
            $controller = new CheckoutController($db, $container['session'], $container['cartRepository']);
            $controller->processCheckout();
        })(),

    $path === '/product/update-stock' && $method === 'POST'
        => (function () use ($container): void {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $newStock = (int) ($_POST['stock_level'] ?? 0);
            if (!$productId) { header('Location: /'); exit; }
            
            $productRepo = $container['productRepository'];
            $productRepo->updateStock($productId, $newStock);
            
            // -------------------------------------------------------
            // OBSERVER PATTERN IN ACTION: Trigger stock notification
            // -------------------------------------------------------
            /** @var \VantageMarket\Services\ProductStockSubject $stockSubject */
            $stockSubject = $container['stockSubject'];
            $stockSubject->notify($productId, $newStock);
            
            if ($newStock === 0) {
                header('Location: /?action=stock_out');
            } else {
                header('Location: /?action=stock_updated');
            }
        })(),

    // ==========================================================
    // Product Catalog & Filters
    // ==========================================================
    $path === '/catalog' && $method === 'GET'
        => (function () use ($container): void {
            // Get session info for the header
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            $cartRepo = $container['cartRepository'];
            
            // Get or create guest cart if not logged in
            global $cartItems, $userType, $userName;
            if ($session->isAuthenticated()) {
                $cart = $cartRepo->findOrCreateForUser($session->currentUserId());
                $userType = 'User';
                $userName = $_SESSION['user_name'];
            } else {
                $cart = $cartRepo->findOrCreateForSession(session_id());
                $userType = 'Guest';
                $userName = 'Guest User';
            }
            $cartItems = $cartRepo->getItems($cart->cartId);

            // Fetch DB instance and call CatalogController
            $db = \VantageMarket\Config\Database::getInstance();
            $controller = new \VantageMarket\Controllers\CatalogController($db);
            $controller->index();
        })(),

    // ==========================================================
    // My Likes / Favorites Page
    // ==========================================================
    $path === '/likes' && $method === 'GET'
        => (function () use ($container): void {
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            
            $userType = $session->isAuthenticated() ? 'User' : 'Guest';
            $userName = $_SESSION['user_name'] ?? 'Guest User';
            
            // TODO: Fetch favorite items from database
            // For now, using sample data
            $favoriteItems = [
                [
                    'id' => 1,
                    'title' => 'Sample Favorite Product',
                    'seller' => 'VantageMarket',
                    'price' => 29.99,
                    'rating' => 4.5,
                    'reviews' => 125
                ]
            ];
            
            include __DIR__ . '/../views/likes.php';
        })(),

    // Auth routes (guest-only pages redirect away if already logged in)
    $path === '/register'        && $method === 'GET'
        => (function () use ($middleware): void {
            $middleware->redirectIfAuthenticated();
            // Render your React frontend or a PHP template here
            include __DIR__ . '/../views/register.php';
        })(),

    $path === '/register'        && $method === 'POST'
        => $auth->register(),

    // Sign In (alias for Login)
    $path === '/signin'          && $method === 'GET'
        => (function () use ($middleware): void {
            $middleware->redirectIfAuthenticated();
            include __DIR__ . '/../views/login.php';
        })(),

    $path === '/login'           && $method === 'GET'
        => (function () use ($middleware): void {
            $middleware->redirectIfAuthenticated();
            include __DIR__ . '/../views/login.php';
        })(),

    $path === '/login'           && $method === 'POST'
        => $auth->login(),

    $path === '/logout'          && $method === 'GET'
        => (function () use ($auth): void {
            // Handle logout as GET request for navigation link
            $auth->logout();
        })(),

    $path === '/logout'          && $method === 'POST'
        => $auth->logout(),

    $path === '/forgot-password' && $method === 'GET'
        => (function () use ($middleware): void {
            $middleware->redirectIfAuthenticated();
            include __DIR__ . '/../views/forgot_password.php';
        })(),

    $path === '/forgot-password' && $method === 'POST'
        => $auth->forgotPassword(),

    $path === '/reset-password'  && $method === 'GET'
        => (function () use ($middleware): void {
            $middleware->redirectIfAuthenticated();
            include __DIR__ . '/../views/reset_password.php';
        })(),

    $path === '/reset-password'  && $method === 'POST'
        => $auth->resetPassword(),

    // Protected API — returns current user info
    $path === '/api/me'          && $method === 'GET'
        => $auth->me(),

    // Fetch product details by a comma-separated list of IDs (used by Likes page)
    $path === '/api/products-by-ids' && $method === 'GET'
        => (function () use ($container): void {
            header('Content-Type: application/json; charset=utf-8');
            $rawIds  = $_GET['ids'] ?? '';
            $ids     = array_filter(array_map('intval', explode(',', $rawIds)));
            if (empty($ids)) {
                echo json_encode([]);
                exit;
            }
            $db          = \VantageMarket\Config\Database::getInstance();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare(
                "SELECT p.product_id, p.title, p.price, p.stock_level,
                        p.brand, p.image_url, c.category_name
                 FROM Products p
                 LEFT JOIN Categories c ON c.category_id = p.category_id
                 WHERE p.product_id IN ($placeholders)"
            );
            $stmt->execute(array_values($ids));
            $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            while (ob_get_level()) ob_end_clean();
            echo json_encode($products);
            exit;
        })(),


    // ==========================================================
    // Admin Panel (UC08, UC09)
    // ==========================================================

    // Admin login — reuses the normal /signin page
    // Sets intended_url so after login user is sent to /admin/dashboard
    $path === '/admin/login' && $method === 'GET'
        => (function (): void {
            session_start();
            $_SESSION['intended_url'] = '/admin/dashboard';
            header('Location: /signin'); exit;
        })(),
    $path === '/admin/logout'
        => (function (): void {
            session_start();
            // Clear admin session keys then do normal logout
            unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_email']);
            header('Location: /signin'); exit;
        })(),

    // Dashboard
    $path === '/admin' || $path === '/admin/dashboard'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->dashboard();
        })(),

    // Products
    $path === '/admin/products' && $method === 'GET'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->productList();
        })(),
    $path === '/admin/products/new' && $method === 'GET'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->productForm(0);
        })(),
    preg_match('#^/admin/products/edit/(\d+)$#', $path, $m) && $method === 'GET'
        => (function () use ($m): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->productForm((int)$m[1]);
        })(),
    $path === '/admin/products/save' && $method === 'POST'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->productSave();
        })(),
    $path === '/admin/products/delete' && $method === 'POST'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->productDelete();
        })(),

    // Orders
    $path === '/admin/orders' && $method === 'GET'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->orderList();
        })(),
    preg_match('#^/admin/orders/(\d+)$#', $path, $m) && $method === 'GET'
        => (function () use ($m): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->orderDetail((int)$m[1]);
        })(),
    $path === '/admin/orders/update-status' && $method === 'POST'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->orderUpdateStatus();
        })(),

    // Users
    $path === '/admin/users' && $method === 'GET'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->userList();
        })(),
    $path === '/admin/users/toggle' && $method === 'POST'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->userToggleActive();
        })(),

    // Promotions
    $path === '/admin/promotions' && $method === 'GET'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->promotionList();
        })(),
    $path === '/admin/promotions/save' && $method === 'POST'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->promotionSave();
        })(),
    $path === '/admin/promotions/delete' && $method === 'POST'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->promotionDelete();
        })(),

    // Reports & Audit
    $path === '/admin/reports'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->reports();
        })(),
    $path === '/admin/audit'
        => (function (): void {
            session_start();
            $ctrl = new \VantageMarket\Controllers\AdminController();
            $ctrl->auditLogView();
        })(),

    // ==========================================================
    // Order Tracking (UC07)
    // ==========================================================

    // POST /orders/place — convert cart → order
    $path === '/orders/place' && $method === 'POST'
        => (function () use ($container): void {
            $controller = new \VantageMarket\Controllers\OrderController(
                new \VantageMarket\Services\OrderRepository(),
                $container['cartRepository'],
                $container['session'],
            );
            $controller->place();
        })(),

    // GET /orders — list all orders for current user/session
    $path === '/orders' && $method === 'GET'
        => (function () use ($container): void {
            $controller = new \VantageMarket\Controllers\OrderController(
                new \VantageMarket\Services\OrderRepository(),
                $container['cartRepository'],
                $container['session'],
            );
            $controller->index();
        })(),

    // GET /orders/123 — single order tracker view
    preg_match('#^/orders/(\d+)$#', $path, $m) && $method === 'GET'
        => (function () use ($container, $m): void {
            $controller = new \VantageMarket\Controllers\OrderController(
                new \VantageMarket\Services\OrderRepository(),
                $container['cartRepository'],
                $container['session'],
            );
            $controller->show((int) $m[1]);
        })(),

    // POST /orders/123/advance — advance status (admin/demo)
    preg_match('#^/orders/(\d+)/advance$#', $path, $m) && $method === 'POST'
        => (function () use ($container, $m): void {
            $controller = new \VantageMarket\Controllers\OrderController(
                new \VantageMarket\Services\OrderRepository(),
                $container['cartRepository'],
                $container['session'],
            );
            $controller->advance((int) $m[1]);
        })(),

    // Validate a promo code and return JSON (used by cart page & checkout page
    // for live discount preview before the order is actually placed)
    $path === '/promo/validate' && $method === 'POST'
        => (function () use ($container): void {
            header('Content-Type: application/json; charset=utf-8');
            $code = trim($_POST['code'] ?? '');

            if ($code === '') {
                echo json_encode(['valid' => false, 'message' => 'Please enter a promo code.']);
                exit;
            }

            $db = \VantageMarket\Config\Database::getInstance();
            $stmt = $db->prepare(
                "SELECT * FROM Promotions WHERE code = :code AND is_active = 1 AND expiry_date >= CURDATE()"
            );
            $stmt->execute(['code' => $code]);
            $promo = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$promo) {
                echo json_encode(['valid' => false, 'message' => "Promo code \"$code\" is invalid or expired."]);
                exit;
            }

            echo json_encode([
                'valid'          => true,
                'code'           => $promo['code'],
                'discount_type'  => $promo['discount_type'],
                'discount_value' => (float) $promo['discount_value'],
                'message'        => $promo['discount_type'] === 'percentage'
                    ? "{$promo['discount_value']}% off applied!"
                    : "RM " . number_format((float)$promo['discount_value'], 2) . " off applied!",
            ]);
            exit;
        })(),

    // Checkout success page
    $path === '/checkout/success' && $method === 'GET'
        => (function () use ($container, $middleware): void {
            $middleware->requireAuth('/checkout');
            $session = $container['session'];
            $session->start();
            $orderId  = (int)($_GET['order'] ?? 0);
            $userName = $_SESSION['user_name'] ?? 'Guest User';
            $userType = 'User';
            $successMessage = $_SESSION['success_message'] ?? null;
            unset($_SESSION['success_message'], $_SESSION['vm_checkout_items']);
            include __DIR__ . '/../views/checkout_success.php';
        })(),

    // Protected dashboard
    $path === '/dashboard'
        => (function () use ($middleware): void {
            $middleware->requireAuth('/dashboard');
            include __DIR__ . '/../views/dashboard.php';
        })(),

    // 404 catch-all
    default => (function (): void {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
    })(),
};
