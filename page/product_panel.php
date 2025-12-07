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
$product = [
    'id' => '',
    'category_id' => '',
    'title' => '',
    'author' => '',
    'price' => '',
    'description' => '',
    'publisher' => '',
    'publication_date' => '',
    'pages' => '',
    'cover_image' => '',
    'images' => '[]',
    'stock_quantity' => '0',
    'low_stock_threshold' => '10',
    'stock_status' => 'in_stock',
    'needs_reorder' => 'no',
    'last_restocked' => ''
];

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch categories for dropdown
    $categories = [];
    $categoriesSql = "SELECT id, name FROM categories ORDER BY sort_order, name";
    $categoriesStmt = $pdo->prepare($categoriesSql);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle CRUD operations
    switch ($action) {
        case 'create':
            $product = $_POST;
            if (validateProduct($product)) {
                // Handle file uploads FIRST
                $uploadedFiles = handleMultipleFileUploads(0); // Use 0 as temp ID for new products
                
                if (empty($uploadedFiles)) {
                    $error = "At least one product image is required";
                    break;
                }
                
                // Set cover image as first uploaded image
                $coverImage = $uploadedFiles[0];
                
                // Store all images as JSON
                $imagesJson = json_encode($uploadedFiles);
                
                // Calculate initial stock status
                $stockQuantity = intval($product['stock_quantity']);
                $lowStockThreshold = intval($product['low_stock_threshold']);
                list($stockStatus, $needsReorder) = calculateStockStatus($stockQuantity, $lowStockThreshold);
                
                $sql = "INSERT INTO products (category_id, title, author, price, description, publisher, publication_date, pages, cover_image, images, stock_quantity, low_stock_threshold, stock_status, needs_reorder) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $product['category_id'] ?: null,
                    $product['title'],
                    $product['author'],
                    $product['price'],
                    $product['description'],
                    $product['publisher'],
                    $product['publication_date'],
                    $product['pages'] ?: null,
                    $coverImage,
                    $imagesJson,
                    $stockQuantity,
                    $lowStockThreshold,
                    $stockStatus,
                    $needsReorder
                ]);
                
                $productId = $pdo->lastInsertId();
                
                // Update filenames with actual product ID
                if ($productId && !empty($uploadedFiles)) {
                    updateImageFilenames($productId, $uploadedFiles);
                    
                    // Update the product record with new filenames
                    $newFilenames = array_map(function($file) use ($productId) {
                        return preg_replace('/^0_/', $productId . '_', $file);
                    }, $uploadedFiles);
                    
                    $newCoverImage = $newFilenames[0];
                    $newImagesJson = json_encode($newFilenames);
                    
                    $updateSql = "UPDATE products SET cover_image = ?, images = ? WHERE id = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([$newCoverImage, $newImagesJson, $productId]);
                }
                
                $message = "Product created successfully! Uploaded " . count($uploadedFiles) . " images.";
            }
            break;

        case 'update':
            $product = $_POST;
            if (validateProduct($product, false)) { // false = not required for updates
                // Get existing images
                $existingImages = json_decode($product['existing_images'] ?? '[]', true) ?: [];
                
                // Handle new file uploads
                $newUploadedFiles = handleMultipleFileUploads($product['id']);
                $allImages = array_merge($existingImages, $newUploadedFiles);
                
                // Set cover image (first image from combined array, or keep existing if empty)
                $coverImage = !empty($allImages) ? $allImages[0] : ($product['existing_cover_image'] ?? '');
                
                // Store all images as JSON
                $imagesJson = json_encode($allImages);
                
                // Calculate stock status
                $stockQuantity = intval($product['stock_quantity']);
                $lowStockThreshold = intval($product['low_stock_threshold']);
                list($stockStatus, $needsReorder) = calculateStockStatus($stockQuantity, $lowStockThreshold);
                
                $sql = "UPDATE products SET 
                        category_id = ?, title = ?, author = ?, price = ?, description = ?, 
                        publisher = ?, publication_date = ?, pages = ?, 
                        cover_image = ?, images = ?, stock_quantity = ?, low_stock_threshold = ?,
                        stock_status = ?, needs_reorder = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $product['category_id'] ?: null,
                    $product['title'],
                    $product['author'],
                    $product['price'],
                    $product['description'],
                    $product['publisher'],
                    $product['publication_date'],
                    $product['pages'] ?: null,
                    $coverImage,
                    $imagesJson,
                    $stockQuantity,
                    $lowStockThreshold,
                    $stockStatus,
                    $needsReorder,
                    $product['id']
                ]);
                
                $message = "Product updated successfully!" . 
                          (count($newUploadedFiles) ? " Added " . count($newUploadedFiles) . " new images." : "");
            }
            break;

        case 'delete':
            $id = $_GET['id'] ?? '';
            if ($id) {
                // First get images to delete files
                $sql = "SELECT images FROM products WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $productData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($productData) {
                    $images = json_decode($productData['images'] ?? '[]', true);
                    // Delete image files
                    foreach ($images as $image) {
                        $filePath = 'uploads/products/' . $image;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }
                
                // Then delete product
                $sql = "DELETE FROM products WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $message = "Product deleted successfully!";
            }
            break;

        case 'edit':
            $id = $_GET['id'] ?? '';
            if ($id) {
                $sql = "SELECT * FROM products WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$product) {
                    $error = "Product not found!";
                } else {
                    // Ensure images is always an array
                    $product['images'] = $product['images'] ?: '[]';
                    // Set default values for new fields
                    $product['low_stock_threshold'] = $product['low_stock_threshold'] ?? '10';
                    $product['stock_status'] = $product['stock_status'] ?? 'in_stock';
                    $product['needs_reorder'] = $product['needs_reorder'] ?? 'no';
                }
            }
            break;
            
        case 'delete_image':
            $productId = $_POST['product_id'] ?? '';
            $imageName = $_POST['image_name'] ?? '';
            if ($productId && $imageName) {
                // Get current images
                $sql = "SELECT images, cover_image FROM products WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$productId]);
                $productData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($productData) {
                    $images = json_decode($productData['images'] ?? '[]', true);
                    $coverImage = $productData['cover_image'];
                    
                    // Remove image from array
                    $newImages = array_filter($images, function($img) use ($imageName) {
                        return $img !== $imageName;
                    });
                    
                    // Reset array keys
                    $newImages = array_values($newImages);
                    
                    // Update cover image if deleted image was the cover
                    $newCoverImage = $coverImage;
                    if ($coverImage === $imageName && !empty($newImages)) {
                        $newCoverImage = $newImages[0];
                    } elseif ($coverImage === $imageName) {
                        $newCoverImage = '';
                    }
                    
                    // Update database
                    $updateSql = "UPDATE products SET images = ?, cover_image = ? WHERE id = ?";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([json_encode($newImages), $newCoverImage, $productId]);
                    
                    // Delete physical file
                    $filePath = 'uploads/products/' . $imageName;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    
                    $message = "Image deleted successfully!";
                }
            }
            break;
    }

    // Fetch all products for listing with category names
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            ORDER BY p.needs_reorder DESC, p.stock_quantity ASC, p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get reorder alert count for sidebar
    $alertCount = $pdo->query("SELECT COUNT(*) FROM products WHERE needs_reorder = 'yes'")->fetchColumn();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

function calculateStockStatus($stockQuantity, $lowStockThreshold) {
    if ($stockQuantity == 0) {
        return ['out_of_stock', 'yes'];
    } elseif ($stockQuantity <= $lowStockThreshold) {
        return ['low_stock', 'yes'];
    } else {
        return ['in_stock', 'no'];
    }
}

function handleMultipleFileUploads($productId) {
    $uploadedFiles = [];
    
    if (!empty($_FILES['product_images']['name'][0])) {
        $uploadDir = 'uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($_FILES['product_images']['name'] as $key => $name) {
            if ($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                $fileTmpName = $_FILES['product_images']['tmp_name'][$key];
                $fileSize = $_FILES['product_images']['size'][$key];
                $fileType = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $maxFileSize = 5 * 1024 * 1024; // 5MB
                
                if (in_array($fileType, $allowedTypes) && $fileSize <= $maxFileSize) {
                    $safeName = preg_replace('/[^a-zA-Z0-9\._-]/', '_', $name);
                    $fileName = $productId . '_' . uniqid() . '_' . $safeName;
                    $uploadFile = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($fileTmpName, $uploadFile)) {
                        $uploadedFiles[] = $fileName;
                    }
                }
            }
        }
    }
    return $uploadedFiles;
}

function updateImageFilenames($productId, $uploadedFiles) {
    foreach ($uploadedFiles as $oldFilename) {
        $newFilename = preg_replace('/^0_/', $productId . '_', $oldFilename);
        $oldPath = 'uploads/products/' . $oldFilename;
        $newPath = 'uploads/products/' . $newFilename;
        
        if (file_exists($oldPath)) {
            rename($oldPath, $newPath);
        }
    }
}

function validateProduct(&$product, $requireImages = true) {
    global $error;
    
    $errors = [];
    
    if (empty(trim($product['title']))) {
        $errors[] = "Title is required";
    }
    
    if (empty(trim($product['author']))) {
        $errors[] = "Author is required";
    }
    
    if (empty($product['price']) || !is_numeric($product['price']) || $product['price'] < 0) {
        $errors[] = "Valid price is required";
    }
    
    if (empty($product['stock_quantity']) || !is_numeric($product['stock_quantity']) || $product['stock_quantity'] < 0) {
        $errors[] = "Valid stock quantity is required";
    }
    
    if (empty($product['low_stock_threshold']) || !is_numeric($product['low_stock_threshold']) || $product['low_stock_threshold'] < 1) {
        $errors[] = "Valid low stock threshold is required (minimum 1)";
    }
    
    // For new products, require at least one image
    if ($requireImages && empty($product['id']) && (empty($_FILES['product_images']['name'][0]) || $_FILES['product_images']['error'][0] !== UPLOAD_ERR_OK)) {
        $errors[] = "At least one product image is required";
    }
    
    if (!empty($errors)) {
        $error = implode(", ", $errors);
        return false;
    }
    
    // Sanitize data
    $product['title'] = htmlspecialchars(trim($product['title']));
    $product['author'] = htmlspecialchars(trim($product['author']));
    $product['description'] = htmlspecialchars(trim($product['description']));
    $product['publisher'] = htmlspecialchars(trim($product['publisher']));
    $product['price'] = floatval($product['price']);
    $product['stock_quantity'] = intval($product['stock_quantity']);
    $product['low_stock_threshold'] = intval($product['low_stock_threshold']);
    $product['pages'] = $product['pages'] ? intval($product['pages']) : null;
    $product['category_id'] = $product['category_id'] ? intval($product['category_id']) : null;
    
    return true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <style>
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-success { background: #28a745; color: white; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .table th { background: #f8f9fa; }
        .actions { white-space: nowrap; }
        .category-badge { 
            background: #e9ecef; 
            padding: 3px 8px; 
            border-radius: 12px; 
            font-size: 12px; 
            color: #495057; 
        }
        .image-thumbnail { 
            max-width: 80px; 
            max-height: 80px; 
            margin: 2px; 
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .existing-images { 
            display: flex; 
            flex-wrap: wrap; 
            margin: 10px 0; 
            gap: 10px;
        }
        .image-item { 
            text-align: center; 
            position: relative;
            padding: 5px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
        }
        .cover-badge { 
            background: #007bff; 
            color: white; 
            padding: 2px 6px; 
            border-radius: 8px; 
            font-size: 10px; 
            margin-top: 5px;
        }
        .image-actions { margin-top: 5px; }

        /* Stock Status Styles */
        .stock-indicator { 
            display: inline-block; 
            padding: 4px 8px; 
            border-radius: 12px; 
            font-size: 11px; 
            font-weight: bold;
        }
        .status-out { background: #f8d7da; color: #721c24; }
        .status-low { background: #fff3cd; color: #856404; }
        .status-in { background: #d1edf1; color: #0c5460; }
        
        .reorder-badge { 
            background: #dc3545; 
            color: white; 
            padding: 2px 6px; 
            border-radius: 8px; 
            font-size: 10px; 
            margin-left: 5px;
        }
        
        .stock-progress {
            width: 80px;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }
        .stock-progress-bar {
            height: 100%;
            transition: width 0.3s ease;
        }
        .stock-progress-bar.in-stock { background: #28a745; }
        .stock-progress-bar.low-stock { background: #ffc107; }
        .stock-progress-bar.out-of-stock { background: #dc3545; }

        /* Drag and Drop Styles */
        .drop-zone {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .drop-zone:hover {
            background: #e9ecef;
            border-color: #0056b3;
        }
        .drop-zone.dragover {
            background: #d1ecf1;
            border-color: #17a2b8;
            border-style: solid;
        }
        .drop-zone-content {
            color: #6c757d;
        }
        .upload-icon {
            font-size: 48px;
            margin-bottom: 10px;
            display: block;
        }
        .drop-zone-text {
            display: block;
            margin: 10px 0;
            color: #6c757d;
        }
        .file-list {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            display: none;
        }
        .file-list-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            margin-bottom: 5px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .file-info {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        .file-icon { margin-right: 10px; font-size: 16px; }
        .file-name { flex-grow: 1; font-size: 14px; }
        .file-size { color: #6c757d; font-size: 12px; margin-left: 10px; }
        .remove-file {
            background: #dc3545; color: white; border: none; border-radius: 4px;
            padding: 4px 8px; cursor: pointer; font-size: 12px; margin-left: 10px;
        }
        .preview-container {
            display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;
        }
        .preview-item {
            position: relative; text-align: center; border: 2px solid transparent;
            border-radius: 8px; padding: 10px; background: #f8f9fa;
        }
        .preview-item.cover {
            border-color: #007bff; background: #e7f3ff;
        }
        .preview-image {
            max-width: 120px; max-height: 120px; border-radius: 4px; object-fit: cover;
        }
        .preview-info { margin-top: 8px; font-size: 12px; }
        .cover-badge-preview {
            background: #007bff; color: white; padding: 2px 8px;
            border-radius: 12px; font-size: 10px; margin-top: 5px;
        }
        .set-cover-btn {
            background: #28a745; color: white; border: none; border-radius: 4px;
            padding: 4px 8px; cursor: pointer; font-size: 11px; margin-top: 5px;
        }
        .remove-preview {
            position: absolute; top: 5px; right: 5px;
            background: rgba(220, 53, 69, 0.9); color: white; border: none;
            border-radius: 50%; width: 24px; height: 24px; cursor: pointer;
            font-size: 12px; display: flex; align-items: center; justify-content: center;
        }
        
        .stock-management-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        .stock-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .reorder-status-yes { color: #28a745; font-weight: bold; }
        .reorder-status-no { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="main-content container">
        <h1>Product Management</h1>

        <!-- Display messages -->
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Product Form -->
        <div class="form-section">
            <h2><?php echo empty($product['id']) ? 'Add New Product' : 'Edit Product'; ?></h2>
            <form method="POST" action="product_panel.php" enctype="multipart/form-data" id="product-form">
                <input type="hidden" name="action" value="<?php echo empty($product['id']) ? 'create' : 'update'; ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                <input type="hidden" name="existing_images" value="<?php echo htmlspecialchars($product['images'] ?? '[]'); ?>">
                <input type="hidden" name="existing_cover_image" value="<?php echo htmlspecialchars($product['cover_image'] ?? ''); ?>">
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- No Category --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo ($product['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($product['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="author">Author *</label>
                    <input type="text" id="author" name="author" required 
                           value="<?php echo htmlspecialchars($product['author']); ?>">
                </div>

                <div class="form-group">
                    <label for="price">Price *</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" required 
                           value="<?php echo htmlspecialchars($product['price']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="publisher">Publisher</label>
                    <input type="text" id="publisher" name="publisher" 
                           value="<?php echo htmlspecialchars($product['publisher']); ?>">
                </div>

                <div class="form-group">
                    <label for="publication_date">Publication Date</label>
                    <input type="date" id="publication_date" name="publication_date" 
                           value="<?php echo htmlspecialchars($product['publication_date']); ?>">
                </div>

                <div class="form-group">
                    <label for="pages">Pages</label>
                    <input type="number" id="pages" name="pages" min="1" 
                           value="<?php echo htmlspecialchars($product['pages']); ?>">
                </div>

                <!-- Stock Management Section -->
                <div class="stock-management-section">
                    <h3>📦 Stock Management</h3>
                    <div class="stock-fields">
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                                   value="<?php echo htmlspecialchars($product['stock_quantity']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="low_stock_threshold">Low Stock Threshold *</label>
                            <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="1" required 
                                   value="<?php echo htmlspecialchars($product['low_stock_threshold']); ?>">
                            <small>Alert when stock falls below this number</small>
                        </div>
                    </div>
                    
                    <?php if (!empty($product['id'])): ?>
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
                        <div style="margin-top: 10px; padding: 10px; background: white; border-radius: 4px;">
                            <strong>Current Status:</strong>
                            <span class="stock-indicator <?php echo $status_class; ?>">
                                <?php echo $status_icon; ?> 
                                <?php echo ucfirst(str_replace('_', ' ', $product['stock_status'])); ?>
                            </span>
                            <?php if ($product['needs_reorder'] == 'yes'): ?>
                                <span class="reorder-badge">NEEDS REORDER</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Display existing images for edit -->
                <?php if (!empty($product['id']) && !empty($product['images'])): ?>
                    <?php $existingImages = json_decode($product['images'], true); ?>
                    <?php if (!empty($existingImages)): ?>
                        <div class="form-group">
                            <label>Existing Images:</label>
                            <div class="existing-images">
                                <?php foreach ($existingImages as $index => $image): ?>
                                    <div class="image-item">
                                        <img src="uploads/products/<?php echo htmlspecialchars($image); ?>" 
                                             alt="Product image" class="image-thumbnail"
                                             onerror="this.src='https://via.placeholder.com/80?text=Image+Not+Found'">
                                        <div class="cover-badge">
                                            <?php echo $index === 0 ? '✓ COVER' : 'Image ' . ($index + 1); ?>
                                        </div>
                                        <?php if ($index > 0): ?>
                                            <div class="image-actions">
                                                <button type="button" class="btn-danger btn-sm" 
                                                        onclick="deleteImage(<?php echo $product['id']; ?>, '<?php echo $image; ?>')">
                                                    Delete
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="form-group">
                    <label for="product_images">
                        <?php echo empty($product['id']) ? 'Upload Product Images *' : 'Add More Images'; ?>
                    </label>
                    
                    <!-- Drag and Drop Area -->
                    <div id="drop-zone" class="drop-zone">
                        <div class="drop-zone-content">
                            <i class="upload-icon">📁</i>
                            <p>Drag & Drop your images here</p>
                            <span class="drop-zone-text">or</span>
                            <button type="button" class="btn-primary btn-sm" id="browse-btn">Browse Files</button>
                            <input type="file" id="product_images" name="product_images[]" 
                                   multiple accept="image/jpeg, image/png, image/gif, image/webp" 
                                   style="display: none;"
                                   <?php echo empty($product['id']) ? 'required' : ''; ?>>
                        </div>
                    </div>
                    
                    <small>The first image will be used as the cover image</small>
                    
                    <!-- Selected Files List -->
                    <div id="file-list" class="file-list" style="display: none;">
                        <h4>Selected Files:</h4>
                        <ul id="file-list-items"></ul>
                    </div>
                </div>

                <!-- Preview area for new images -->
                <div id="image-preview" style="margin-top: 10px; display: none;">
                    <h4>Preview:</h4>
                    <div id="preview-container" class="preview-container"></div>
                </div>

                <button type="submit" class="btn-primary">
                    <?php echo empty($product['id']) ? 'Create Product' : 'Update Product'; ?>
                </button>
                
                <?php if (!empty($product['id'])): ?>
                    <a href="product_panel.php" class="btn-warning">Cancel</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products List -->
        <div class="list-section">
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <h2>Products List</h2>
                <div>
                    <?php if ($alertCount > 0): ?>
                        <a href="product_reorder.php" class="btn btn-danger">
                            ⚠️ Reorder Alerts <span class="reorder-badge"><?php echo $alertCount; ?></span>
                        </a>
                    <?php endif; ?>
                    <a href="product_reorder.php" class="btn btn-primary">View Reorder List</a>
                </div>
            </div>
            
            <?php if (empty($products)): ?>
                <p>No products found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cover</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Reorder Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr style="<?php echo $p['needs_reorder'] == 'yes' ? 'background: #fff3cd;' : ''; ?>">
                                <td><?php echo htmlspecialchars($p['id']); ?></td>
                                <td>
                                    <?php if (!empty($p['cover_image'])): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($p['cover_image']); ?>" 
                                             alt="Cover" class="image-thumbnail"
                                             onerror="this.src='https://via.placeholder.com/80?text=No+Image'">
                                    <?php else: ?>
                                        <span style="color: #6c757d;">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                                    <?php if ($p['needs_reorder'] == 'yes'): ?>
                                        <span class="reorder-badge">!</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($p['author']); ?></td>
                                <td>
                                    <?php if ($p['category_name']): ?>
                                        <span class="category-badge"><?php echo htmlspecialchars($p['category_name']); ?></span>
                                    <?php else: ?>
                                        <span style="color: #6c757d;">No Category</span>
                                    <?php endif; ?>
                                </td>
                                <td>$<?php echo number_format($p['price'], 2); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <strong style="color: <?php echo $p['stock_quantity'] == 0 ? '#dc3545' : ($p['stock_quantity'] <= $p['low_stock_threshold'] ? '#ffc107' : '#28a745'); ?>">
                                            <?php echo $p['stock_quantity']; ?>
                                        </strong>
                                        <?php if ($p['stock_quantity'] > 0): ?>
                                            <?php
                                            $progress = min(100, ($p['stock_quantity'] / max($p['low_stock_threshold'], 1)) * 100);
                                            $progressClass = str_replace('_', '-', $p['stock_status']);
                                            ?>
                                            <div class="stock-progress">
                                                <div class="stock-progress-bar <?php echo $progressClass; ?>" 
                                                     style="width: <?php echo $progress; ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <small style="color: #6c757d;">Threshold: <?php echo $p['low_stock_threshold']; ?></small>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($p['stock_status']) {
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
                                        <?php echo $status_icon; ?> <?php echo ucfirst(str_replace('_', ' ', $p['stock_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['needs_reorder'] == 'yes'): ?>
                                        <span class="reorder-status-no">NO - Need Restock</span>
                                    <?php else: ?>
                                        <span class="reorder-status-yes">YES - Already Restocked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($p['created_at'])); ?></td>
                                <td class="actions">
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <a href="product_panel.php?action=edit&id=<?php echo $p['id']; ?>" 
                                           class="btn-warning btn-sm">Edit</a>
                                        <a href="product_panel.php?action=delete&id=<?php echo $p['id']; ?>" 
                                           class="btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Drag and Drop functionality
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('product_images');
        const browseBtn = document.getElementById('browse-btn');
        const fileList = document.getElementById('file-list');
        const fileListItems = document.getElementById('file-list-items');
        const previewContainer = document.getElementById('preview-container');
        const imagePreview = document.getElementById('image-preview');
        const productForm = document.getElementById('product-form');

        let uploadedFiles = [];

        // Browse button click
        browseBtn.addEventListener('click', () => {
            fileInput.click();
        });

        // File input change
        fileInput.addEventListener('change', handleFileSelection);

        // Drag and drop events
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dropZone.classList.add('dragover');
        }

        function unhighlight() {
            dropZone.classList.remove('dragover');
        }

        // Handle drop
        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            handleFiles(files);
        }

        function handleFileSelection(e) {
            const files = e.target.files;
            handleFiles(files);
        }

        function handleFiles(files) {
            if (files.length > 0) {
                // Convert FileList to Array and add to uploadedFiles
                const newFiles = Array.from(files);
                uploadedFiles = [...uploadedFiles, ...newFiles];
                
                // Update the file input (for form submission)
                updateFileInput();
                
                // Update UI
                updateFileList();
                updateImagePreview();
                
                // Show preview and file list
                imagePreview.style.display = 'block';
                fileList.style.display = 'block';
            }
        }

        function updateFileInput() {
            // Create a new DataTransfer object
            const dataTransfer = new DataTransfer();
            
            // Add all files to the DataTransfer object
            uploadedFiles.forEach(file => {
                dataTransfer.items.add(file);
            });
            
            // Update the file input
            fileInput.files = dataTransfer.files;
        }

        function updateFileList() {
            fileListItems.innerHTML = '';
            
            uploadedFiles.forEach((file, index) => {
                const listItem = document.createElement('li');
                listItem.className = 'file-item';
                
                const fileSize = formatFileSize(file.size);
                
                listItem.innerHTML = `
                    <div class="file-info">
                        <span class="file-icon">📷</span>
                        <span class="file-name">${file.name}</span>
                        <span class="file-size">${fileSize}</span>
                    </div>
                    <button type="button" class="remove-file" onclick="removeFile(${index})">Remove</button>
                `;
                
                fileListItems.appendChild(listItem);
            });
        }

        function updateImagePreview() {
            previewContainer.innerHTML = '';
            
            uploadedFiles.forEach((file, index) => {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const previewItem = document.createElement('div');
                    previewItem.className = `preview-item ${index === 0 ? 'cover' : ''}`;
                    previewItem.dataset.index = index;
                    
                    previewItem.innerHTML = `
                        <button type="button" class="remove-preview" onclick="removeFile(${index})">×</button>
                        <img src="${e.target.result}" alt="Preview" class="preview-image">
                        <div class="preview-info">
                            <div>${file.name}</div>
                            <div>${formatFileSize(file.size)}</div>
                            ${index === 0 ? 
                                '<div class="cover-badge-preview">Cover Image</div>' : 
                                `<button type="button" class="set-cover-btn" onclick="setAsCover(${index})">Set as Cover</button>`
                            }
                        </div>
                    `;
                    
                    previewContainer.appendChild(previewItem);
                };
                
                reader.readAsDataURL(file);
            });
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function removeFile(index) {
            if (confirm('Are you sure you want to remove this file?')) {
                uploadedFiles.splice(index, 1);
                updateFileInput();
                updateFileList();
                updateImagePreview();
                
                // Hide preview and file list if no files
                if (uploadedFiles.length === 0) {
                    imagePreview.style.display = 'none';
                    fileList.style.display = 'none';
                }
            }
        }

        function setAsCover(index) {
            if (index > 0) {
                // Move the selected file to the beginning of the array
                const [file] = uploadedFiles.splice(index, 1);
                uploadedFiles.unshift(file);
                
                updateFileInput();
                updateImagePreview();
            }
        }

        // Delete individual image from server
        function deleteImage(productId, imageName) {
            if (confirm('Are you sure you want to delete this image?')) {
                const formData = new FormData();
                formData.append('action', 'delete_image');
                formData.append('product_id', productId);
                formData.append('image_name', imageName);
                
                fetch('product_panel.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting image');
                });
            }
        }

        // Form validation for new products
        productForm.addEventListener('submit', function(e) {
            const productId = document.querySelector('input[name="id"]').value;
            if (uploadedFiles.length === 0 && !productId) {
                e.preventDefault();
                alert('Please upload at least one product image.');
                return false;
            }
        });
    </script>
</body>
</html>