<?php
// =============================================================
// VantageMarket — CartRepository
// SRP: responsible for database persistence of Carts & Cart_Items
// Observer Pattern: used by CartObserver to remove out-of-stock items
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use PDO;
use VantageMarket\Config\Database;
use VantageMarket\Models\Cart;

/**
 * Handles all DB reads and writes for Shopping_Carts and Cart_Items.
 *
 * In the Observer pattern this repository is used by CartObserver::update()
 * to remove line items that belong to a product that has gone out of stock.
 */
class CartRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    // Cart creation / lookup
    // ----------------------------------------------------------

    /**
     * Find the active cart for a registered user, creating one if absent.
     *
     * @param int $userId
     * @return Cart
     */
    public function findOrCreateForUser(int $userId): Cart
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Shopping_Carts
             WHERE user_id = :uid
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();

        if ($row) {
            return $this->hydrate($row);
        }

        // Create a new cart for this user
        $this->db->prepare(
            'INSERT INTO Shopping_Carts (user_id) VALUES (:uid)'
        )->execute([':uid' => $userId]);

        return $this->findOrCreateForUser($userId);
    }

    /**
     * Find the active cart for a guest session, creating one if absent.
     *
     * @param string $sessionId  PHP session_id() value
     * @return Cart
     */
    public function findOrCreateForSession(string $sessionId): Cart
    {
        // Pin the cart session ID in $_SESSION so it survives PHP session regeneration.
        // On the very first call we store it; every subsequent request reuses it.
        if (!isset($_SESSION['vm_guest_cart_sid'])) {
            $_SESSION['vm_guest_cart_sid'] = $sessionId;
        }
        $pinnedId = $_SESSION['vm_guest_cart_sid'];

        $stmt = $this->db->prepare(
            'SELECT * FROM Shopping_Carts
             WHERE session_id = :sid AND user_id IS NULL
             LIMIT 1'
        );
        $stmt->execute([':sid' => $pinnedId]);
        $row = $stmt->fetch();

        if ($row) {
            return $this->hydrate($row);
        }

        $this->db->prepare(
            'INSERT INTO Shopping_Carts (session_id) VALUES (:sid)'
        )->execute([':sid' => $pinnedId]);

        return $this->findOrCreateForSession($pinnedId);
    }

    /**
     * Find a cart by its primary key.
     *
     * @param int $cartId
     * @return Cart|null
     */
    public function findById(int $cartId): ?Cart
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Shopping_Carts WHERE cart_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $cartId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // ----------------------------------------------------------
    // Cart item management
    // ----------------------------------------------------------

    /**
     * Add a product to a cart or increase its quantity if already present.
     *
     * Uses an ON DUPLICATE KEY UPDATE to handle the (cart_id, product_id)
     * unique constraint in Cart_Items.
     *
     * @param int $cartId
     * @param int $productId
     * @param int $qty        Units to add (default 1)
     */
    public function addItem(int $cartId, int $productId, int $qty = 1): void
    {
        $this->db->prepare(
            'INSERT INTO Cart_Items (cart_id, product_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + ?'
        )->execute([
            $cartId,
            $productId,
            $qty,
            $qty,
        ]);
    }

    /**
     * Remove a specific product from a cart entirely (regardless of quantity).
     *
     * Called by CartObserver::update() when a product goes out of stock,
     * ensuring the item is silently detached / greyed-out in the user's cart.
     *
     * @param int $cartId
     * @param int $productId
     */
    public function removeItem(int $cartId, int $productId): void
    {
        $this->db->prepare(
            'DELETE FROM Cart_Items
             WHERE cart_id   = :cart_id
               AND product_id = :product_id'
        )->execute([
            ':cart_id'    => $cartId,
            ':product_id' => $productId,
        ]);
    }

    /**
     * Update the quantity of an existing cart item.
     *
     * If the new quantity is ≤ 0 the item is removed instead.
     *
     * @param int $cartId
     * @param int $productId
     * @param int $newQty
     */
    public function updateItemQuantity(int $cartId, int $productId, int $newQty): void
    {
        if ($newQty <= 0) {
            $this->removeItem($cartId, $productId);
            return;
        }

        $this->db->prepare(
            'UPDATE Cart_Items
             SET    quantity = :qty
             WHERE  cart_id    = :cart_id
               AND  product_id = :product_id'
        )->execute([
            ':qty'        => $newQty,
            ':cart_id'    => $cartId,
            ':product_id' => $productId,
        ]);
    }

    /**
     * Return all line items in a cart with their quantities.
     *
     * Returns raw associative rows: [cart_item_id, cart_id, product_id, quantity, added_at]
     *
     * @param int $cartId
     * @return array<int, array<string, mixed>>
     */
    public function getItems(int $cartId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ci.cart_item_id,
                    ci.cart_id,
                    ci.product_id,
                    ci.quantity,
                    ci.added_at,
                    p.title,
                    p.price,
                    p.stock_level,
                    p.sku
             FROM   Cart_Items ci
             JOIN   Products p ON p.product_id = ci.product_id
             WHERE  ci.cart_id = :cart_id
             ORDER  BY ci.added_at ASC'
        );
        $stmt->execute([':cart_id' => $cartId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Empty all items from a cart (e.g. after successful checkout).
     *
     * @param int $cartId
     */
    public function clearCart(int $cartId): void
    {
        $this->db->prepare(
            'DELETE FROM Cart_Items WHERE cart_id = :cart_id'
        )->execute([':cart_id' => $cartId]);

        // Also reset the pinned guest cart session key so a fresh cart is created next visit
        unset($_SESSION['vm_guest_cart_sid']);
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function hydrate(array $row): Cart
    {
        return new Cart(
            cartId:      (int) $row['cart_id'],
            userId:      isset($row['user_id']) ? (int) $row['user_id'] : null,
            sessionId:   $row['session_id']   ?? null,
            lastUpdated: $row['last_updated'] ?? '',
        );
    }
}
