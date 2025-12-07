<?php
// Database configuration
$host = 'localhost';
$dbname = 'sbonline';
$username = 'root';
$password = '';

// Initialize variables
$product = null;
$productImages = [];
$relatedProducts = [];
$error = '';

// Check if product ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $error = "Product ID is required.";
} else {
    $productId = intval($_GET['id']);
    
    try {
        // Create PDO connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch product details with category information
        $sql = "SELECT p.*, c.name as category_name, c.id as category_id 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $error = "Product not found.";
        } else {
            // Get product images from JSON field (single-table approach)
            $productImages = json_decode($product['images'] ?? '[]', true);
            
            // If no images in JSON but cover_image exists, use that
            if (empty($productImages) && !empty($product['cover_image'])) {
                $productImages = [$product['cover_image']];
            }

            // Fetch related products (same category)
            if (!empty($product['category_id'])) {
                $relatedSql = "SELECT p.*, c.name as category_name 
                              FROM products p 
                              LEFT JOIN categories c ON p.category_id = c.id 
                              WHERE p.category_id = ? AND p.id != ? 
                              ORDER BY RAND() 
                              LIMIT 4";
                $relatedStmt = $pdo->prepare($relatedSql);
                $relatedStmt->execute([$product['category_id'], $productId]);
                $relatedProducts = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // If no related products from same category, get random products
            if (empty($relatedProducts)) {
                $randomSql = "SELECT p.*, c.name as category_name 
                             FROM products p 
                             LEFT JOIN categories c ON p.category_id = c.id 
                             WHERE p.id != ? 
                             ORDER BY RAND() 
                             LIMIT 4";
                $randomStmt = $pdo->prepare($randomSql);
                $randomStmt->execute([$productId]);
                $relatedProducts = $randomStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

$_title = $product ? htmlspecialchars($product['title']) : 'Product Details';
include '../sb_head.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_title; ?> - Book Shop</title>
    <link rel="stylesheet" href="/css/product.css">
</head>
<body>
    <div class="product-details-container">
        <?php if ($error): ?>
            <div class="error-message">
                <h3>Error</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="product.php" class="back-button">Back to Products</a>
            </div>
        <?php elseif ($product): ?>
            <!-- Back Button -->
            <a href="product_view.php" class="back-button">&larr; Back to Products</a>

            <!-- Product Details -->
            <div class="product-details">
                <!-- Product Images -->
                <div class="product-images">
                    <div class="main-image">
                        <?php if (!empty($productImages)): ?>
                            <img src="uploads/products/<?php echo htmlspecialchars($productImages[0]); ?>" 
                                 alt="<?php echo htmlspecialchars($product['title']); ?>" 
                                 id="mainProductImage">
                        <?php else: ?>
                            <div class="no-image" style="height: 400px; display: flex; align-items: center; justify-content: center; background: #f8f9fa; border-radius: 8px;">
                                No Image Available
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($productImages) > 1): ?>
                        <div class="image-gallery">
                            <?php foreach ($productImages as $index => $image): ?>
                                <div class="gallery-thumb <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     onclick="changeMainImage('<?php echo htmlspecialchars($image); ?>', this)">
                                    <img src="uploads/products/<?php echo htmlspecialchars($image); ?>" 
                                         alt="<?php echo htmlspecialchars($product['title']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Information -->
                <div class="product-info">
                    <!-- Category Badge -->
                    <?php if (!empty($product['category_name'])): ?>
                        <div class="product-category category-highlight">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </div>
                    <?php endif; ?>

                    <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                    <p class="product-author">by <?php echo htmlspecialchars($product['author']); ?></p>
                    
                    <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>

                    <!-- Stock Information -->
                    <div class="stock-info">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <span class="in-stock">✓ In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                        <?php else: ?>
                            <span class="out-of-stock">✗ Out of Stock</span>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <button class="add-to-cart-btn" 
                                onclick="addToCart(<?php echo $product['id']; ?>)">
                                Add to Cart
                            </button>
                            <button class="buy-now-btn" 
                                onclick="buyNow(<?php echo $product['id']; ?>)">
                                Buy Now
                            </button>
                        <?php else: ?>
                            <button class="add-to-cart-btn" disabled>Out of Stock</button>
                            <button class="buy-now-btn" disabled>Notify Me</button>
                        <?php endif; ?>
                    </div>

                    <!-- Product Meta Details -->
                    <div class="product-meta-details">
                        <div class="meta-item">
                            <span class="meta-label">Publisher:</span>
                            <span class="meta-value">
                                <?php echo !empty($product['publisher']) ? htmlspecialchars($product['publisher']) : 'Not specified'; ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Publication Date:</span>
                            <span class="meta-value">
                                <?php echo !empty($product['publication_date']) ? date('F j, Y', strtotime($product['publication_date'])) : 'Not specified'; ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Pages:</span>
                            <span class="meta-value">
                                <?php echo !empty($product['pages']) ? number_format($product['pages']) : 'Not specified'; ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value">
                                <?php echo !empty($product['category_name']) ? htmlspecialchars($product['category_name']) : 'Uncategorized'; ?>
                            </span>
                        </div>
                        
                        <div class="meta-item">
                            <span class="meta-label">ISBN/ID:</span>
                            <span class="meta-value">#<?php echo $product['id']; ?></span>
                        </div>
                    </div>

                    <!-- Full Description -->
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-description-full">
                            <h3>Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>



        <?php endif; ?>
    </div>

    <script>
        // Change main product image
        function changeMainImage(imagePath, element) {
            const mainImage = document.getElementById('mainProductImage');
            if (mainImage) {
                mainImage.src = 'uploads/products/' + imagePath;
            }
            
            // Update active thumbnail
            document.querySelectorAll('.gallery-thumb').forEach(thumb => {
                thumb.classList.remove('active');
            });
            element.classList.add('active');
        }

        // Update cart badge in header
        function updateCartBadge(cartCount) {
            // Find the cart badge element
            const cartBadge = document.querySelector('.cart-badge');
            const cartIconLink = document.querySelector('.cart-icon-link');

            if (cartCount > 0) {
                if (cartBadge) {
                    // Update existing badge
                    cartBadge.textContent = cartCount;
                } else if (cartIconLink) {
                    // Create new badge if it doesn't exist
                    const newBadge = document.createElement('span');
                    newBadge.className = 'cart-badge';
                    newBadge.textContent = cartCount;
                    cartIconLink.appendChild(newBadge);
                }
            } else if (cartBadge) {
                // Remove badge if cart is empty
                cartBadge.remove();
            }
        }

        // Add to cart functionality
        function addToCart(productId) {
            fetch('cart_add.php', {
                method: 'POST',
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `product_id=${productId}&qty=1`
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    alert('Product added to cart!');
                    // Update cart badge without page reload
                    updateCartBadge(data.cartCount);
                } else {
                    alert(data.message || 'Unable to add to cart.');
                }
            })
            .catch(() => alert('Unable to add to cart.'));
        }

        // Buy now functionality
        function buyNow(productId) {
            fetch('cart_add.php', {
                method: 'POST',
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `product_id=${productId}&qty=1`
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    // Update cart badge before redirecting
                    updateCartBadge(data.cartCount);
                    window.location.href = 'cart_view.php';
                } else {
                    alert(data.message || 'Unable to add to cart.');
                }
            })
            .catch(() => alert('Unable to add to cart.'));
        }

        // Keyboard navigation for image gallery
        document.addEventListener('keydown', function(e) {
            const thumbnails = document.querySelectorAll('.gallery-thumb');
            if (thumbnails.length <= 1) return;

            const activeThumb = document.querySelector('.gallery-thumb.active');
            let currentIndex = Array.from(thumbnails).indexOf(activeThumb);

            if (e.key === 'ArrowRight') {
                e.preventDefault();
                currentIndex = (currentIndex + 1) % thumbnails.length;
                thumbnails[currentIndex].click();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                currentIndex = (currentIndex - 1 + thumbnails.length) % thumbnails.length;
                thumbnails[currentIndex].click();
            }
        });
    </script>
</body>
</html>
<?php
include '../sb_foot.php';