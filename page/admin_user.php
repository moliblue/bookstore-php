<?php
session_start();
require_once __DIR__ . '/../sb_base.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: /login/login.php');
    exit;
}

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$userId = $_GET['id'] ?? null;

// Get current admin user's profile photo for sidebar
$currentAdminId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
$stmt->execute([$currentAdminId]);
$adminPhoto = $stmt->fetchColumn();
$adminPhotoPath = $adminPhoto ? '/page/uploads/profiles/' . $adminPhoto : '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'list';
    
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields';
        } else {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists';
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, user_role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, $email, $password, $role]);
                $message = 'Customer created successfully!';
                $action = 'list';
            }
        }
    } elseif ($action === 'update') {
        $id = intval($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'user');
        $password = trim($_POST['password'] ?? '');
        
        if (empty($username) || empty($email)) {
            $error = 'Please fill in all required fields';
        } else {
            // Check if username or email already exists (excluding current user)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $id]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists';
            } else {
                if (!empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, user_role = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $password, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, user_role = ? WHERE id = ?");
                    $stmt->execute([$username, $email, $role, $id]);
                }
                $message = 'Customer updated successfully!';
                $action = 'list';
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Customer deleted successfully!';
        }
        $action = 'list';
    }
}

// Get search term
$search = trim($_GET['search'] ?? '');
$searchQuery = '';
$searchParams = [];

// Build query to exclude admins (only show users and members)
if (!empty($search)) {
    $searchQuery = "WHERE user_role IN ('user', 'member') AND (username LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $searchParams = [$searchTerm, $searchTerm];
} else {
    $searchQuery = "WHERE user_role IN ('user', 'member')";
}

// Get users list (only users and members, exclude admins)
$users = [];
if ($action === 'list') {
    $sql = "SELECT id, username, email, user_role, profile_photo, created_at FROM users $searchQuery ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($searchParams);
    $users = $stmt->fetchAll();
}

// Get single user for edit/view (only users and members, exclude admins)
$user = null;
if ($action === 'edit' || $action === 'view') {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT id, username, email, user_role, profile_photo, created_at, updated_at FROM users WHERE id = ? AND user_role IN ('user', 'member')");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            $error = 'User not found or cannot be managed';
            $action = 'list';
        }
    } else {
        $action = 'list';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SB | Member Management</title>
<link rel="stylesheet" href="/css/sb_style.css">
<link rel="stylesheet" href="/css/user.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div style="display: flex; min-height: 100vh;">
   <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <span class="logo-text">Chapter One</span>
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
            <a href="adminPanel.php" class="menu-item">
                <i class="fas fa-tachometer-alt"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="product_panel.php" class="menu-item">
                <i class="fas fa-book"></i>
                <span class="menu-text">Books</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-tags"></i>
                <span class="menu-text">Categories</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-shopping-cart"></i>
                <span class="menu-text">Orders</span>
            </a>
            <a href="admin_user.php" class="menu-item active">
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

<!-- Main Content -->
<div class="main-content">
    <div class="page-header">
        <h1>Customer Management</h1>
        <p class="subtitle">Manage users and customers</p>
    </div>
    
    <?php if ($action === 'list'): ?>
        <a href="?action=create" class="btn btn-success" style="margin-bottom: 7px;">
            <i class="fas fa-plus"></i> Add New Customer
        </a>
    <?php else: ?>
        <a href="?" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    <?php endif; ?>

    <?php if ($action === 'list'): ?>
        <!-- Customer Listing -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Customer Listing</h2>
            </div>
            
            <?php if ($message): ?>
                <div class="message message-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message message-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="GET" class="search-box">
                <input type="text" name="search" placeholder="Search by username or email..." value="<?= htmlspecialchars($search) ?>" class="form-control">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($search): ?>
                    <a href="?" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">No members found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= htmlspecialchars($u['id']) ?></td>
                                    <td><?= htmlspecialchars($u['username']) ?></td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td>
                                        <span class="status status-<?= $u['user_role'] ?>">
                                            <?= ucfirst($u['user_role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y-m-d H:i', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?action=view&id=<?= $u['id'] ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <form method="POST" style="display: inline-block; margin: 0; padding: 0; vertical-align: middle;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" style="margin: 0; padding: 8px 15px; min-height: 36px; display: inline-flex; align-items: center;">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($action === 'view' && $user): ?>
        <!-- Member Detail -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Customer Details</h2>
            </div>
            
            <div class="user-detail">
                <div class="user-avatar">
                    <?php if ($user['profile_photo']): ?>
                        <img src="/page/uploads/profiles/<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile Photo">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h3><?= htmlspecialchars($user['username']) ?></h3>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Role:</strong> 
                        <span class="status status-<?= $user['user_role'] ?>">
                            <?= ucfirst($user['user_role']) ?>
                        </span>
                    </p>
                    <p><strong>Created:</strong> <?= date('Y-m-d H:i:s', strtotime($user['created_at'])) ?></p>
                    <p><strong>Updated:</strong> <?= date('Y-m-d H:i:s', strtotime($user['updated_at'])) ?></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="?action=edit&id=<?= $user['id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-edit"></i> Edit User
                </a>
            </div>
        </div>

    <?php elseif ($action === 'create' || ($action === 'edit' && $user)): ?>
        <!-- Create/Edit Form -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title"><?= $action === 'create' ? 'Create New Customer' : 'Edit Customer' ?></h2>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="<?= $action === 'edit' ? 'update' : $action ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" class="form-control" required>
                        <option value="user" <?= ($user['user_role'] ?? '') === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="member" <?= ($user['user_role'] ?? '') === 'member' ? 'selected' : '' ?>>Member</option>
                    </select>
                    <small style="color: #7f8c8d;">Note: Admin role cannot be assigned through customer management</small>
                </div>
                
                <div class="form-group">
                    <label for="password"><?= $action === 'create' ? 'Password *' : 'New Password (leave blank to keep current)' ?></label>
                    <input type="password" id="password" name="password" class="form-control" 
                           <?= $action === 'create' ? 'required' : '' ?>>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?= $action === 'create' ? 'Create Customer' : 'Update Customer' ?>
                    </button>
                    <a href="?" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
</div>
</body>
</html>
