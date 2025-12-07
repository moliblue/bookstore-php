<?php
session_start();
require_once __DIR__ . '/../sb_base.php';

// Get current admin user's profile photo for sidebar
$currentAdminId = $_SESSION['user_id'] ?? null;
$adminPhotoPath = '';
if ($currentAdminId) {
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
    $stmt->execute([$currentAdminId]);
    $adminPhoto = $stmt->fetchColumn();
    $adminPhotoPath = $adminPhoto ? '/page/uploads/profiles/' . $adminPhoto : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB Online | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <span class="logo-text">SB Online</span>
        </div>
        
        <div class="admin-info">
            <div class="admin-avatar">
                <?php if ($adminPhotoPath): ?>
                    <img src="<?= htmlspecialchars($adminPhotoPath) ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="admin-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin User') ?></div>
            <div class="admin-role">Administrator</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="/page/adminPanel.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="product_panel.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span class="menu-text">Books</span>
            </a>
                        <a href="product_reorder.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span class="menu-text">Restock</span>
            </a>
            <a href="product_category.php" class="menu-item">
                <i class="fas fa-tags"></i>
                <span class="menu-text">Categories</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                <span class="menu-text">Orders</span>
            </a>
            <a href="admin_user.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span class="menu-text">Customers</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Reports</span>
            </a>
            <a href="admin_profile.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span class="menu-text">Settings</span>
            </a>
            <a href="/login/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span class="menu-text">Logout</span>
            </a>
        </div>
    </div>

    </div>
</body>
</html>