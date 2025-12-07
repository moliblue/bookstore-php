<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php?error=Please login to proceed');
    exit;
}

// Load database and helpers
require_once __DIR__ . '/../sb_base.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/product_functions.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart_view.php');
    exit;
}

// Get form data
$totalAmount = $_POST['total_amount'] ?? 0;
$paymentMethod = $_POST['payment_method'] ?? '';
$selectedItems = $_POST['selected_items'] ?? '';
$fullName = $_POST['full_name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';

// Validate required fields
if (empty($totalAmount) || empty($paymentMethod) || empty($selectedItems)) {
    header('Location: payment.php?error=Missing required fields');
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    $userId = $_SESSION['user_id'];

    // Verify user exists in database
    $userCheckSql = "SELECT id FROM users WHERE id = ?";
    $userCheckStmt = $pdo->prepare($userCheckSql);
    $userCheckStmt->execute([$userId]);
    $userExists = $userCheckStmt->fetch();

    if (!$userExists) {
        throw new Exception('User account not found. Please log in again.');
    }

    // Generate unique order number
    $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

    // Create order
    $orderSql = "INSERT INTO orders (user_id, order_number, total_amount, status, payment_status, 
                 shipping_name, shipping_email, shipping_phone, shipping_address, created_at) 
                 VALUES (?, ?, ?, 'processing', 'paid', ?, ?, ?, ?, NOW())";

    $orderStmt = $pdo->prepare($orderSql);
    $orderStmt->execute([
        $userId,
        $orderNumber,
        $totalAmount,
        $fullName,
        $email,
        $phone,
        $address
    ]);

    $orderId = $pdo->lastInsertId();

    // Get selected items and create order items
    $selectedProductIds = json_decode($selectedItems, true);
    $cartUserId = cart_user_id();

    foreach ($selectedProductIds as $productId) {
        // Get cart item
        $cartSql = "SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?";
        $cartStmt = $pdo->prepare($cartSql);
        $cartStmt->execute([$cartUserId, $productId]);
        $cartItem = $cartStmt->fetch();

        if ($cartItem) {
            // Get product details
            $product = get_product_by_id($productId);

            if ($product) {
                $subtotal = $product['price'] * $cartItem['quantity'];

                // Insert order item
                $itemSql = "INSERT INTO order_items (order_id, product_id, product_title, product_price, quantity, subtotal, created_at) 
                            VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $itemStmt = $pdo->prepare($itemSql);
                $itemStmt->execute([
                    $orderId,
                    $productId,
                    $product['title'],
                    $product['price'],
                    $cartItem['quantity'],
                    $subtotal
                ]);
//  //remove the product quantity from database
$newquantity = $product['stock_quantity'] - $cartItem['quantity'];
$updateQuantitySql = "UPDATE products SET stock_quantity = ? WHERE id = ?";
$updateQuantityStmt = $pdo->prepare($updateQuantitySql);
$updateQuantityStmt->execute([$newquantity, $productId]);
            }
        }
    }

    // Generate unique payment reference number
    $referenceNo = 'PAY-' . strtoupper(bin2hex(random_bytes(6)));

    // Insert payment record
    $paymentSql = "INSERT INTO payment (order_id, amount, method, status, reference_no, transaction_time, created_at) 
                   VALUES (?, ?, ?, 'SUCCESS', ?, NOW(), NOW())";

    $paymentStmt = $pdo->prepare($paymentSql);
    $paymentStmt->execute([
        $orderId,
        $totalAmount,
        $paymentMethod,
        $referenceNo
    ]);

    $paymentId = $pdo->lastInsertId();

    // Remove purchased items from cart
    foreach ($selectedProductIds as $productId) {
        cart_remove_item($productId);
    }

 




    // Commit transaction
    $pdo->commit();

    // Redirect to success page
    header('Location: payment_success.php?ref=' . $referenceNo . '&payment_id=' . $paymentId);
    exit;
} catch (Exception $e) {
    // Rollback on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Payment processing error: ' . $e->getMessage());

    // User-friendly error message
    $errorMsg = urlencode('Payment processing failed. Please try again or contact support.');
    header('Location: cart_view.php?error=' . $errorMsg);
    exit;
}
