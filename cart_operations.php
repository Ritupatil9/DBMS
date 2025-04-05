<?php
require_once 'db_config.php';

class CartOperations {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Add item to cart
    public function addToCart($userId, $productId, $quantity = 1) {
        try {
            // Check if user has an active cart
            $cartId = $this->getOrCreateCart($userId);
            
            // Check if product exists in cart
            $stmt = $this->pdo->prepare("SELECT cart_item_id, quantity FROM cart_items 
                                       WHERE cart_id = ? AND product_id = ?");
            $stmt->execute([$cartId, $productId]);
            $existingItem = $stmt->fetch();

            if ($existingItem) {
                // Update quantity if item exists
                $stmt = $this->pdo->prepare("UPDATE cart_items 
                                           SET quantity = quantity + ? 
                                           WHERE cart_item_id = ?");
                $stmt->execute([$quantity, $existingItem['cart_item_id']]);
            } else {
                // Add new item to cart
                $stmt = $this->pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) 
                                           VALUES (?, ?, ?)");
                $stmt->execute([$cartId, $productId, $quantity]);
            }
            return true;
        } catch (PDOException $e) {
            error_log("Error adding to cart: " . $e->getMessage());
            return false;
        }
    }

    // Get or create cart for user
    private function getOrCreateCart($userId) {
        $stmt = $this->pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();

        if ($cart) {
            return $cart['cart_id'];
        } else {
            $stmt = $this->pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $stmt->execute([$userId]);
            return $this->pdo->lastInsertId();
        }
    }

    // Get cart items
    public function getCartItems($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ci.*, p.name, p.price, p.image_url, p.description
                FROM cart_items ci
                JOIN cart c ON ci.cart_id = c.cart_id
                JOIN products p ON ci.product_id = p.product_id
                WHERE c.user_id = ?
                ORDER BY ci.added_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting cart items: " . $e->getMessage());
            return [];
        }
    }

    // Update item quantity
    public function updateQuantity($userId, $cartItemId, $quantity) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE cart_items ci
                JOIN cart c ON ci.cart_id = c.cart_id
                SET ci.quantity = ?
                WHERE ci.cart_item_id = ? AND c.user_id = ?
            ");
            return $stmt->execute([$quantity, $cartItemId, $userId]);
        } catch (PDOException $e) {
            error_log("Error updating quantity: " . $e->getMessage());
            return false;
        }
    }

    // Remove item from cart
    public function removeItem($userId, $cartItemId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE ci FROM cart_items ci
                JOIN cart c ON ci.cart_id = c.cart_id
                WHERE ci.cart_item_id = ? AND c.user_id = ?
            ");
            return $stmt->execute([$cartItemId, $userId]);
        } catch (PDOException $e) {
            error_log("Error removing item: " . $e->getMessage());
            return false;
        }
    }

    // Clear cart
    public function clearCart($userId) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE ci FROM cart_items ci
                JOIN cart c ON ci.cart_id = c.cart_id
                WHERE c.user_id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }

    // Get cart total
    public function getCartTotal($userId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT SUM(ci.quantity * p.price) as total
                FROM cart_items ci
                JOIN cart c ON ci.cart_id = c.cart_id
                JOIN products p ON ci.product_id = p.product_id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
        } catch (PDOException $e) {
            error_log("Error getting cart total: " . $e->getMessage());
            return 0;
        }
    }
}

// Example usage:
/*
$cartOps = new CartOperations($pdo);

// Add item to cart
$cartOps->addToCart($userId, $productId, $quantity);

// Get cart items
$items = $cartOps->getCartItems($userId);

// Update quantity
$cartOps->updateQuantity($userId, $cartItemId, $newQuantity);

// Remove item
$cartOps->removeItem($userId, $cartItemId);

// Get total
$total = $cartOps->getCartTotal($userId);
*/
?> 