<?php
// =============================================================
// VantageMarket — Auth Configuration
// =============================================================

declare(strict_types=1);

return [
    // Session
    'session_lifetime_minutes'     => 30,       // UC04: expire after inactivity
    'remember_me_days'             => 30,        // UC04: persistent token duration

    // Login throttling (UC04 business rule)
    'max_failed_attempts'          => 5,
    'lockout_minutes'              => 15,

    // Password policy (UC05 business rule)
    'password_min_length'          => 8,
    'password_require_uppercase'   => true,
    'password_require_number'      => true,
    'password_require_special'     => true,

    // Password hashing
    'bcrypt_cost'                  => 12,

    // Cookie settings (UC04 business rule — HttpOnly, Secure)
    'cookie_name'                  => 'vm_remember',
    'cookie_secure'                => false,      // set false for local HTTP dev
    'cookie_httponly'              => true,
    'cookie_samesite'              => 'Lax',

    // Token
    'reset_token_expiry_minutes'   => 60,
];
