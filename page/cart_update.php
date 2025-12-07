<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../sb_base.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Not logged in";
    exit;
}

$userId    = $_SESSION['user_id'];
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity  = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!$productId || !$quantity || $quantity < 1) {
    http_response_code(422);
    echo "Invalid input";
    exit;
}

// ① 获取产品库存
$product = get_product_by_id($productId);

if (!$product) {
    http_response_code(404);
    echo "Product not found";
    exit;
}

$stock = (int)$product['stock_quantity'];

// ② 后端强制检查库存
if ($quantity > $stock) {
    http_response_code(409);  // Conflict
    echo "exceed_stock";
    exit;
}

$sql = "UPDATE cart_items 
        SET quantity = ?, updated_at = NOW()
        WHERE user_id = ? AND product_id = ?";

$stmt = $pdo->prepare($sql);

if ($stmt->execute([$quantity, $userId, $productId])) {
    echo "updated";
} else {
    echo "error";
}
