<?php
// =============================================================
// VantageMarket — Bootstrap
// Wires all dependencies together (poor-man's DI container)
// DIP: AuthController depends on abstractions, not concretions
// =============================================================

declare(strict_types=1);

// --- Autoloader (PSR-4 compatible without Composer) ----------
spl_autoload_register(function (string $class): void {
    $prefix = 'VantageMarket\\';
    $base   = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// --- Environment (replace with vlucas/phpdotenv in production) ---
// $_ENV['DB_HOST'] = 'localhost';
// $_ENV['DB_NAME'] = 'vantagemarket';
// ...

// --- Instantiate services (Dependency Inversion) -------------
use VantageMarket\Controllers\AuthController;
use VantageMarket\Middleware\AuthMiddleware;
use VantageMarket\Services\{
    UserValidator,
    UserRepository,
    UserMailer,
    SecurityLogger,
    SessionManager,
    UserRegistrationService,
    UserLoginService,
    PasswordResetService
};

$validator   = new UserValidator();
$repository  = new UserRepository();
$mailer      = new UserMailer();
$logger      = new SecurityLogger();
$session     = new SessionManager();

$registrationService = new UserRegistrationService($validator, $repository, $mailer);
$loginService        = new UserLoginService($validator, $repository, $session, $logger, $mailer);
$resetService        = new PasswordResetService($validator, $repository, $mailer);

$authController = new AuthController(
    $registrationService,
    $loginService,
    $resetService,
    $session,
);

$authMiddleware = new AuthMiddleware($session, $loginService);

return [
    'auth'       => $authController,
    'middleware' => $authMiddleware,
    'session'    => $session,
];
