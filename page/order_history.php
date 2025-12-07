<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php?error=Please login to view your orders');
    exit;
}

// Load database
require_once __DIR__ . '/../sb_base.php';

$userId = $_SESSION['user_id'];

// Get all orders for this user
$ordersSql = "SELECT o.*, p.reference_no, p.method as payment_method 
              FROM orders o 
              LEFT JOIN payment p ON o.order_id = p.order_id 
              WHERE o.user_id = ? 
              ORDER BY o.created_at DESC";
$ordersStmt = $pdo->prepare($ordersSql);
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();

$_title = 'Order History - SB Online';
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

        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin: 0;
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

        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .order-header {
            background: #f9f7f2;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #ecf0f1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .order-info-item {
            display: flex;
            flex-direction: column;
        }

        .order-info-label {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 0.3rem;
        }

        .order-info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 600;
        }

        .order-number {
            color: #3498db;
        }

        .order-total {
            color: #e74c3c;
            font-size: 1.2rem;
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

        .order-items {
            padding: 1.5rem 2rem;
        }

        .order-items-title {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
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
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            background: #ecf0f1;
        }

        .item-details {
            flex: 1;
        }

        .item-title {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .item-price {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .item-qty {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .item-subtotal {
            font-size: 1rem;
            font-weight: bold;
            color: #2c3e50;
            text-align: right;
        }

        .order-actions {
            padding: 1rem 2rem;
            border-top: 1px solid #ecf0f1;
            background: #fafafa;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            font-family: "Georgia", serif;
        }

        .btn-outline {
            background: white;
            color: #2c3e50;
            border: 1px solid #2c3e50;
        }

        .btn-outline:hover {
            background: #2c3e50;
            color: white;
        }

        .empty-orders {
            background: white;
            padding: 4rem 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .empty-orders h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .empty-orders p {
            color: #7f8c8d;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
            padding: 0.8rem 2rem;
        }

        .btn-primary:hover {
            background: #c0392b;
        }

        @media (max-width: 768px) {
            .order-header {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .order-item {
                flex-wrap: wrap;
            }

            .order-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="orders-container">
        <a href="/index.php" class="back-link">← Back to Home</a>

        <div class="page-header">
            <h1>My Order History</h1>
        </div>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet. Start shopping now!</p>
                <a href="/page/product_view.php" class="btn btn-primary">Browse Books</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php
                // Get order items
                $itemsSql = "SELECT oi.*, p.cover_image 
                             FROM order_items oi 
                             LEFT JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?";
                $itemsStmt = $pdo->prepare($itemsSql);
                $itemsStmt->execute([$order['order_id']]);
                $orderItems = $itemsStmt->fetchAll();
                ?>

                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info-item">
                            <span class="order-info-label">Order Number</span>
                            <span class="order-info-value order-number"><?= htmlspecialchars($order['order_number']) ?></span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Date</span>
                            <span class="order-info-value"><?= date('d M Y', strtotime($order['created_at'])) ?></span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Status</span>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </div>
                        <div class="order-info-item">
                            <span class="order-info-label">Total</span>
                            <span class="order-info-value order-total">RM<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>

                    <div class="order-items">
                        <div class="order-items-title">Order Items (<?= count($orderItems) ?>)</div>
                        <?php foreach ($orderItems as $item): ?>
                            <div class="order-item">
                                <img src="uploads/products/<?= htmlspecialchars($item['cover_image'] ?? 'default.jpg') ?>"
                                    alt="<?= htmlspecialchars($item['product_title']) ?>"
                                    class="item-image">
                                <div class="item-details">
                                    <div class="item-title"><?= htmlspecialchars($item['product_title']) ?></div>
                                    <div class="item-price">RM<?= number_format($item['product_price'], 2) ?> each</div>
                                    <div class="item-qty">Quantity: <?= $item['quantity'] ?></div>
                                </div>
                                <div class="item-subtotal">RM<?= number_format($item['subtotal'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-actions">
                        <a href="order_details.php?order_id=<?= $order['order_id'] ?>" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>

<?php include '../sb_foot.php'; ?>