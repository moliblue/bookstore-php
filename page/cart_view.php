<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// check if user already log in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php?error=Please login to view your cart');
    exit;
}

// Load database and helpers
require_once __DIR__ . '/../sb_base.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/product_functions.php';

// Get current user ID
$userId = cart_user_id();

// Get cart items
$sql = "SELECT * FROM cart_items WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

// Calculate subtotal
$subtotal = 0;
foreach ($cartItems as $item) {
    $product = get_product_by_id($item['product_id']);
    if ($product) {
        $subtotal += $product['price'] * $item['quantity'];
    }
}

$_title = 'My Cart - SB Online';
include '../sb_head.php';
?>

<!DOCTYPE html>
<html>

<head>
    <title><?= $_title ?></title>
    <style>
        body {
            background-color: #f9f7f2;
            font-family: "Georgia", serif;
        }

        .cart-container {
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

        .cart-header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            margin: 0;
        }

        .select-all-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .select-all-wrapper input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .cart-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .cart-items {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .cart-item {
            display: flex;
            gap: 1.5rem;
            padding: 1.5rem 0;
            border-bottom: 1px solid #ecf0f1;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .cart-item img {
            width: 120px;
            height: 160px;
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

        .item-price {
            font-size: 1.3rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 0.5rem;
        }

        .item-total {
            font-size: 1.1rem;
            color: #2c3e50;
            margin-bottom: 0.8rem;
        }

        .qty-selector {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            cursor: pointer;
            font-family: "Georgia", serif;
        }

        .remove-btn {
            cursor: pointer;
            color: #7f8c8d;
            font-size: 1.5rem;
            transition: color 0.3s;
            padding: 0.5rem;
        }

        .remove-btn:hover {
            color: #e74c3c;
        }

        .cart-summary {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }

        .cart-summary h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .summary-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: background 0.3s;
            font-family: "Georgia", serif;
        }

        .checkout-btn:hover {
            background: #c0392b;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-cart h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        @media (max-width: 968px) {
            .cart-content {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

            .cart-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }

        @media (max-width: 640px) {
            .cart-item {
                flex-wrap: wrap;
            }

            .cart-item img {
                width: 100%;
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <div class="cart-container">
        <a href="javascript:history.back()" class="back-link">← Back</a>

        <?php if (empty($cartItems)): ?>
            <div class="cart-items">
                <div class="empty-cart">
                    <h2>Your Cart is Empty</h2>
                    <p>Start adding some books to your cart!</p>
                    <a href="/page/product_view.php" style="display: inline-block; margin-top: 1rem; padding: 0.8rem 2rem; background: #e74c3c; color: white; text-decoration: none; border-radius: 4px;">Browse Books</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-header">
                <h1>My Shopping Cart</h1>
                <label class="select-all-wrapper">
                    <input type="checkbox" id="select-all">
                    <span>Select All</span>
                </label>
            </div>

            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <?php $product = get_product_by_id($item['product_id']); ?>
                        <div class="cart-item">
                            <input type="checkbox" class="item-checkbox" data-product-id="<?= $item['product_id'] ?>">

                            <img src="uploads/products/<?= $product['cover_image'] ?>" alt="">

                            <div class="item-details">
                                <div class="item-title"><?= htmlspecialchars($product['title']) ?></div>
                                <div class="item-price">RM<?= number_format($product['price'], 2) ?></div>
                                <?php $lineTotal = $product['price'] * $item['quantity']; ?>
                                <div class="item-total line-total" data-line-total="<?= number_format($lineTotal, 2, '.', '') ?>">
                                    Total: RM<?= number_format($lineTotal, 2) ?>
                                </div>
                                <div>
                                    Qty:
                                    <select class="qty-selector"
                                        data-product-id="<?= $item['product_id'] ?>"
                                        data-price="<?= number_format($product['price'], 2, '.', '') ?>"
                                        data-stock="<?= $product['stock_quantity'] ?>">

                                        <?php
                                        $max = $product['stock_quantity'];
                                        for ($i = 1; $i <= $max; $i++): ?>
                                            <option value="<?= $i ?>" <?= $i == $item['quantity'] ? 'selected' : '' ?>>
                                                <?= $i ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>

                                </div>
                            </div>

                            <div class="remove-btn" data-product-id="<?= $item['product_id'] ?>">×</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-line">
                        <span>Subtotal:</span>
                        <span id="cart-subtotal" class="summary-total">RM0.00</span>
                    </div>

                    <form id="checkout-form" action="payment.php" method="POST">
                        <input type="hidden" id="selected-items-input" name="selected_items" value="">
                        <button type="button" id="checkout-btn" class="checkout-btn">
                            PROCEED TO CHECKOUT
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Remove item
        document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (confirm('Remove this item from cart?')) {
                    const productId = btn.dataset.productId;
                    fetch('cart_delete.php?product_id=' + productId)
                        .then(res => res.text())
                        .then(data => {
                            if (data.includes('deleted')) {
                                location.reload();
                            } else {
                                alert('Error: Failed to delete');
                            }
                        })
                        .catch(err => {
                            console.error('Error:', err);
                            alert('Failed to delete item');
                        });
                }
            });
        });

        const qtySelectors = document.querySelectorAll('.qty-selector');
        const subtotalEl = document.getElementById('cart-subtotal');

        function updateSubtotal() {
            let subtotal = 0;
            document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
                const cartItem = checkbox.closest('.cart-item');
                const lineEl = cartItem.querySelector('.line-total');
                const amount = parseFloat(lineEl.dataset.lineTotal || '0');
                if (!isNaN(amount)) {
                    subtotal += amount;
                }
            });
            if (subtotalEl) {
                subtotalEl.textContent = 'RM' + subtotal.toFixed(2);
            }
        }

        qtySelectors.forEach(select => {
            select.addEventListener('change', event => {

                const price = parseFloat(event.target.dataset.price);
                const quantity = parseInt(event.target.value, 10);
                const productId = event.target.dataset.productId;
                const stock = parseInt(event.target.dataset.stock, 10);

                // ① 前端阻止超过库存
                if (quantity > stock) {
                    alert("Quantity exceeds stock! Maximum available: " + stock);
                    event.target.value = stock;
                    return;
                }

                // ② 前端更新 line total
                const lineTotal = price * quantity;
                const lineTotalEl = event.target.closest('.cart-item').querySelector('.line-total');
                lineTotalEl.dataset.lineTotal = lineTotal.toFixed(2);
                lineTotalEl.textContent = 'Total: RM' + lineTotal.toFixed(2);

                updateSubtotal();

                // ③ AJAX 发送到后端
                fetch('cart_update.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(res => res.text())
                    .then(res => {
                        if (res === "exceed_stock") {
                            alert("Server: Quantity exceeds stock. Please refresh.");
                            event.target.value = stock;
                        }
                    })
                    .catch(err => console.error(err));
            });
        });


        const selectAllCheckbox = document.getElementById('select-all');
        const itemCheckboxes = document.querySelectorAll('.item-checkbox');

        if (selectAllCheckbox && itemCheckboxes.length > 0) {
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateSubtotal();
            });

            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(itemCheckboxes).every(cb => cb.checked);
                    const someChecked = Array.from(itemCheckboxes).some(cb => cb.checked);

                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;

                    updateSubtotal();
                });
            });

            const checkoutBtn = document.getElementById('checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function() {
                    const selectedProductIds = [];
                    document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
                        selectedProductIds.push(checkbox.dataset.productId);
                    });

                    if (selectedProductIds.length === 0) {
                        alert('Please select at least one item to checkout');
                        return;
                    }

                    document.getElementById('selected-items-input').value = JSON.stringify(selectedProductIds);
                    document.getElementById('checkout-form').submit();
                });
            }

            updateSubtotal();
        }
    </script>
</body>

</html>

<?php include '../sb_foot.php'; ?>