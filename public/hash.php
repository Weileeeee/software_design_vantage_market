<?php
// TEMPORARY FILE — DELETE AFTER USE
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403); exit('Forbidden');
}
$password = $_GET['p'] ?? 'Admin@1234';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
echo "<pre>Password: $password\nHash:     $hash\n\nSQL:\nUPDATE Admin SET password_hash = '$hash' WHERE username = 'admin_leong';</pre>";
