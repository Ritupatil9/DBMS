<?php
header('Content-Type: application/json');
require_once 'db_config.php';
require_once 'cart_operations.php';

$cartOps = new CartOperations($pdo);

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get user ID from session (you should implement proper authentication)
session_start();
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

switch ($method) {
    case 'GET':
        if ($action === 'items') {
            // Get cart items
            $items = $cartOps->getCartItems($userId);
            $total = $cartOps->getCartTotal($userId);
            echo json_encode([
                'items' => $items,
                'total' => $total
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'add':
                if (isset($data['product_id']) && isset($data['quantity'])) {
                    $success = $cartOps->addToCart($userId, $data['product_id'], $data['quantity']);
                    echo json_encode(['success' => $success]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                }
                break;

            case 'update':
                if (isset($data['cart_item_id']) && isset($data['quantity'])) {
                    $success = $cartOps->updateQuantity($userId, $data['cart_item_id'], $data['quantity']);
                    echo json_encode(['success' => $success]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                }
                break;

            case 'remove':
                if (isset($data['cart_item_id'])) {
                    $success = $cartOps->removeItem($userId, $data['cart_item_id']);
                    echo json_encode(['success' => $success]);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing cart_item_id']);
                }
                break;

            case 'clear':
                $success = $cartOps->clearCart($userId);
                echo json_encode(['success' => $success]);
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
?> 