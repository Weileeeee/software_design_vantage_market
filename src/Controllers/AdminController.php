<?php
// =============================================================
// VantageMarket — AdminController
// Handles all admin HTTP routes: login, dashboard, products,
// orders, promotions, users, reports, audit log
// =============================================================
declare(strict_types=1);

namespace VantageMarket\Controllers;

use PDO;
use VantageMarket\Config\Database;

final class AdminController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    // Auth
    // ----------------------------------------------------------

    public function showLogin(): void
    {
        if ($this->isAdminLoggedIn()) {
            header('Location: /admin/dashboard'); exit;
        }
        $error = $_SESSION['admin_login_error'] ?? null;
        unset($_SESSION['admin_login_error']);
        include __DIR__ . '/../../views/admin/login.php';
    }

    public function processLogin(): void
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $stmt = $this->db->prepare(
            'SELECT admin_id, username, password_hash, email, is_active
             FROM Admin WHERE username = :u LIMIT 1'
        );
        $stmt->execute([':u' => $username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !$admin['is_active'] || !password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_login_error'] = 'Invalid credentials or account inactive.';
            header('Location: /admin/login'); exit;
        }

        // Update last_login
        $this->db->prepare('UPDATE Admin SET last_login = NOW() WHERE admin_id = :id')
                 ->execute([':id' => $admin['admin_id']]);

        $_SESSION['admin_id']       = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_email']    = $admin['email'];

        $this->auditLog('ADMIN_LOGIN', 'Admin', $admin['admin_id'], null);
        header('Location: /admin/dashboard'); exit;
    }

    public function logout(): void
    {
        $this->auditLog('ADMIN_LOGOUT', 'Admin', $_SESSION['admin_id'] ?? null, null);
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_email']);
        header('Location: /admin/login'); exit;
    }

    // ----------------------------------------------------------
    // Dashboard
    // ----------------------------------------------------------

    public function dashboard(): void
    {
        $this->requireAdmin();

        $stats = [
            'total_orders'    => $this->scalar('SELECT COUNT(*) FROM Orders'),
            'pending_orders'  => $this->scalar("SELECT COUNT(*) FROM Orders WHERE status = 'pending'"),
            'total_products'  => $this->scalar("SELECT COUNT(*) FROM Products WHERE status = 'active'"),
            'low_stock'       => $this->scalar("SELECT COUNT(*) FROM Products WHERE stock_level <= 10 AND status = 'active'"),
            'total_users'     => $this->scalar('SELECT COUNT(*) FROM Users WHERE is_guest = 0'),
            'total_revenue'   => $this->scalar('SELECT COALESCE(SUM(total_amount),0) FROM Orders WHERE status NOT IN ("cancelled","refunded")'),
            'active_promos'   => $this->scalar("SELECT COUNT(*) FROM Promotions WHERE expiry_date >= NOW() AND is_active = 1"),
        ];

        $recent_orders = $this->db->query(
            'SELECT o.order_id, o.status, o.total_amount, o.created_at,
                    CONCAT(u.first_name," ",u.last_name) AS customer_name
             FROM Orders o
             LEFT JOIN Users u ON u.user_id = o.user_id
             ORDER BY o.created_at DESC LIMIT 8'
        )->fetchAll(PDO::FETCH_ASSOC);

        $low_stock_products = $this->db->query(
            "SELECT product_id, title, stock_level, sku
             FROM Products WHERE stock_level <= 10 AND status = 'active'
             ORDER BY stock_level ASC LIMIT 6"
        )->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/admin/dashboard.php';
    }

    // ----------------------------------------------------------
    // Products (CRUD)
    // ----------------------------------------------------------

    public function productList(): void
    {
        $this->requireAdmin();
        $search = $_GET['search'] ?? '';
        $cat    = (int)($_GET['category'] ?? 0);

        $sql = "SELECT p.*, c.category_name,
                (p.status = 'active') AS is_active
                FROM Products p
                LEFT JOIN Categories c ON c.category_id = p.category_id
                WHERE 1=1";
        $params = [];
        if ($search) { $sql .= ' AND (p.title LIKE :s OR p.sku LIKE :s)'; $params[':s'] = "%$search%"; }
        if ($cat)    { $sql .= ' AND p.category_id = :c'; $params[':c'] = $cat; }
        $sql .= ' ORDER BY p.product_id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $categories = $this->db->query('SELECT * FROM Categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/admin/products.php';
    }

    public function productForm(int $id = 0): void
    {
        $this->requireAdmin();
        $product    = $id ? $this->db->prepare('SELECT * FROM Products WHERE product_id = :id')
                              ->execute([':id' => $id]) && null : null;
        if ($id) {
            $stmt = $this->db->prepare('SELECT * FROM Products WHERE product_id = :id');
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        $categories = $this->db->query('SELECT * FROM Categories ORDER BY category_name')->fetchAll(PDO::FETCH_ASSOC);
        $error      = $_SESSION['admin_form_error'] ?? null;
        $success    = $_SESSION['admin_form_success'] ?? null;
        unset($_SESSION['admin_form_error'], $_SESSION['admin_form_success']);
        include __DIR__ . '/../../views/admin/product_form.php';
    }

    public function productSave(): void
    {
        $this->requireAdmin();
        $id    = (int)($_POST['product_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock_level'] ?? 0);
        $cat   = (int)($_POST['category_id'] ?? 0);
        $desc  = trim($_POST['description'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $sku   = trim($_POST['sku'] ?? '');
        $image = trim($_POST['image_url'] ?? '');
        $active = isset($_POST['is_active']) ? 'active' : 'archived';

        if (!$title || $price <= 0) {
            $_SESSION['admin_form_error'] = 'Title and valid price are required.';
            header('Location: ' . ($id ? "/admin/products/edit/$id" : '/admin/products/new')); exit;
        }

        if ($id) {
            $before = $this->db->prepare('SELECT * FROM Products WHERE product_id = :id');
            $before->execute([':id' => $id]);
            $beforeData = $before->fetch(PDO::FETCH_ASSOC);

            $this->db->prepare(
                'UPDATE Products SET title=:t, description=:d, price=:p, stock_level=:s,
                 category_id=:c, brand=:b, sku=:k, image_url=:img, status=:a WHERE product_id=:id'
            )->execute([':t'=>$title,':d'=>$desc,':p'=>$price,':s'=>$stock,
                        ':c'=>$cat,':b'=>$brand,':k'=>$sku,':img'=>$image,':a'=>$active,':id'=>$id]);

            $this->auditLog('EDIT_PRODUCT', 'Products', $id,
                json_encode(['before' => $beforeData,
                             'after'  => compact('title','price','stock','active')]));
            $_SESSION['admin_form_success'] = "Product #$id updated.";
            header("Location: /admin/products/edit/$id"); exit;
        } else {
            $this->db->prepare(
                'INSERT INTO Products (category_id,title,description,price,stock_level,brand,sku,image_url,status)
                 VALUES (:c,:t,:d,:p,:s,:b,:k,:img,:a)'
            )->execute([':c'=>$cat,':t'=>$title,':d'=>$desc,':p'=>$price,
                        ':s'=>$stock,':b'=>$brand,':k'=>$sku,':img'=>$image,':a'=>$active]);
            $newId = (int)$this->db->lastInsertId();
            $this->auditLog('ADD_PRODUCT', 'Products', $newId,
                json_encode(compact('title','price','stock')));
            $_SESSION['admin_form_success'] = "Product \"$title\" created (ID: $newId).";
            header("Location: /admin/products/edit/$newId"); exit;
        }
    }

    public function productDelete(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['product_id'] ?? 0);
        if ($id) {
            $this->db->prepare("UPDATE Products SET status = 'archived' WHERE product_id = :id")
                     ->execute([':id' => $id]);
            $this->auditLog('ARCHIVE_PRODUCT', 'Products', $id, null);
        }
        $_SESSION['admin_form_success'] = "Product #$id archived.";
        header('Location: /admin/products'); exit;
    }

    // ----------------------------------------------------------
    // Orders Management
    // ----------------------------------------------------------

    public function orderList(): void
    {
        $this->requireAdmin();
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = 'SELECT o.*, CONCAT(u.first_name," ",u.last_name) AS customer_name, u.email_address
                FROM Orders o LEFT JOIN Users u ON u.user_id = o.user_id WHERE 1=1';
        $params = [];
        if ($status) { $sql .= ' AND o.status = :st'; $params[':st'] = $status; }
        if ($search) { $sql .= ' AND (o.order_id = :s OR u.email_address LIKE :sl)';
                       $params[':s'] = (int)$search; $params[':sl'] = "%$search%"; }
        $sql .= ' ORDER BY o.created_at DESC LIMIT 100';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include __DIR__ . '/../../views/admin/orders.php';
    }

    public function orderDetail(int $id): void
    {
        $this->requireAdmin();
        $stmt = $this->db->prepare(
            'SELECT o.*, CONCAT(u.first_name," ",u.last_name) AS customer_name,
                    u.email_address, a.street_address, a.city, a.state, a.postcode
             FROM Orders o
             LEFT JOIN Users u ON u.user_id = o.user_id
             LEFT JOIN Address a ON a.address_id = o.address_id
             WHERE o.order_id = :id'
        );
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$order) { http_response_code(404); echo "Order not found."; return; }

        $itemsStmt = $this->db->prepare(
            'SELECT oi.*, p.title, p.sku FROM Order_Items oi
             JOIN Products p ON p.product_id = oi.product_id
             WHERE oi.order_id = :id'
        );
        $itemsStmt->execute([':id' => $id]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        $success = $_SESSION['admin_form_success'] ?? null;
        unset($_SESSION['admin_form_success']);
        include __DIR__ . '/../../views/admin/order_detail.php';
    }

    public function orderUpdateStatus(): void
    {
        $this->requireAdmin();
        $id     = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $valid  = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded'];

        if ($id && in_array($status, $valid, true)) {
            $this->db->prepare('UPDATE Orders SET status=:s WHERE order_id=:id')
                     ->execute([':s'=>$status, ':id'=>$id]);
            $this->auditLog('UPDATE_ORDER_STATUS', 'Orders', $id,
                json_encode(['new_status' => $status]));
            $_SESSION['admin_form_success'] = "Order #$id status updated to \"$status\".";
        }
        header("Location: /admin/orders/$id"); exit;
    }

    // ----------------------------------------------------------
    // Users
    // ----------------------------------------------------------

    public function userList(): void
    {
        $this->requireAdmin();
        $search = $_GET['search'] ?? '';
        $sql = 'SELECT user_id, first_name, last_name, email_address,
                (NOT is_locked AND NOT is_guest) AS is_active,
                is_locked, created_at FROM Users WHERE 1=1';
        $params = [];
        if ($search) { $sql .= ' AND (email_address LIKE :s OR first_name LIKE :s OR last_name LIKE :s)';
                       $params[':s'] = "%$search%"; }
        $sql .= ' ORDER BY created_at DESC LIMIT 200';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../../views/admin/users.php';
    }

    public function userToggleActive(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['user_id'] ?? 0);
        if ($id) {
            $this->db->prepare(
                'UPDATE Users SET is_locked = NOT is_locked WHERE user_id = :id'
            )->execute([':id' => $id]);
            $this->auditLog('TOGGLE_USER', 'Users', $id, null);
        }
        header('Location: /admin/users'); exit;
    }

    // ----------------------------------------------------------
    // Promotions
    // ----------------------------------------------------------

    public function promotionList(): void
    {
        $this->requireAdmin();
        $promos = $this->db->query(
            'SELECT p.*, COUNT(e.category_id) AS exclusion_count
             FROM Promotions p
             LEFT JOIN Promo_Category_Exclusions e ON e.promo_id = p.promo_id
             GROUP BY p.promo_id ORDER BY p.created_at DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
        $success = $_SESSION['admin_form_success'] ?? null;
        unset($_SESSION['admin_form_success']);
        include __DIR__ . '/../../views/admin/promotions.php';
    }

    public function promotionSave(): void
    {
        $this->requireAdmin();
        $code      = strtoupper(trim($_POST['code'] ?? ''));
        $value     = (float)($_POST['discount_value'] ?? 0);
        $type      = $_POST['discount_type'] ?? 'percentage';
        $minSpend  = (float)($_POST['min_spend'] ?? 0);
        $limit     = $_POST['usage_limit'] !== '' ? (int)$_POST['usage_limit'] : null;
        $start     = $_POST['start_date'] ?? date('Y-m-d');
        $expiry    = $_POST['expiry_date'] ?? date('Y-m-d', strtotime('+30 days'));
        $adminId   = $_SESSION['admin_id'];

        if (!$code || $value <= 0) {
            $_SESSION['admin_form_error'] = 'Code and valid discount value required.';
            header('Location: /admin/promotions'); exit;
        }

        $this->db->prepare(
            'INSERT INTO Promotions (code,discount_value,discount_type,min_spend,usage_limit,start_date,expiry_date,created_by)
             VALUES (:code,:val,:type,:min,:lim,:start,:exp,:by)'
        )->execute([':code'=>$code,':val'=>$value,':type'=>$type,':min'=>$minSpend,
                    ':lim'=>$limit,':start'=>$start.' 00:00:00',':exp'=>$expiry.' 23:59:59',':by'=>$adminId]);

        $newId = (int)$this->db->lastInsertId();
        $this->auditLog('CREATE_PROMO', 'Promotions', $newId, json_encode(compact('code','value','type')));
        $_SESSION['admin_form_success'] = "Promo code \"$code\" created.";
        header('Location: /admin/promotions'); exit;
    }

    public function promotionDelete(): void
    {
        $this->requireAdmin();
        $id = (int)($_POST['promo_id'] ?? 0);
        if ($id) {
            $this->db->prepare('DELETE FROM Promotions WHERE promo_id = :id')->execute([':id' => $id]);
            $this->auditLog('DELETE_PROMO', 'Promotions', $id, null);
        }
        header('Location: /admin/promotions'); exit;
    }

    // ----------------------------------------------------------
    // Reports
    // ----------------------------------------------------------

    public function reports(): void
    {
        $this->requireAdmin();

        $revenue_by_month = $this->db->query(
            'SELECT DATE_FORMAT(created_at,"%Y-%m") AS month,
                    COUNT(*) AS order_count,
                    SUM(total_amount) AS revenue
             FROM Orders WHERE status NOT IN ("cancelled","refunded")
             GROUP BY month ORDER BY month DESC LIMIT 12'
        )->fetchAll(PDO::FETCH_ASSOC);

        $top_products = $this->db->query(
            'SELECT p.title, p.sku,
                    SUM(oi.quantity) AS units_sold,
                    SUM(oi.quantity * oi.purchased_price) AS revenue
             FROM Order_Items oi
             JOIN Products p ON p.product_id = oi.product_id
             GROUP BY oi.product_id ORDER BY units_sold DESC LIMIT 10'
        )->fetchAll(PDO::FETCH_ASSOC);

        $orders_by_status = $this->db->query(
            'SELECT status, COUNT(*) AS cnt FROM Orders GROUP BY status'
        )->fetchAll(PDO::FETCH_ASSOC);

        $new_users_30d = $this->scalar(
            "SELECT COUNT(*) FROM Users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );

        $this->auditLog('GENERATE_REPORT', 'Reports', null, null);
        include __DIR__ . '/../../views/admin/reports.php';
    }

    // ----------------------------------------------------------
    // Audit Log
    // ----------------------------------------------------------

    public function auditLogView(): void
    {
        $this->requireAdmin();
        $logs = $this->db->query(
            'SELECT al.*, a.username
             FROM Audit_Log al
             LEFT JOIN Admin a ON a.admin_id = al.admin_id
             ORDER BY al.logged_at DESC LIMIT 200'
        )->fetchAll(PDO::FETCH_ASSOC);
        include __DIR__ . '/../../views/admin/audit_log.php';
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function requireAdmin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!$this->isAdminLoggedIn()) {
            header('Location: /admin/login'); exit;
        }
    }

    private function isAdminLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return !empty($_SESSION['admin_id']);
    }

    private function scalar(string $sql): mixed
    {
        return $this->db->query($sql)->fetchColumn();
    }

    private function auditLog(string $action, ?string $table, ?int $targetId, ?string $changesJson): void
    {
        $this->db->prepare(
            'INSERT INTO Audit_Log (admin_id, action_type, target_table, target_id, changes_json, ip_address)
             VALUES (:aid, :act, :tbl, :tid, :json, :ip)'
        )->execute([
            ':aid'  => $_SESSION['admin_id'] ?? null,
            ':act'  => $action,
            ':tbl'  => $table,
            ':tid'  => $targetId,
            ':json' => $changesJson,
            ':ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
