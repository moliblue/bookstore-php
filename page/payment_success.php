<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php');
    exit;
}

// Get payment details
$referenceNo = $_GET['ref'] ?? '';
$paymentId = $_GET['payment_id'] ?? '';

if (empty($referenceNo) || empty($paymentId)) {
    header('Location: /index.php');
    exit;
}

// Load database
require_once __DIR__ . '/../sb_base.php';

// Get payment details from database
$sql = "SELECT * FROM payment WHERE payment_id = ? AND reference_no = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$paymentId, $referenceNo]);
$payment = $stmt->fetch();

if (!$payment) {
    header('Location: /index.php');
    exit;
}

$_title = 'Payment Successful - SB Online';
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
            min-height: 100vh;
        }

        .success-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 0 2rem;
        }

        .success-card {
            background: white;
            border-radius: 8px;
            padding: 3rem 2.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #27ae60;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }

            to {
                transform: scale(1);
            }
        }

        .success-icon::before {
            content: '✓';
            color: white;
            font-size: 50px;
            font-weight: bold;
        }

        h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 1rem;
            color: #7f8c8d;
            margin-bottom: 2rem;
        }

        .payment-details {
            background: #f9f7f2;
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-size: 0.95rem;
            color: #7f8c8d;
        }

        .detail-value {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .detail-value.amount {
            font-size: 1.5rem;
            color: #e74c3c;
        }

        .detail-value.status {
            color: #27ae60;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 0.9rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s;
            font-family: "Georgia", serif;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:hover {
            background: #c0392b;
        }

        .btn-secondary {
            background: white;
            color: #2c3e50;
            border: 2px solid #2c3e50;
        }

        .btn-secondary:hover {
            background: #2c3e50;
            color: white;
        }

        @media (max-width: 640px) {
            .success-card {
                padding: 2rem 1.5rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon"></div>

            <h1>Payment Successful!</h1>
            <p class="subtitle">Thank you for your purchase. Your order has been confirmed.</p>

            <div class="payment-details">
                <div class="detail-row">
                    <span class="detail-label">Reference Number</span>
                    <span class="detail-value"><?= htmlspecialchars($referenceNo) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method</span>
                    <span class="detail-value"><?= htmlspecialchars($payment['method']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction Time</span>
                    <span class="detail-value"><?= date('d M Y, h:i A', strtotime($payment['transaction_time'])) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value status"><?= htmlspecialchars($payment['status']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount Paid</span>
                    <span class="detail-value amount">RM<?= number_format($payment['amount'], 2) ?></span>
                </div>
            </div>

            <div class="action-buttons">
                <a href="/index.php" class="btn btn-primary">Continue Shopping</a>
                <a href="cart_view.php" class="btn btn-secondary">View Cart</a>
            </div>
        </div>
    </div>
</body>

</html>

<?php include '../sb_foot.php'; ?>