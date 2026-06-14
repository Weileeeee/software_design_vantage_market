<?php

namespace VantageMarket\Controllers;

use Exception;
use PDO;

class CheckoutController
{
    private $db;
    private $session;
    private $cartRepo;

    // We inject the database, session manager, and cart repository explicitly
    public function __construct($db, $session, $cartRepo)
    {
        $this->db = $db;
        $this->session = $session;
        $this->cartRepo = $cartRepo;
    }

    public function index()
    {
        // 1. Ensure user is tracked
        $this->session->start();
        $userId = $this->session->currentUserId();

        // 2. Use session-selected items (from cart page selection) or full cart
        if (!empty($_SESSION['vm_checkout_items'])) {
            $cartItems = $_SESSION['vm_checkout_items'];
        } else {
            $cart = $this->cartRepo->findOrCreateForUser($userId);
            $cartItems = $this->cartRepo->getItems($cart->cartId);
        }

        // 3. If nothing is in the cart, kick them back to the catalog
        if (empty($cartItems)) {
            header("Location: /cart?action=empty");
            exit;
        }

        // 4. Load the visual UI page
        include __DIR__ . '/../../views/checkout_view.php';
    }

    public function processCheckout()
    {
        $this->session->start();
        $userId = $this->session->currentUserId();

        // Use session-selected items if present, else fall back to full cart
        $cart = $this->cartRepo->findOrCreateForUser($userId);
        if (!empty($_SESSION['vm_checkout_items'])) {
            $cartItems = $_SESSION['vm_checkout_items'];
        } else {
            $cartItems = $this->cartRepo->getItems($cart->cartId);
        }

        $promoCode = $_POST['promo_code'] ?? null;

        if (empty($cartItems)) {
            $_SESSION['checkout_error'] = "Your cart is empty. Please select items to order.";
            header("Location: /cart");
            exit;
        }

        try {
            // ==========================================
            // START TRANSACTION: Lock the database state
            // ==========================================
            $this->db->beginTransaction();

            $subtotal = 0;
            $validatedItems = [];

            // ------------------------------------------
            // PHASE 1: Inventory Verification & Locking
            // ------------------------------------------
            foreach ($cartItems as $item) {
                // Ensure array keys match your actual cart_items columns
                $productId = $item['product_id'];
                $qty = $item['quantity'];

                // Explicitly lock the product rows using FOR UPDATE
                $stmt = $this->db->prepare("SELECT product_id, title, price, stock_level FROM Products WHERE product_id = :id FOR UPDATE");
                $stmt->execute(['id' => $productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Product ID {$productId} is no longer available.");
                }

                if ($product['stock_level'] < $qty) {
                    throw new Exception("Sorry, '{$product['title']}' only has {$product['stock_level']} units left.");
                }

                $itemTotal = $product['price'] * $qty;
                $subtotal += $itemTotal;

                $validatedItems[] = [
                    'product_id' => $product['product_id'],
                    'quantity'   => $qty,
                    'price'      => $product['price']
                ];
            }

            // ------------------------------------------
            // PHASE 2: Promo Code Processing (UC09 Math)
            // ------------------------------------------
            $discountAmount = 0;

            if (!empty($promoCode)) {
                $stmt = $this->db->prepare("SELECT * FROM Promotions WHERE code = :code AND is_active = 1 AND expiry_date >= CURDATE()");
                $stmt->execute(['code' => $promoCode]);
                $promo = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($promo) {
                    if ($promo['discount_type'] === 'percentage') {
                        $discountAmount = $subtotal * ($promo['discount_value'] / 100);
                    } else if ($promo['discount_type'] === 'fixed') {
                        $discountAmount = $promo['discount_value'];
                    }
                    // Prevent negative balances
                    $discountAmount = min($discountAmount, $subtotal);
                } else {
                    throw new Exception("Promo code '{$promoCode}' is invalid or expired.");
                }
            }

            $finalTotalAmount = $subtotal - $discountAmount;

            // ------------------------------------------
            // PHASE 3: Order Creation (UC06)
            // ------------------------------------------

            // A. Insert the Parent Order
            $stmt = $this->db->prepare("INSERT INTO Orders (user_id, address_id, subtotal, total_amount, status) VALUES (:uid, 1, :sub, :tot, 'pending')");
            $stmt->execute([
                'uid' => $userId,
                'sub' => $subtotal,
                'tot' => $finalTotalAmount
            ]);
            $newOrderId = $this->db->lastInsertId();

            // B. Insert Child Items & Deduct Physical Stock
            foreach ($validatedItems as $item) {
                // Log the breakdown
                $stmt = $this->db->prepare("INSERT INTO Order_Items (order_id, product_id, quantity, purchased_price) VALUES (:oid, :pid, :qty, :price)");
                $stmt->execute([
                    'oid'   => $newOrderId,
                    'pid'   => $item['product_id'],
                    'qty'   => $item['quantity'],
                    'price' => $item['price']
                ]);

                // Deduct the inventory
                $stmt = $this->db->prepare("UPDATE Products SET stock_level = stock_level - :qty WHERE product_id = :pid");
                $stmt->execute([
                    'qty' => $item['quantity'],
                    'pid' => $item['product_id']
                ]);
            }

            // C. Remove only the ordered items from cart (other items remain)
            foreach ($validatedItems as $item) {
                $this->cartRepo->removeItem($cart->cartId, $item['product_id']);
            }

            // ==========================================
            // COMMIT TRANSACTION: Save everything safely
            // ==========================================
            $this->db->commit();

            // Clear session checkout state
            unset($_SESSION['vm_checkout_items']);
            $_SESSION['success_message'] = "Order #{$newOrderId} successfully placed!";

            // Redirect to success page
            header("Location: /checkout/success?order=" . $newOrderId);
            exit;
        } catch (Exception $e) {
            // Something broke. Roll back changes to prevent phantom orders
            $this->db->rollBack();

            $_SESSION['checkout_error'] = $e->getMessage();
            header("Location: /checkout");
            exit;
        }
    }
}
