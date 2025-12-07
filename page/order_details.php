<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php?error=Please login to view order details');
    exit;
}

// Load database
require_once __DIR__ . '/../sb_base.php';

$userId = $_SESSION['user_id'];
$orderId = $_GET['order_id'] ?? 0;

// Get order details
$orderSql = "SELECT o.*, p.reference_no, p.method as payment_method, p.transaction_time 
             FROM orders o 
             LEFT JOIN payment p ON o.order_id = p.order_id 
             WHERE o.order_id = ? AND o.user_id = ?";
$orderStmt = $pdo->prepare($orderSql);
$orderStmt->execute([$orderId, $userId]);
$order = $orderStmt->fetch();

if (!$order) {
    header('Location: order_history.php');
    exit;
}

// Get order items
$itemsSql = "SELECT oi.*, p.cover_image 
             FROM order_items oi 
             LEFT JOIN products p ON oi.product_id = p.id 
             WHERE oi.order_id = ?";
$itemsStmt = $pdo->prepare($itemsSql);
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

$_title = 'Order Details - SB Online';
include '../sb_head.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_title ?></title>
    <style>
        body {
            background-color: #f9f7f2;
            font-family: "Georgia", serif;
        }

        .order-details-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            font-size: 1rem;
            text-decoration: none;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #e74c3c;
        }

        .order-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
        }

        .order-header h1 {
            color: #2c3e50;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .order-number {
            color: #3498db;
            font-size: 1.1rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .detail-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .detail-section h2 {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ecf0f1;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.6rem 0;
            border-bottom: 1px solid #f9f7f2;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: 600;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .items-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .items-section h2 {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .order-item {
            display: flex;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-image {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            background: #ecf0f1;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .item-info {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 0.3rem;
        }

        .item-price-section {
            text-align: right;
        }

        .item-subtotal {
            font-size: 1.1rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .order-summary {
            background: #f9f7f2;
            padding: 1.5rem;
            border-radius: 4px;
            margin-top: 1.5rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 1rem;
        }

        .summary-total {
            font-size: 1.4rem;
            font-weight: bold;
            color: #e74c3c;
            border-top: 2px solid #ddd;
            padding-top: 1rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .order-item {
                flex-direction: column;
            }

            .item-image {
                width: 100%;
                height: 200px;
            }

            .item-price-section {
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <div class="order-details-container">
        <a href="order_history.php" class="back-link">← Back to Order History</a>

        <div class="order-header">
            <h1>Order Details</h1>
            <div class="order-number">Order #<?= htmlspecialchars($order['order_number']) ?></div>
        </div>

        <div class="detail-grid">
            <div class="detail-section">
                <h2>Order Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Order Date:</span>
                    <span class="detail-value"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="status-badge status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Status:</span>
                    <span class="detail-value"><?= ucfirst($order['payment_status']) ?></span>
                </div>
            </div>

            <div class="detail-section">
                <h2>Payment Details</h2>
                <div class="detail-row">
                    <span class="detail-label">Reference No:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['reference_no'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Method:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['payment_method'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction Time:</span>
                    <span class="detail-value">
                        <?= $order['transaction_time'] ? date('d M Y, h:i A', strtotime($order['transaction_time'])) : 'N/A' ?>
                    </span>
                </div>
            </div>

            <div class="detail-section">
                <h2>Shipping Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['shipping_name'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['shipping_email'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['shipping_phone'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value" style="text-align: right; max-width: 200px;">
                        <?= htmlspecialchars($order['shipping_address'] ?? 'N/A') ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="items-section" style="margin-top: 2rem;">
            <h2>Order Items (<?= count($orderItems) ?>)</h2>

            <?php foreach ($orderItems as $item): ?>
                <div class="order-item">
                    <img src="uploads/products/<?= htmlspecialchars($item['cover_image'] ?? 'default.jpg') ?>"
                        alt="<?= htmlspecialchars($item['product_title']) ?>"
                        class="item-image">
                    <div class="item-details">
                        <div class="item-title"><?= htmlspecialchars($item['product_title']) ?></div>
                        <div class="item-info">Price: RM<?= number_format($item['product_price'], 2) ?></div>
                        <div class="item-info">Quantity: <?= $item['quantity'] ?></div>
                    </div>
                    <div class="item-price-section">
                        <div class="item-subtotal">RM<?= number_format($item['subtotal'], 2) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="order-summary">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>RM<?= number_format($order['total_amount'], 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping:</span>
                    <span>RM0.00</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total:</span>
                    <span>RM<?= number_format($order['total_amount'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

<?php include '../sb_foot.php'; ?>