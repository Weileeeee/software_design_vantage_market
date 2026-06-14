<?php declare(strict_types=1); $error = $error ?? null; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — VantageMarket</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #1e272e; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
    .login-box { background: #2d3436; border-radius: 10px; padding: 44px 40px; width: 380px; box-shadow: 0 8px 32px rgba(0,0,0,0.4); }
    .login-brand { text-align: center; margin-bottom: 32px; }
    .login-brand .name { font-size: 22px; font-weight: 800; color: #fff; }
    .login-brand .sub  { font-size: 12px; color: #ff6b6b; text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
    .login-brand i     { font-size: 36px; color: #ff6b6b; margin-bottom: 10px; display: block; }
    label { display: block; font-size: 12px; font-weight: 700; color: #b2bec3; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
    input { width: 100%; padding: 11px 14px; border: 1px solid #3d3d3d; border-radius: 4px; background: #1e272e; color: #ecf0f1; font-size: 14px; margin-bottom: 16px; }
    input:focus { outline: none; border-color: #ff6b6b; }
    .btn { width: 100%; padding: 12px; background: #ff6b6b; color: #fff; border: none; border-radius: 4px; font-size: 15px; font-weight: 700; cursor: pointer; margin-top: 6px; }
    .btn:hover { background: #ff5252; }
    .alert { background: rgba(231,76,60,0.15); border: 1px solid #e74c3c; color: #ff6b6b; padding: 10px 14px; border-radius: 4px; font-size: 13px; margin-bottom: 18px; }
    .back-link { text-align: center; margin-top: 20px; font-size: 13px; }
    .back-link a { color: #636e72; text-decoration: none; }
    .back-link a:hover { color: #ff6b6b; }
  </style>
</head>
<body>
  <div class="login-box">
    <div class="login-brand">
      <i class="fas fa-user-shield"></i>
      <div class="name">VantageMarket</div>
      <div class="sub">Admin Panel</div>
    </div>
    <?php if ($error): ?>
    <div class="alert"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="/admin/login">
      <label>Username</label>
      <input type="text" name="username" placeholder="admin username" required autofocus>
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>
      <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Sign In</button>
    </form>
    <div class="back-link"><a href="/"><i class="fas fa-arrow-left"></i> Back to store</a></div>
  </div>
</body>
</html>
