<?php
include 'admin_sidebar.php';

// Database configuration
$host = 'localhost';
$dbname = 'sbonline';
$username = 'root';
$password = '';

// Initialize variables
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$message = '';
$error = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle actions
    switch ($action) {
        case 'restock':
            $productId = $_POST['product_id'] ?? '';
            $restockQuantity = $_POST['restock_quantity'] ?? '';
            
            if ($productId && $restockQuantity && is_numeric($restockQuantity) && $restockQuantity > 0) {
                // Get current stock
                $sql = "SELECT stock_quantity, low_stock_threshold FROM products WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$productId]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product) {
                    $newQuantity = $product['stock_quantity'] + $restockQuantity;
                    
                    // Update product stock and set needs_reorder to 'no' since we're restocking
                    $updateSql = "UPDATE products 
                                 SET stock_quantity = ?,
                                     stock_status = CASE 
                                         WHEN ? = 0 THEN 'out_of_stock'
                                         WHEN ? <= low_stock_threshold THEN 'low_stock'
                                         ELSE 'in_stock'
                                     END,
                                     needs_reorder = 'no',
                                     last_restocked = CURRENT_DATE
                                 WHERE id = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([$newQuantity, $newQuantity, $newQuantity, $productId]);
                    
                    $message = "Product restocked successfully! Added $restockQuantity units. Reorder status updated to YES (Already Restocked).";
                } else {
                    $error = "Product not found!";
                }
            } else {
                $error = "Invalid restock quantity!";
            }
            break;
            
        case 'update_threshold':
            $productId = $_POST['product_id'] ?? '';
            $newThreshold = $_POST['low_stock_threshold'] ?? '';
            
            if ($productId && $newThreshold && is_numeric($newThreshold) && $newThreshold >= 0) {
                // Update threshold and recalculate status
                $updateSql = "UPDATE products 
                             SET low_stock_threshold = ?,
                                 stock_status = CASE 
                                     WHEN stock_quantity = 0 THEN 'out_of_stock'
                                     WHEN stock_quantity <= ? THEN 'low_stock'
                                     ELSE 'in_stock'
                                 END,
                                 needs_reorder = CASE 
                                     WHEN stock_quantity <= ? THEN 'yes'
                                     ELSE 'no'
                                 END
                             WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$newThreshold, $newThreshold, $newThreshold, $productId]);
                
                $message = "Low stock threshold updated successfully!";
            } else {
                $error = "Invalid threshold value!";
            }
            break;
            
        case 'mark_restocked':
            $productId = $_POST['product_id'] ?? '';
            if ($productId) {
                // Mark as restocked (set needs_reorder to 'no')
                $updateSql = "UPDATE products SET needs_reorder = 'no' WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$productId]);
                
                $message = "Product marked as restocked! Reorder status updated to YES (Already Restocked).";
            }
            break;
            
        case 'mark_need_restock':
            $productId = $_POST['product_id'] ?? '';
            if ($productId) {
                // Mark as needing restock (set needs_reorder to 'yes')
                $updateSql = "UPDATE products SET needs_reorder = 'yes' WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([$productId]);
                
                $message = "Product marked as needing restock! Reorder status updated to NO (Need Restock).";
            }
            break;
    }

    // Fetch products needing reorder (needs_reorder = 'yes')
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.needs_reorder = 'yes' 
            ORDER BY p.stock_quantity ASC, p.title ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $reorderProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count by status for summary
    $countSql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN stock_status = 'out_of_stock' THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN stock_status = 'low_stock' THEN 1 ELSE 0 END) as low_stock
                FROM products 
                WHERE needs_reorder = 'yes'";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute();
    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Reorder Management - Stock Alert System</title>
    <style>
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .summary-cards { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin: 20px 0; 
        }
        .summary-card { 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        .summary-card.critical { border-left-color: #dc3545; }
        .summary-card.warning { border-left-color: #ffc107; }
        .summary-card h3 { margin: 0 0 10px 0; color: #495057; }
        .summary-card .count { 
            font-size: 2em; 
            font-weight: bold; 
            margin: 10px 0;
        }
        .summary-card.critical .count { color: #dc3545; }
        .summary-card.warning .count { color: #ffc107; }
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .table th, .table td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #dee2e6; 
        }
        .table th { 
            background: #f8f9fa; 
            font-weight: 600;
            color: #495057;
        }
        .table tr:hover { background: #f8f9fa; }
        
        .stock-indicator { 
            display: inline-block; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 12px; 
            font-weight: bold;
        }
        .status-out { background: #f8d7da; color: #721c24; }
        .status-low { background: #fff3cd; color: #856404; }
        .status-in { background: #d1edf1; color: #0c5460; }
        
        .btn { 
            padding: 6px 12px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-sm { padding: 4px 8px; font-size: 11px; }
        
        .action-form { display: inline-block; margin: 2px; }
        .quantity-input { 
            width: 60px; 
            padding: 4px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
        }
        .threshold-input { 
            width: 80px; 
            padding: 4px; 
            border: 1px solid #ddd; 
            border-radius: 4px;
        }
        
        .empty-state { 
            text-align: center; 
            padding: 40px; 
            color: #6c757d;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .empty-state i { 
            font-size: 48px; 
            margin-bottom: 10px; 
            display: block;
        }
        
        .product-image { 
            width: 40px; 
            height: 40px; 
            object-fit: cover; 
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .stock-progress {
            width: 100px;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            display: inline-block;
            margin-right: 8px;
        }
        .stock-progress-bar {
            height: 100%;
            background: #28a745;
            transition: width 0.3s ease;
        }
        .stock-progress-bar.low { background: #ffc107; }
        .stock-progress-bar.critical { background: #dc3545; }
        
        .reorder-status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .reorder-no { background: #f8d7da; color: #721c24; }
        .reorder-yes { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <div class="main-content container">
        <h1>📦 Product Reorder Management</h1>
        <p class="text-muted">Manage products that need restocking</p>

        <!-- Display messages -->
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card critical">
                <h3>🚨 Out of Stock</h3>
                <div class="count"><?php echo $counts['out_of_stock'] ?? 0; ?></div>
                <p>Products with zero stock</p>
            </div>
            <div class="summary-card warning">
                <h3>⚠️ Low Stock</h3>
                <div class="count"><?php echo $counts['low_stock'] ?? 0; ?></div>
                <p>Products below threshold</p>
            </div>
            <div class="summary-card">
                <h3>📋 Total Alerts</h3>
                <div class="count"><?php echo $counts['total'] ?? 0; ?></div>
                <p>Products needing restock</p>
            </div>
        </div>

        <!-- Reorder Products List -->
        <div class="reorder-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <h2>Products Needing Restock (Reorder Status: NO)</h2>
                <a href="product_panel.php" class="btn btn-primary">← Back to Products</a>
            </div>

            <?php if (empty($reorderProducts)): ?>
                <div class="empty-state">
                    <i>🎉</i>
                    <h3>No Restock Alerts!</h3>
                    <p>All products are adequately stocked. Great job!</p>
                    <a href="product_panel.php" class="btn btn-primary">Manage Products</a>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Current Stock</th>
                            <th>Threshold</th>
                            <th>Status</th>
                            <th>Reorder Status</th>
                            <th>Last Restocked</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reorderProducts as $product): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if (!empty($product['cover_image'])): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($product['cover_image']); ?>" 
                                                 alt="Cover" class="product-image"
                                                 onerror="this.src='https://via.placeholder.com/40?text=IMG'">
                                        <?php else: ?>
                                            <div class="product-image" style="background: #f8f9fa; display: flex; align-items: center; justify-content: center;">
                                                <span style="color: #6c757d; font-size: 10px;">NO IMG</span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($product['title']); ?></strong><br>
                                            <small style="color: #6c757d;">by <?php echo htmlspecialchars($product['author']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($product['category_name']): ?>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                            <?php echo htmlspecialchars($product['category_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="font-size: 1.1em; color: <?php echo $product['stock_quantity'] == 0 ? '#dc3545' : ($product['stock_quantity'] <= $product['low_stock_threshold'] ? '#ffc107' : '#28a745'); ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </strong>
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <?php
                                        $progress = min(100, ($product['stock_quantity'] / max($product['low_stock_threshold'], 1)) * 100);
                                        $progressClass = $product['stock_quantity'] <= $product['low_stock_threshold'] ? 'low' : ($product['stock_quantity'] == 0 ? 'critical' : '');
                                        ?>
                                        <div class="stock-progress">
                                            <div class="stock-progress-bar <?php echo $progressClass; ?>" 
                                                 style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="product_reorder.php" class="action-form">
                                        <input type="hidden" name="action" value="update_threshold">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="number" name="low_stock_threshold" value="<?php echo $product['low_stock_threshold']; ?>" 
                                               min="1" max="1000" class="threshold-input">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Update Threshold">📊</button>
                                    </form>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($product['stock_status']) {
                                        case 'out_of_stock':
                                            $status_class = 'status-out';
                                            $status_icon = '❌';
                                            break;
                                        case 'low_stock':
                                            $status_class = 'status-low';
                                            $status_icon = '⚠️';
                                            break;
                                        default:
                                            $status_class = 'status-in';
                                            $status_icon = '✅';
                                    }
                                    ?>
                                    <span class="stock-indicator <?php echo $status_class; ?>">
                                        <?php echo $status_icon; ?> <?php echo ucfirst(str_replace('_', ' ', $product['stock_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="reorder-status-badge reorder-no">
                                        ❌ NO - Need Restock
                                    </span>
                                </td>
                                <td>
                                    <?php echo $product['last_restocked'] ? date('M j, Y', strtotime($product['last_restocked'])) : '<span style="color: #6c757d;">Never</span>'; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <!-- Restock Form -->
                                        <form method="POST" action="product_reorder.php" class="action-form">
                                            <input type="hidden" name="action" value="restock">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="restock_quantity" 
                                                   value="<?php echo max(10, $product['low_stock_threshold'] * 2); ?>" 
                                                   min="1" max="1000" class="quantity-input">
                                            <button type="submit" class="btn btn-sm btn-success" title="Restock Product">📥 Restock</button>
                                        </form>
                                        
                                        <!-- Mark as Restocked -->
                                        <form method="POST" action="product_reorder.php" class="action-form">
                                            <input type="hidden" name="action" value="mark_restocked">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-info" title="Mark as Restocked">✅ Mark Restocked</button>
                                        </form>
                                        
                                        <!-- Edit Product -->
                                        <a href="product_panel.php?action=edit&id=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-warning" title="Edit Product">✏️ Edit</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Quick Actions -->
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                    <h4>📋 Quick Actions</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="product_panel.php" class="btn btn-primary">📊 View All Products</a>
                        <button type="button" class="btn btn-warning" onclick="window.print()">🖨️ Print Restock List</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-focus quantity input when restock button is clicked
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-success')) {
                const form = e.target.closest('form');
                if (form) {
                    const quantityInput = form.querySelector('.quantity-input');
                    if (quantityInput) {
                        setTimeout(() => quantityInput.focus(), 100);
                    }
                }
            }
        });

        // Confirm before marking as restocked
        document.addEventListener('submit', function(e) {
            if (e.target.querySelector('input[name="action"]')?.value === 'mark_restocked') {
                if (!confirm('Mark this product as restocked? This will change reorder status to YES (Already Restocked).')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>