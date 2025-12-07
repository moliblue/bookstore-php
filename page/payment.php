<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php?error=Please login to proceed with payment');
    exit;
}

// Load database and helpers
require_once __DIR__ . '/../sb_base.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/product_functions.php';

// Get selected items from POST or redirect back
if (!isset($_POST['selected_items']) || empty($_POST['selected_items'])) {
    header('Location: cart_view.php?error=Please select items to checkout');
    exit;
}

$selectedItems = json_decode($_POST['selected_items'], true);
$userId = cart_user_id();

// Get cart items that match selected IDs
$cartItems = [];
$totalAmount = 0;

foreach ($selectedItems as $productId) {
    $sql = "SELECT * FROM cart_items WHERE user_id = ? AND product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $productId]);
    $item = $stmt->fetch();

    if ($item) {
        $product = get_product_by_id($item['product_id']);
        if ($product) {
            $lineTotal = $product['price'] * $item['quantity'];
            $totalAmount += $lineTotal;

            $cartItems[] = [
                'product' => $product,
                'quantity' => $item['quantity'],
                'line_total' => $lineTotal
            ];
        }
    }
}

// If no valid items, redirect back
if (empty($cartItems)) {
    header('Location: cart_view.php?error=No valid items found');
    exit;
}

$_title = 'Payment - SB Online';
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

        .payment-container {
            max-width: 1200px;
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

        .page-title {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .page-title h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin: 0;
        }

        .payment-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .payment-form,
        .order-summary {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .order-summary {
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .form-section h3 {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #ecf0f1;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-size: 0.95rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: "Georgia", serif;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .payment-method {
            position: relative;
        }

        .payment-method input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .payment-method label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            background: #fafafa;
        }

        .payment-method input[type="radio"]:checked+label {
            border-color: #e74c3c;
            background: #fff5f5;
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

        .order-item img {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            background: #ecf0f1;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.3rem;
        }

        .item-qty {
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .item-price {
            font-size: 1rem;
            font-weight: bold;
            color: #e74c3c;
            text-align: right;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            font-size: 1rem;
            color: #2c3e50;
        }

        .summary-line.total {
            border-top: 2px solid #ecf0f1;
            margin-top: 1rem;
            padding-top: 1rem;
            font-size: 1.3rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1.5rem;
            font-family: "Georgia", serif;
        }

        .submit-btn:hover {
            background: #c0392b;
        }

        .secure-note {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 968px) {
            .payment-wrapper {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
                order: -1;
            }
        }

        @media (max-width: 640px) {
            .payment-methods {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="payment-container">
        <a href="cart_view.php" class="back-link">← Back to Cart</a>

        <div class="page-title">
            <h1>Payment Details</h1>
        </div>

        <div class="payment-wrapper">
            <div class="payment-form">
                <form id="payment-form" action="payment_process.php" method="POST">
                    <input type="hidden" name="total_amount" value="<?= number_format($totalAmount, 2, '.', '') ?>">
                    <input type="hidden" name="selected_items" value="<?= htmlspecialchars($_POST['selected_items']) ?>">
                    
                    <div class="form-section">
                        <h3>Payment Method</h3>
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="credit_card" value="Credit Card" required>
                                <label for="credit_card">💳 Credit Card</label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="debit_card" value="Debit Card">
                                <label for="debit_card">💳 Debit Card</label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="online_banking" value="Online Banking">
                                <label for="online_banking">🏦 Online Banking</label>
                            </div>
                            <div class="payment-method">
                                <input type="radio" name="payment_method" id="ewallet" value="E-Wallet">
                                <label for="ewallet">📱 E-Wallet</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-section" id="card-details">
                        <h3>Card Information</h3>
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Billing Information</h3>
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" required>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">Complete Payment</button>
                    <div class="secure-note">🔒 Secure payment processing</div>
                </form>
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>

                <div style="margin: 1.5rem 0;">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <img src="uploads/products/<?= htmlspecialchars($item['product']['cover_image']) ?>" alt="">
                            <div class="item-info">
                                <div class="item-name"><?= htmlspecialchars($item['product']['title']) ?></div>
                                <div class="item-qty">Qty: <?= $item['quantity'] ?></div>
                            </div>
                            <div class="item-price">RM<?= number_format($item['line_total'], 2) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-line">
                    <span>Subtotal</span>
                    <span>RM<?= number_format($totalAmount, 2) ?></span>
                </div>
                <div class="summary-line">
                    <span>Shipping</span>
                    <span>RM0.00</span>
                </div>
                <div class="summary-line total">
                    <span>Total</span>
                    <span>RM<?= number_format($totalAmount, 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-format card number
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });

        // Auto-format expiry date
        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });

        // Only allow numbers in CVV
        document.getElementById('cvv').addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Show/hide card details based on payment method
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
        const cardDetails = document.getElementById('card-details');

        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                if (this.value === 'Credit Card' || this.value === 'Debit Card') {
                    cardDetails.style.display = 'block';
                } else {
                    cardDetails.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>

<?php include '../sb_foot.php'; ?>