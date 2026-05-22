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

    // Auth routes (guest-only pages redirect away if already logged in)
    $path === '/register'        && $method === 'GET'
        => (function () use ($middleware): void {
            $middleware->redirectIfAuthenticated();
            // Render your React frontend or a PHP template here
            include __DIR__ . '/../views/register.php';
        })(),

    $path === '/register'        && $method === 'POST'
        => $auth->register(),

    $path === '/login'           && $method === 'GET'
        => (function () use ($middleware): void {
            $middleware->redirectIfAuthenticated();
            include __DIR__ . '/../views/login.php';
        })(),

    $path === '/login'           && $method === 'POST'
        => $auth->login(),

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

    // Protected example route
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
