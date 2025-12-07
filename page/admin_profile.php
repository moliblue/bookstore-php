<?php
session_start();
require_once __DIR__ . '/../sb_base.php';

// check if user is already log in and is adminZ?
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    header('Location: /login/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';
$error = '';
$tableName = 'users';

// for profile update
if (isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    if (empty($username) || empty($email)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM $tableName WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $userId]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
        } else {
            $stmt = $pdo->prepare("UPDATE $tableName SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $userId]);
            $message = 'Profile updated successfully!';
            $_SESSION['username'] = $username;
        }
    }
}

// for profile photo upload
if (isset($_POST['upload_photo']) && isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['photo'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $maxSize = 5 * 1024 * 1024;
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = 'Invalid file type. Only JPEG, PNG, and GIF are allowed.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'File size too large. Maximum size is 5MB.';
        } else {
            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $userId . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                $stmt = $pdo->prepare("SELECT profile_photo FROM $tableName WHERE id = ?");
                $stmt->execute([$userId]);
                $oldPhoto = $stmt->fetchColumn();
                if ($oldPhoto && file_exists(__DIR__ . '/uploads/profiles/' . $oldPhoto)) {
                    unlink(__DIR__ . '/uploads/profiles/' . $oldPhoto);
                }
                
                $stmt = $pdo->prepare("UPDATE $tableName SET profile_photo = ? WHERE id = ?");
                $stmt->execute([$filename, $userId]);
                $message = 'Profile photo uploaded successfully!';
                $_SESSION['profile_photo'] = $filename;
            } else {
                $error = 'Failed to upload file.';
            }
        }
    } else {
        $error = 'File upload error.';
    }
}

// for password update
if (isset($_POST['update_password'])) {
    $current = trim($_POST['current_password'] ?? '');
    $new = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    
    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'Please fill in all password fields';
    } else {
        $stmt = $pdo->prepare("SELECT password_hash FROM $tableName WHERE id = ?");
        $stmt->execute([$userId]);
        $dbPassword = $stmt->fetchColumn();
        
        if ($current !== $dbPassword) {
            $error = 'Current password incorrect!';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirm password do not match!';
        } else {
            $stmt = $pdo->prepare("UPDATE $tableName SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new, $userId]);
            $message = 'Password updated successfully!';
        }
    }
}

// get user data
$stmt = $pdo->prepare("SELECT username, email, profile_photo FROM $tableName WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /login/login.php');
    exit;
}

$username = $user['username'];
$email = $user['email'];
$profilePhoto = $user['profile_photo'];
$photoPath = $profilePhoto ? '/page/uploads/profiles/' . $profilePhoto : '';
if ($profilePhoto) {
    $_SESSION['profile_photo'] = $profilePhoto;
} else {
    unset($_SESSION['profile_photo']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SB | Admin Profile</title>
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
                <?php if ($photoPath): ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" alt="Profile Photo">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="admin-name"><?= htmlspecialchars($username) ?></div>
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
            <a href="admin_user.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span class="menu-text">Customers</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Reports</span>
            </a>
            <a href="admin_profile.php" class="menu-item active">
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
            <h1>Profile Settings</h1>
            <p class="subtitle">Manage your account information and preferences</p>
        </div>

        <?php if ($message): ?>
            <div class="message message-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message message-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Update Profile Details -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Profile Information</h2>
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?= htmlspecialchars($username) ?>" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= htmlspecialchars($email) ?>" required>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <!-- Upload Profile Photo -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Profile Photo</h2>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="profile-photo-container">
                    <?php if ($photoPath): ?>
                        <img id="photoPreview" src="<?= htmlspecialchars($photoPath) ?>" class="profile-photo-preview" alt="Profile Photo">
                    <?php else: ?>
                        <div class="profile-photo-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                        <img id="photoPreview" src="" class="profile-photo-preview" style="display:none;" alt="Profile Photo">
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="photo">Select New Profile Photo</label>
                    <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
                </div>
                <div class="action-buttons">
                    <button type="submit" name="upload_photo" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Photo
                    </button>
                </div>
            </form>
        </div>

        <!-- Update Password -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Change Password</h2>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:1;">
                        <label for="new_password">New Password *</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="action-buttons">
                    <button type="submit" name="update_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Preview image before upload
    const photoInput = document.getElementById('photo');
    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    let preview = document.getElementById('photoPreview');
                    if (!preview) {
                        const container = document.querySelector('.profile-photo-container');
                        preview = document.createElement('img');
                        preview.id = 'photoPreview';
                        preview.className = 'profile-photo-preview';
                        container.innerHTML = '';
                        container.appendChild(preview);
                    }
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    }
</script>
</body>
</html>

