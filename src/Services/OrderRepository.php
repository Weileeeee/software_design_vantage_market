<?php
// =============================================================
// VantageMarket — OrderRepository
// SRP: responsible for all database persistence of Orders & Order_Items
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Services;

use PDO;
use VantageMarket\Config\Database;
use VantageMarket\Models\Order;

/**
 * Handles all DB reads and writes for the Orders and Order_Items tables.
 */
class OrderRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ----------------------------------------------------------
    // Order creation
    // ----------------------------------------------------------

    /**
     * Place a new order from a cart's current items.
     *
     * Steps:
     *   1. Insert a row into Orders with status = 'pending'.
     *   2. Copy every Cart_Items row across into Order_Items.
     *   3. Clear the cart.
     *
     * @param int      $cartId
     * @param int|null $userId     null for guest checkouts
     * @param string|null $sessionId
     * @return Order   The newly created order
     * @throws \RuntimeException if the cart is empty
     */
    public function createFromCart(int $cartId, ?int $userId, ?string $sessionId): Order
    {
        // Fetch cart items with price snapshot
        $stmt = $this->db->prepare(
            'SELECT ci.product_id, ci.quantity, p.price
             FROM   Cart_Items ci
             JOIN   Products p ON p.product_id = ci.product_id
             WHERE  ci.cart_id = :cart_id'
        );
        $stmt->execute([':cart_id' => $cartId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            throw new \RuntimeException('Cannot place an order from an empty cart.');
        }

        $total = array_sum(array_map(
            fn($row) => $row['price'] * $row['quantity'],
            $items
        ));

        // Insert order header
        $this->db->prepare(
            'INSERT INTO Orders (user_id, session_id, status, total_amount)
             VALUES (:user_id, :session_id, :status, :total)'
        )->execute([
            ':user_id'    => $userId,
            ':session_id' => $sessionId,
            ':status'     => 'pending',
            ':total'      => $total,
        ]);

        $orderId = (int) $this->db->lastInsertId();

        // Copy line items (price_at_purchase = snapshot so price changes don't affect old orders)
        $itemStmt = $this->db->prepare(
            'INSERT INTO Order_Items (order_id, product_id, quantity, price_at_purchase)
             VALUES (:order_id, :product_id, :qty, :price)'
        );

        foreach ($items as $item) {
            $itemStmt->execute([
                ':order_id'   => $orderId,
                ':product_id' => $item['product_id'],
                ':qty'        => $item['quantity'],
                ':price'      => $item['price'],
            ]);
        }

        return $this->findById($orderId);
    }

    // ----------------------------------------------------------
    // Lookup
    // ----------------------------------------------------------

    /**
     * Find a single order by its primary key.
     *
     * @param int $orderId
     * @return Order|null
     */
    public function findById(int $orderId): ?Order
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Orders WHERE order_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $orderId]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Return all orders for a registered user, newest first.
     *
     * @param int $userId
     * @return Order[]
     */
    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Orders
             WHERE  user_id = :uid
             ORDER  BY created_at DESC'
        );
        $stmt->execute([':uid' => $userId]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Return all orders placed in a guest session, newest first.
     *
     * @param string $sessionId
     * @return Order[]
     */
    public function findBySession(string $sessionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM Orders
             WHERE  session_id = :sid AND user_id IS NULL
             ORDER  BY created_at DESC'
        );
        $stmt->execute([':sid' => $sessionId]);

        return array_map([$this, 'hydrate'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Return all line items for a given order, with product details joined.
     *
     * @param int $orderId
     * @return array<int, array<string, mixed>>
     */
    public function getItems(int $orderId): array
    {
        $stmt = $this->db->prepare(
            'SELECT oi.order_item_id,
                    oi.order_id,
                    oi.product_id,
                    oi.quantity,
                    oi.price_at_purchase,
                    p.title,
                    p.sku
             FROM   Order_Items oi
             JOIN   Products p ON p.product_id = oi.product_id
             WHERE  oi.order_id = :order_id
             ORDER  BY oi.order_item_id ASC'
        );
        $stmt->execute([':order_id' => $orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ----------------------------------------------------------
    // Status management
    // ----------------------------------------------------------

    /**
     * Advance an order to the next status in the pipeline.
     *
     * Pipeline: pending → processing → shipped → delivered
     *
     * @param int $orderId
     * @return Order  The updated order
     * @throws \RuntimeException if order not found or already delivered
     */
    public function advanceStatus(int $orderId): Order
    {
        $order = $this->findById($orderId);

        if ($order === null) {
            throw new \RuntimeException("Order #{$orderId} not found.");
        }

        $next = $this->nextStatus($order->status);

        if ($next === null) {
            throw new \RuntimeException("Order #{$orderId} is already delivered.");
        }

        $this->db->prepare(
            'UPDATE Orders SET status = :status WHERE order_id = :id'
        )->execute([':status' => $next, ':id' => $orderId]);

        return $this->findById($orderId);
    }

    /**
     * Explicitly set an order to a specific valid status.
     *
     * @param int    $orderId
     * @param string $status  Must be one of Order::STATUSES
     * @return Order
     * @throws \InvalidArgumentException on bad status value
     * @throws \RuntimeException if order not found
     */
    public function setStatus(int $orderId, string $status): Order
    {
        if (!in_array($status, Order::STATUSES, true)) {
            throw new \InvalidArgumentException(
                "Invalid status '{$status}'. Allowed: " . implode(', ', Order::STATUSES)
            );
        }

        $order = $this->findById($orderId);

        if ($order === null) {
            throw new \RuntimeException("Order #{$orderId} not found.");
        }

        $this->db->prepare(
            'UPDATE Orders SET status = :status WHERE order_id = :id'
        )->execute([':status' => $status, ':id' => $orderId]);

        return $this->findById($orderId);
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    private function hydrate(array $row): Order
    {
        return new Order(
            orderId:     (int)   $row['order_id'],
            userId:      isset($row['user_id']) ? (int) $row['user_id'] : null,
            sessionId:   $row['session_id']  ?? null,
            status:      $row['status'],
            totalAmount: (float) $row['total_amount'],
            createdAt:   $row['created_at']  ?? '',
            updatedAt:   $row['updated_at']  ?? '',
        );
    }

    /**
     * Returns the next status in the pipeline, or null if already at the end.
     */
    private function nextStatus(string $current): ?string
    {
        $idx = array_search($current, Order::STATUSES, true);

        if ($idx === false || $idx >= count(Order::STATUSES) - 1) {
            return null;
        }

        return Order::STATUSES[$idx + 1];
    }
}
