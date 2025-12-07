<?php
$_title = 'Product Catalog - BookShop';
include '../sb_head.php';

// Database connection
try {
    $host = 'localhost';
    $dbname = 'sbonline';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get categories for filter
$categories_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY sort_order, name");
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Process filters and search
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? [];
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$sort = $_GET['sort'] ?? 'title_asc';
$page = $_GET['page'] ?? 1;

// Convert to integers
$page = max(1, (int)$page);
if (is_string($category_filter)) {
    $category_filter = [$category_filter];
}
$category_filter = array_map('intval', $category_filter);

// Build SQL query with filters
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";

$params = [];

// Search filter
if (!empty($search)) {
    $sql .= " AND (p.title LIKE :search OR p.author LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Category filter
if (!empty($category_filter)) {
    $placeholders = implode(',', array_fill(0, count($category_filter), '?'));
    $sql .= " AND p.category_id IN ($placeholders)";
    $params = array_merge($params, $category_filter);
}

// Price filter
if (!empty($min_price)) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}
if (!empty($max_price)) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}

// Sorting
$sort_options = [
    'title_asc' => 'p.title ASC',
    'title_desc' => 'p.title DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'date_desc' => 'p.publication_date DESC',
    'date_asc' => 'p.publication_date ASC'
];
$sql .= " ORDER BY " . ($sort_options[$sort] ?? 'p.title ASC');

// Pagination
$products_per_page = 6;
$offset = ($page - 1) * $products_per_page;

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM ($sql) as count_table";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();

// Add pagination to main query
$sql .= " LIMIT $products_per_page OFFSET $offset";

// Execute main query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total pages
$total_pages = ceil($total_products / $products_per_page);

////////// testing paging with loop //////////
// // TESTING: Reduce items per page to see pagination
// $products_per_page = 2; // Change this to test pagination

// // Build SQL query with filters
// $sql = "SELECT p.*, c.name as category_name 
//         FROM products p 
//         LEFT JOIN categories c ON p.category_id = c.id 
//         WHERE 1=1";

// $params = [];

// // Get total count for pagination
// $count_sql = "SELECT COUNT(*) FROM ($sql) as count_table";
// $count_stmt = $pdo->prepare($count_sql);
// $count_stmt->execute($params);
// $total_products = $count_stmt->fetchColumn();

// // SIMPLE LOOP: Generate test products if we have few real products
// $test_products = [];
// if ($total_products < 6) {
//     $test_titles = [
//         'The Great Gatsby',
//         'A Brief History of Time', 
//         'To Kill a Mockingbird',
//         'Sapiens: A Brief History',
//         'Steve Jobs Biography',
//         'The Lean Startup',
//         'The Selfish Gene',
//         'Guns, Germs, and Steel',
//         '1984',
//         'Pride and Prejudice'
//     ];
    
//     $test_authors = [
//         'F. Scott Fitzgerald',
//         'Stephen Hawking',
//         'Harper Lee', 
//         'Yuval Noah Harari',
//         'Walter Isaacson',
//         'Eric Ries',
//         'Richard Dawkins',
//         'Jared Diamond',
//         'George Orwell',
//         'Jane Austen'
//     ];
    
//     $test_prices = [12.99, 15.50, 14.25, 18.75, 20.00, 16.99, 13.99, 17.50, 11.99, 10.50];
    
//     // Get real products first
//     $offset = ($page - 1) * $products_per_page;
//     $main_sql = $sql . " LIMIT $products_per_page OFFSET $offset";
//     $stmt = $pdo->prepare($main_sql);
//     $stmt->execute($params);
//     $real_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
//     // Generate test products using simple loop
//     $products_needed = $products_per_page - count($real_products);
//     if ($products_needed > 0) {
//         $start_index = ($page - 1) * $products_per_page;
//         for ($i = 0; $i < $products_needed; $i++) {
//             $test_index = $start_index + $i;
//             if ($test_index < count($test_titles)) {
//                 $test_products[] = [
//                     'id' => 1000 + $test_index, // Fake ID
//                     'title' => $test_titles[$test_index],
//                     'author' => $test_authors[$test_index],
//                     'price' => $test_prices[$test_index],
//                     'category_id' => ($test_index % 5) + 1, // Cycle through categories 1-5
//                     'category_name' => 'Test Category',
//                     'stock_status' => 'in_stock',
//                     'cover_image' => 'https://via.placeholder.com/200x300/95a5a6/ffffff?text=Test+' . ($test_index + 1),
//                     'description' => 'Test product for pagination demonstration'
//                 ];
//             }
//         }
//     }
    
//     $products = array_merge($real_products, $test_products);
//     $total_products = 10; // Set fixed total for pagination
// } else {
//     // Normal case - enough real products
//     $offset = ($page - 1) * $products_per_page;
//     $main_sql = $sql . " LIMIT $products_per_page OFFSET $offset";
//     $stmt = $pdo->prepare($main_sql);
//     $stmt->execute($params);
//     $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
// }

// // Calculate total pages
// $total_pages = ceil($total_products / $products_per_page);

////////// End of testing paging with loop //////////


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_title; ?></title>
    <link rel="stylesheet" href="sb_style.css">
    <style>
        /* Additional styles for product view */
        .main-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            margin: 2rem 0;
        }
        
        /* Filter Sidebar */
        .filters-sidebar {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .filter-section {
            margin-bottom: 25px;
        }
        
        .filter-section h3 {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
            color: #2c3e50;
        }
        
        .category-list {
            list-style: none;
            padding: 0;
        }
        
        .category-item {
            margin-bottom: 8px;
        }
        
        .category-item label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .category-item input {
            margin-right: 10px;
        }
        
        .price-range-inputs {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .price-range-inputs input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-box {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        /* Products Section */
        .products-section {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .results-count {
            color: #666;
            font-size: 14px;
        }
        
        .sort-options {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .sort-options select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            height: 180px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-category {
            font-size: 12px;
            color: #3498db;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            height: 40px;
            overflow: hidden;
        }
        
        .product-author {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .stock-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .in-stock {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }
        
        .low-stock {
            background-color: rgba(241, 196, 15, 0.2);
            color: #f39c12;
        }
        
        .out-of-stock {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            margin-top: 30px;
            padding: 0;
        }
        
        .pagination li {
            margin: 0 5px;
        }
        
        .pagination a {
            display: block;
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #2c3e50;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .pagination a:hover {
            background-color: #ecf0f1;
        }
        
        .pagination .active a {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        /* Buttons */
        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #3498db;
            color: #3498db;
        }
        
        .btn-outline:hover {
            background-color: #3498db;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .filters-sidebar {
                position: static;
            }
            
            .products-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
        
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
    </style>
</head>
<body>
    <main class="container">        
        <div class="main-content">
            <!-- Filters Sidebar -->
            <aside class="filters-sidebar">
                <h2>Filters</h2>
                
                <form method="GET" class="filter-form" id="filterForm">
                    <!-- Search Box -->
                    <div class="filter-section">
                        <input type="text" class="search-box" name="search" placeholder="Search products..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="filter-section">
                        <h3>Categories</h3>
                        <ul class="category-list">
                            <?php foreach ($categories as $category): ?>
                                <li class="category-item">
                                    <label>
                                        <input type="checkbox" name="category[]" value="<?php echo $category['id']; ?>"
                                            <?php echo in_array($category['id'], $category_filter) ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                                    </label>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <!-- Price Range Filter -->
                    <div class="filter-section">
                        <h3>Price Range</h3>
                        <div class="price-range-inputs">
                            <input type="number" name="min_price" placeholder="Min" min="0" step="0.01"
                                   value="<?php echo htmlspecialchars($min_price); ?>">
                            <input type="number" name="max_price" placeholder="Max" min="0" step="0.01"
                                   value="<?php echo htmlspecialchars($max_price); ?>">
                        </div>
                    </div>
                    
                    <!-- Hidden fields for sort and page -->
                    <input type="hidden" name="sort" id="sortInput" value="<?php echo htmlspecialchars($sort); ?>">
                    <input type="hidden" name="page" id="pageInput" value="1">
                    
                    <!-- Action Buttons -->
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="product_view.php" class="btn btn-outline" style="text-align: center; display: block; text-decoration: none;">Reset Filters</a>
                </form>
            </aside>
            
            <!-- Products Section -->
            <section class="products-section">
                <div class="products-header">
                    <div class="results-count">
                        Showing <?php echo ($products_per_page * ($page - 1)) + 1; ?>-<?php 
                            echo min($products_per_page * $page, $total_products); 
                        ?> of <?php echo $total_products; ?> products
                    </div>
                    <div class="sort-options">
                        <label for="sortSelect">Sort by:</label>
                        <select id="sortSelect">
                            <option value="title_asc" <?php echo $sort == 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                            <option value="title_desc" <?php echo $sort == 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Price (Low to High)</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price (High to Low)</option>
                            <option value="date_desc" <?php echo $sort == 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="date_asc" <?php echo $sort == 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
                        </select>
                    </div>
                </div>
                
                <!-- Products Grid -->
                <div class="products-grid">
                    <?php if (empty($products)): ?>
                        <p style="text-align: center; grid-column: 1 / -1; padding: 2rem;">
                            No products found matching your criteria.
                        </p>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
 <div class="product-image">
    <?php
    // Get the correct image path
    $image_url = 'https://via.placeholder.com/200x300/95a5a6/ffffff?text=No+Image';
    
    if (!empty($product['cover_image'])) {
        // Check if it's already a full URL
        if (filter_var($product['cover_image'], FILTER_VALIDATE_URL)) {
            $image_url = $product['cover_image'];
        } else {
            // It's a filename - build the correct path
            $image_url = 'uploads/products/' . $product['cover_image'];
            
            // Check if file actually exists
            if (!file_exists($image_url)) {
                $image_url = 'https://via.placeholder.com/200x300/95a5a6/ffffff?text=Image+Missing';
            }
        }
    }
    ?>
    
    <img src="<?php echo htmlspecialchars($image_url); ?>" 
         alt="<?php echo htmlspecialchars($product['title']); ?>"
         onerror="this.src='https://via.placeholder.com/200x300/95a5a6/ffffff?text=Image+Error'">
</div>
                                <div class="product-info">
                                    <div class="product-category">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </div>
                                    <h3 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h3>
                                    <div class="product-author">by <?php echo htmlspecialchars($product['author']); ?></div>
                                    <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                                    <span class="stock-status <?php echo $product['stock_status']; ?>">
                                        <?php 
                                            switch($product['stock_status']) {
                                                case 'in_stock': echo 'In Stock'; break;
                                                case 'low_stock': echo 'Low Stock'; break;
                                                case 'out_of_stock': echo 'Out of Stock'; break;
                                            }
                                        ?>
                                    </span>
                                     <div class="product-actions" style="margin-top: 15px; display: flex; gap: 10px;">
        <a href="product_details.php?id=<?php echo $product['id']; ?>" 
           class="btn btn-outline" 
           style="padding: 8px 12px; text-decoration: none; font-size: 14px;">
            Details
        </a>
        <button class="btn btn-primary add-to-cart" 
                data-product-id="<?php echo $product['id']; ?>"
                data-product-title="<?php echo htmlspecialchars($product['title']); ?>"
                data-product-price="<?php echo $product['price']; ?>"
                style="padding: 8px 12px; font-size: 14px;"
                <?php echo $product['stock_status'] == 'out_of_stock' ? 'disabled' : ''; ?>>
            Add to Cart
        </button>
    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li>
                                <a href="<?php echo buildPaginationUrl($page - 1); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="<?php echo $i == $page ? 'active' : ''; ?>">
                                <a href="<?php echo buildPaginationUrl($i); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li>
                                <a href="<?php echo buildPaginationUrl($page + 1); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
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

        // Add to cart function
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

        // JavaScript for enhanced interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const sortSelect = document.getElementById('sortSelect');
            const sortInput = document.getElementById('sortInput');
            const filterForm = document.getElementById('filterForm');

            // Add event listeners to add-to-cart buttons
            const addToCartButtons = document.querySelectorAll('.add-to-cart');
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-product-id');
                    addToCart(productId);
                });
            });

            // Update sort when select changes
            sortSelect.addEventListener('change', function() {
                sortInput.value = this.value;
                document.getElementById('pageInput').value = 1; // Reset to page 1 when sorting
                filterForm.submit();
            });

            // Auto-submit form on search input (with debounce)
            let searchTimeout;
            const searchInput = document.querySelector('input[name="search"]');
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('pageInput').value = 1;
                    filterForm.submit();
                }, 500);
            });

            // Auto-submit form on price input (with debounce)
            let priceTimeout;
            const priceInputs = document.querySelectorAll('input[name="min_price"], input[name="max_price"]');
            priceInputs.forEach(input => {
                input.addEventListener('input', function() {
                    clearTimeout(priceTimeout);
                    priceTimeout = setTimeout(() => {
                        document.getElementById('pageInput').value = 1;
                        filterForm.submit();
                    }, 800);
                });
            });

            // Auto-submit form on category change
            const categoryCheckboxes = document.querySelectorAll('input[name="category[]"]');
            categoryCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    document.getElementById('pageInput').value = 1;
                    filterForm.submit();
                });
            });
        });
    </script>
</body>
</html>

<?php
// Helper function to build pagination URLs
function buildPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return 'product_view.php?' . http_build_query($params);
}

include '../sb_foot.php'; 
?>