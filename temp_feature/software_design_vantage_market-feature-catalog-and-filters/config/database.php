<?php
// =============================================================
// VantageMarket — Database Configuration
// =============================================================

declare(strict_types=1);

return [
    'host'     => $_ENV['DB_HOST']     ?? (getenv('DB_HOST') ?: 'localhost'),
    'port'     => $_ENV['DB_PORT']     ?? (getenv('DB_PORT') ?: '3306'),
    'dbname'   => $_ENV['DB_NAME']     ?? (getenv('DB_NAME') ?: 'vantagemarket'),
    'username' => $_ENV['DB_USER']     ?? (getenv('DB_USER') ?: 'root'),
    'password' => '', // Forced empty string to match local XAMPP defaults
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
