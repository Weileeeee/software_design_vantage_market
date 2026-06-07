<?php
// =============================================================
// VantageMarket — Public Router (public/index.php)
// Entry point for all HTTP requests
// Put this file inside your web server's document root
// =============================================================

declare(strict_types=1);

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
            // Checkout only the user-selected products from the cart
            $session = $container['session'];
            $session->start();
            $cartRepo = $container['cartRepository'];

            $cart = $session->isAuthenticated()
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());

            $allItems = $cartRepo->getItems($cart->cartId);

            // Filter to selected product IDs only
            $selectedIds = array_map('intval', $_POST['selected_products'] ?? []);
            $cartItems   = array_values(array_filter(
                $allItems,
                fn($item) => in_array((int)$item['product_id'], $selectedIds, true)
            ));

            if (empty($cartItems)) {
                header('Location: /cart?action=noselect');
                exit;
            }

            // Store selected items in session so /checkout/process can use them
            $_SESSION['vm_checkout_items'] = $cartItems;

            $userType = $session->isAuthenticated() ? 'User' : 'Guest';
            $userName = $_SESSION['user_name'] ?? 'Guest User';
            include __DIR__ . '/../views/checkout.php';
        })(),

    $path === '/checkout' && $method === 'GET'
        => (function () use ($container): void {
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            $cartRepo = $container['cartRepository'];
            
            $cart = $session->isAuthenticated() 
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());
            $cartItems = $cartRepo->getItems($cart->cartId);
            
            if (empty($cartItems)) {
                header('Location: /cart?action=empty');
                exit;
            }
            
            $userType = $session->isAuthenticated() ? 'User' : 'Guest';
            $userName = $_SESSION['user_name'] ?? 'Guest User';
            
            include __DIR__ . '/../views/checkout.php';
        })(),

    $path === '/checkout/process' && $method === 'POST'
        => (function () use ($container): void {
            /** @var \VantageMarket\Services\SessionManager $session */
            $session = $container['session'];
            $session->start();
            $cartRepo = $container['cartRepository'];

            $cart = $session->isAuthenticated()
                ? $cartRepo->findOrCreateForUser($session->currentUserId())
                : $cartRepo->findOrCreateForSession(session_id());

            // Use session-stored selected items (set by /checkout/selected),
            // falling back to all cart items for direct /checkout GET flows
            if (!empty($_SESSION['vm_checkout_items'])) {
                $cartItems = $_SESSION['vm_checkout_items'];
            } else {
                $cartItems = $cartRepo->getItems($cart->cartId);
            }

            if (empty($cartItems)) {
                $userType = $session->isAuthenticated() ? 'User' : 'Guest';
                $userName = $_SESSION['user_name'] ?? 'Guest User';
                $success  = false;
                $checkoutLog = "<p style='color:#c62828;'>Your cart is empty. Please select items before placing an order.</p>";
                include __DIR__ . '/../views/checkout.php';
                exit;
            }
            
            $cartTotal = array_reduce($cartItems, fn($sum, $item) => $sum + ($item['price'] * $item['quantity']), 0.0);
            $paymentMethod = $_POST['payment_method'] ?? '';
            
            // Strategy Pattern: Client instantiates the correct concrete strategy
            $strategy = null;
            if ($paymentMethod === 'credit_card') {
                $strategy = new \VantageMarket\Strategy\CreditCardPayment($_POST['card_number'] ?? '0000000000000000');
            } elseif ($paymentMethod === 'ewallet') {
                $strategy = new \VantageMarket\Strategy\EWalletPayment($_POST['wallet_id'] ?? 'UnknownWallet');
            } elseif ($paymentMethod === 'fpx') {
                $strategy = new \VantageMarket\Strategy\FPXBankingPayment($_POST['bank_code'] ?? 'MBB0228');
            } elseif ($paymentMethod === 'cod') {
                $strategy = new \VantageMarket\Strategy\CashOnDeliveryPayment();
            }
            
            // Context Class
            $checkoutSession = new \VantageMarket\Services\CheckoutSession($cartTotal);
            if ($strategy) {
                $checkoutSession->setPaymentStrategy($strategy);
            }
            
            // Start output buffering to capture the echo statements for the view
            ob_start();
            $success = $checkoutSession->executeCheckout();
            $checkoutLog = ob_get_clean();
            
            if ($success) {
                // Remove only the ordered items from the cart
                foreach ($cartItems as $item) {
                    $cartRepo->removeItem($cart->cartId, (int)$item['product_id']);
                }
                // Detach observers for ordered items
                $stockSubject = $container['stockSubject'];
                foreach ($cartItems as $item) {
                    $stockSubject->detach((int)$item['product_id'], $cart->cartId);
                }
                // Clear the session-stored checkout items
                unset($_SESSION['vm_checkout_items']);
            }
            
            $userType = $session->isAuthenticated() ? 'User' : 'Guest';
            $userName = $_SESSION['user_name'] ?? 'Guest User';
            
            // Show the result in the checkout view
            include __DIR__ . '/../views/checkout.php';
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

    // 404 catch-all
    default => (function (): void {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route not found.']);
    })(),
};
