<?php
include 'admin_sidebar.php';

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

// Handle form actions
$action = $_POST['action'] ?? '';
$message = '';
$message_type = '';

if ($action === 'create') {
    // Create new category
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $sort_order = (int)$_POST['sort_order'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$name, $description, $sort_order]);
        $message = "Category created successfully!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Error creating category: " . $e->getMessage();
        $message_type = "error";
    }
} elseif ($action === 'update') {
    // Update category
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $sort_order = (int)$_POST['sort_order'];
    
    try {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, sort_order = ? WHERE id = ?");
        $stmt->execute([$name, $description, $sort_order, $id]);
        $message = "Category updated successfully!";
        $message_type = "success";
    } catch (PDOException $e) {
        $message = "Error updating category: " . $e->getMessage();
        $message_type = "error";
    }
} elseif ($action === 'delete') {
    // Delete category
    $id = (int)$_POST['id'];
    
    try {
        // Check if category has products
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $check_stmt->execute([$id]);
        $product_count = $check_stmt->fetchColumn();
        
        if ($product_count > 0) {
            $message = "Cannot delete category. There are $product_count products associated with it.";
            $message_type = "error";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Category deleted successfully!";
            $message_type = "success";
        }
    } catch (PDOException $e) {
        $message = "Error deleting category: " . $e->getMessage();
        $message_type = "error";
    }
}

// Fetch all categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order, name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category for editing
$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $_title; ?></title>
    <style>
        body{
            margin:0;
            padding: 0;
        }

        main.container{
            margin-left: 270px;
        }

        .management-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 2rem 0;
        }

        .form-section, .list-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.3);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #3498db;
            color: #3498db;
        }
        
        .btn-outline:hover {
            background: #3498db;
            color: white;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .category-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .category-table th,
        .category-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .category-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .category-table tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .message {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .management-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <main class="container">
        <section class="hero">
            <h1>Category Management</h1>
            <p>Manage your product categories</p>
        </section>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="management-container">
            <!-- Add/Edit Form -->
            <div class="form-section">
                <h2><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h2>
                
                <form method="POST" id="categoryForm">
                    <?php if ($edit_category): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $edit_category['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="create">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" 
                               required maxlength="255">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="4"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="sort_order">Sort Order</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" 
                               value="<?php echo $edit_category['sort_order'] ?? 0; ?>" min="0">
                        <small style="color: #7f8c8d;">Lower numbers appear first</small>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $edit_category ? 'Update Category' : 'Create Category'; ?>
                        </button>
                        
                        <?php if ($edit_category): ?>
                            <a href="product_category.php" class="btn btn-outline">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Categories List -->
            <div class="list-section">
                <h2>Existing Categories</h2>
                
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <div>📁</div>
                        <h3>No Categories Found</h3>
                        <p>Create your first category to get started</p>
                    </div>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="category-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Sort Order</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                            <?php if ($category['description']): ?>
                                                <br><small style="color: #7f8c8d;"><?php echo htmlspecialchars($category['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $category['sort_order']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($category['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="product_category.php?edit=<?php echo $category['id']; ?>" 
                                               class="btn btn-outline btn-sm">Edit</a>
                                            <form method="POST" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this category?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <small style="color: #7f8c8d;">
                            <strong>Total Categories:</strong> <?php echo count($categories); ?>
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // Form validation
        document.getElementById('categoryForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            
            if (!name) {
                e.preventDefault();
                alert('Category name is required');
                document.getElementById('name').focus();
                return false;
            }
            
            if (name.length > 255) {
                e.preventDefault();
                alert('Category name must be less than 255 characters');
                document.getElementById('name').focus();
                return false;
            }
        });
        
        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>