<?php
// =============================================================
// VantageMarket — OrderController
// HTTP layer: receives request, calls OrderRepository, returns response
// Keeps all HTTP concerns out of repository classes (SRP)
// =============================================================

declare(strict_types=1);

namespace VantageMarket\Controllers;

use VantageMarket\Services\CartRepository;
use VantageMarket\Services\OrderRepository;
use VantageMarket\Services\SessionManager;

final class OrderController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private CartRepository  $cartRepository,
        private SessionManager  $session,
    ) {}

    // ----------------------------------------------------------
    // POST /orders/place
    // Converts the current cart into a new order
    // ----------------------------------------------------------

    public function place(): void
    {
        $this->requirePost();
        $this->session->start();

        $userId    = $this->session->isAuthenticated() ? $this->session->currentUserId() : null;
        $sessionId = session_id();

        // Resolve the correct cart
        $cart = $userId
            ? $this->cartRepository->findOrCreateForUser($userId)
            : $this->cartRepository->findOrCreateForSession($sessionId);

        try {
            $order = $this->orderRepository->createFromCart(
                $cart->cartId,
                $userId,
                $userId ? null : $sessionId,
            );

            // Clear the cart after a successful order
            $this->cartRepository->clearCart($cart->cartId);

            header('Location: /orders/' . $order->orderId);
            exit;

        } catch (\RuntimeException $e) {
            $this->jsonError($e->getMessage(), 422);
        }
    }

    // ----------------------------------------------------------
    // GET /orders/{id}
    // Returns order details + line items for the tracker view
    // ----------------------------------------------------------

    public function show(int $orderId): void
    {
        $this->session->start();

        $order = $this->orderRepository->findById($orderId);

        if ($order === null) {
            $this->jsonError('Order not found.', 404);
            return;
        }

        // Ownership check: guests match by session, users by user_id
        if (!$this->ownsOrder($order)) {
            $this->jsonError('Forbidden.', 403);
            return;
        }

        $items = $this->orderRepository->getItems($orderId);

        // Render tracker view if this is a browser request
        if ($this->wantHtml()) {
            $pageTitle = "Order #{$orderId}";
            include __DIR__ . '/../../views/order_tracker.php';
            return;
        }

        $this->jsonSuccess([
            'order' => $order->toPublicArray(),
            'items' => $items,
        ]);
    }

    // ----------------------------------------------------------
    // GET /orders
    // Lists all orders for the current user / session
    // ----------------------------------------------------------

    public function index(): void
    {
        $this->session->start();

        if ($this->session->isAuthenticated()) {
            $orders = $this->orderRepository->findByUser($this->session->currentUserId());
        } else {
            $orders = $this->orderRepository->findBySession(session_id());
        }

        $orderData = array_map(fn($o) => $o->toPublicArray(), $orders);

        if ($this->wantHtml()) {
            $pageTitle = 'My Orders';
            include __DIR__ . '/../../views/order_list.php';
            return;
        }

        $this->jsonSuccess(['orders' => $orderData]);
    }

    // ----------------------------------------------------------
    // POST /orders/{id}/advance   (admin / demo use)
    // Moves an order to the next status in the pipeline
    // ----------------------------------------------------------

    public function advance(int $orderId): void
    {
        $this->requirePost();

        try {
            $order = $this->orderRepository->advanceStatus($orderId);

            $this->jsonSuccess([
                'message' => "Order #{$orderId} is now '{$order->status}'.",
                'order'   => $order->toPublicArray(),
            ]);

        } catch (\RuntimeException $e) {
            $this->jsonError($e->getMessage(), 422);
        }
    }

    // ----------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------

    /**
     * Returns true if the currently authenticated user or guest session
     * is the owner of this order.
     */
    private function ownsOrder(\VantageMarket\Models\Order $order): bool
    {
        if ($this->session->isAuthenticated()) {
            return $order->userId === $this->session->currentUserId();
        }

        return $order->sessionId === session_id();
    }

    private function wantHtml(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'text/html');
    }

    private function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonError('Method not allowed.', 405);
            exit;
        }
    }

    private function jsonSuccess(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, ...$data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function jsonError(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
