<?php
// Shared admin layout header — include at top of every admin view
$adminUsername = $_SESSION['admin_username'] ?? 'Admin';
$currentPath   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function adminNavActive(string $prefix): string {
    global $currentPath;
    return str_starts_with($currentPath, $prefix) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Admin' ?> — VantageMarket Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --sidebar-w: 230px;
      --primary: #ff6b6b;
      --dark: #2d3436;
      --sidebar-bg: #1e272e;
      --sidebar-hover: #2d3436;
      --sidebar-active: #ff6b6b;
      --card-bg: #ffffff;
      --body-bg: #f4f6f8;
      --text: #333;
      --muted: #7f8c8d;
      --border: #e0e0e0;
      --success: #27ae60;
      --warning: #f39c12;
      --danger: #e74c3c;
      --info: #2980b9;
    }
    body { font-family: 'Segoe UI', sans-serif; background: var(--body-bg); color: var(--text); display: flex; min-height: 100vh; }

    /* ── Sidebar ── */
    .sidebar {
      width: var(--sidebar-w); background: var(--sidebar-bg); color: #ecf0f1;
      display: flex; flex-direction: column; position: fixed; top: 0; left: 0; height: 100vh; z-index: 100; overflow-y: auto;
    }
    .sidebar-brand { padding: 20px 18px; border-bottom: 1px solid #2d3436; }
    .sidebar-brand .brand-name { font-size: 16px; font-weight: 800; color: #fff; letter-spacing: 0.5px; }
    .sidebar-brand .brand-sub  { font-size: 11px; color: #ff6b6b; text-transform: uppercase; letter-spacing: 1px; }
    .sidebar-section { padding: 14px 18px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #636e72; font-weight: 700; }
    .sidebar-nav a {
      display: flex; align-items: center; gap: 10px; padding: 10px 18px;
      color: #b2bec3; text-decoration: none; font-size: 14px; transition: 0.15s;
    }
    .sidebar-nav a:hover  { background: var(--sidebar-hover); color: #fff; }
    .sidebar-nav a.active { background: var(--sidebar-hover); color: var(--primary); border-left: 3px solid var(--primary); }
    .sidebar-nav a i      { width: 18px; text-align: center; font-size: 14px; }
    .sidebar-footer { margin-top: auto; padding: 16px 18px; border-top: 1px solid #2d3436; font-size: 13px; color: #636e72; }
    .sidebar-footer a { color: #ff6b6b; text-decoration: none; font-weight: 600; }

    /* ── Main content ── */
    .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
    .topbar {
      background: #fff; border-bottom: 1px solid var(--border);
      padding: 0 28px; height: 56px; display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
    }
    .topbar-title { font-size: 18px; font-weight: 700; color: var(--dark); }
    .topbar-right  { display: flex; align-items: center; gap: 16px; font-size: 14px; color: var(--muted); }
    .topbar-right a { color: var(--primary); text-decoration: none; font-weight: 600; }
    .content { padding: 28px; flex: 1; }

    /* ── Cards ── */
    .card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 22px; margin-bottom: 22px; }
    .card-title { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: var(--dark); display: flex; align-items: center; gap: 8px; }

    /* ── Stat cards ── */
    .stat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--card-bg); border: 1px solid var(--border); border-radius: 8px; padding: 20px; }
    .stat-card .stat-val  { font-size: 28px; font-weight: 800; color: var(--dark); }
    .stat-card .stat-label{ font-size: 12px; color: var(--muted); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-card .stat-icon { font-size: 24px; margin-bottom: 10px; }

    /* ── Tables ── */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { background: #f8f9fa; padding: 12px 14px; text-align: left; font-weight: 700; color: var(--dark); border-bottom: 2px solid var(--border); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafafa; }

    /* ── Badges ── */
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-pending    { background: #fff3cd; color: #856404; }
    .badge-confirmed  { background: #cfe2ff; color: #084298; }
    .badge-processing { background: #d1ecf1; color: #0c5460; }
    .badge-shipped    { background: #d4edda; color: #155724; }
    .badge-delivered  { background: #198754; color: #fff; }
    .badge-cancelled  { background: #f8d7da; color: #842029; }
    .badge-refunded   { background: #e2e3e5; color: #41464b; }
    .badge-active     { background: #d4edda; color: #155724; }
    .badge-inactive   { background: #f8d7da; color: #842029; }

    /* ── Forms ── */
    .form-group { margin-bottom: 16px; }
    .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: var(--dark); }
    .form-control { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: 4px; font-size: 14px; transition: border-color 0.15s; background: #fff; }
    .form-control:focus { outline: none; border-color: var(--primary); }
    select.form-control { cursor: pointer; }

    /* ── Buttons ── */
    .btn { display: inline-block; padding: 8px 18px; border-radius: 4px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; transition: 0.15s; }
    .btn-primary   { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: #ff5252; }
    .btn-dark      { background: var(--dark); color: #fff; }
    .btn-dark:hover{ background: #1a1f23; }
    .btn-success   { background: var(--success); color: #fff; }
    .btn-danger    { background: var(--danger); color: #fff; }
    .btn-warning   { background: var(--warning); color: #fff; }
    .btn-sm        { padding: 5px 12px; font-size: 12px; }
    .btn-outline   { background: transparent; border: 1px solid var(--border); color: var(--dark); }
    .btn-outline:hover { background: #f0f0f0; }

    /* ── Alerts ── */
    .alert { padding: 12px 16px; border-radius: 4px; margin-bottom: 18px; font-size: 14px; }
    .alert-success { background: #d4edda; color: #155724; border-left: 4px solid var(--success); }
    .alert-danger  { background: #f8d7da; color: #842029; border-left: 4px solid var(--danger); }
    .alert-warning { background: #fff3cd; color: #856404; border-left: 4px solid var(--warning); }

    /* ── Misc ── */
    .search-bar { display: flex; gap: 8px; margin-bottom: 18px; }
    .search-bar .form-control { max-width: 300px; }
    .page-actions { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    @media (max-width: 900px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } .stat-grid { grid-template-columns: repeat(2, 1fr); } }
  </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-name">VantageMarket</div>
    <div class="brand-sub">Admin Panel</div>
  </div>
  <div class="sidebar-section">Main</div>
  <div class="sidebar-nav">
    <a href="/admin/dashboard" class="<?= adminNavActive('/admin/dashboard') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  </div>
  <div class="sidebar-section">Catalogue</div>
  <div class="sidebar-nav">
    <a href="/admin/products" class="<?= adminNavActive('/admin/products') ?>"><i class="fas fa-box"></i> Products</a>
    <a href="/admin/products/new" class="<?= adminNavActive('/admin/products/new') ?>"><i class="fas fa-plus-circle"></i> Add Product</a>
  </div>
  <div class="sidebar-section">Commerce</div>
  <div class="sidebar-nav">
    <a href="/admin/orders" class="<?= adminNavActive('/admin/orders') ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
    <a href="/admin/users" class="<?= adminNavActive('/admin/users') ?>"><i class="fas fa-users"></i> Users</a>
    <a href="/admin/promotions" class="<?= adminNavActive('/admin/promotions') ?>"><i class="fas fa-tag"></i> Promotions</a>
  </div>
  <div class="sidebar-section">Insights</div>
  <div class="sidebar-nav">
    <a href="/admin/reports" class="<?= adminNavActive('/admin/reports') ?>"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="/admin/audit" class="<?= adminNavActive('/admin/audit') ?>"><i class="fas fa-history"></i> Audit Log</a>
  </div>
  <div class="sidebar-footer">
    Logged in as <strong><?= htmlspecialchars($adminUsername) ?></strong><br>
    <a href="/admin/logout"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </div>
</nav>

<!-- Main -->
<div class="main">
  <div class="topbar">
    <div class="topbar-title"><?= $pageTitle ?? 'Admin Panel' ?></div>
    <div class="topbar-right">
      <span><i class="fas fa-user-shield"></i> <?= htmlspecialchars($adminUsername) ?></span>
      <a href="/"><i class="fas fa-external-link-alt"></i> View Site</a>
    </div>
  </div>
  <div class="content">
