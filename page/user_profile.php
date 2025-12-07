<?php
session_start();
require_once __DIR__ . '/../sb_base.php';

// check if user is already log in and is user or member (not admin)
if (!isset($_SESSION['user_id'])) {
    header('Location: /login/login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'user';

// link admin to admin profile
if ($userType === 'admin') {
    header('Location: /page/admin_profile.php');
    exit;
}

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

// Address Management
$addressAction = $_GET['address_action'] ?? '';
$addressId = $_GET['address_id'] ?? null;

// for add address or edit
if (isset($_POST['save_address'])) {
    $street = trim($_POST['street'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $zip = trim($_POST['zip_code'] ?? '');
    $isDefault = isset($_POST['is_default']) ? 1 : 0;
    $addrId = intval($_POST['address_id'] ?? 0);
    
    if (empty($street) || empty($city) || empty($state) || empty($zip)) {
        $error = 'Please fill in all required address fields';
    } else {
        // if set one default then unset others
        if ($isDefault) {
            $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
        
        if ($addrId > 0) {
            // update existing address
            $stmt = $pdo->prepare("UPDATE addresses SET street = ?, city = ?, address_state = ?, zip_code = ?, is_default = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$street, $city, $state, $zip, $isDefault, $addrId, $userId]);
            $message = 'Address updated successfully!';
        } else {
            // create new address
            $stmt = $pdo->prepare("INSERT INTO addresses (user_id, street, city, address_state, zip_code, is_default) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $street, $city, $state, $zip, $isDefault]);
            $message = 'Address added successfully!';
        }
    }
}

// for address delete
if ($addressAction === 'delete' && $addressId) {
    $stmt = $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$addressId, $userId]);
    $message = 'Address deleted successfully!';
}

// for set default address
if ($addressAction === 'set_default' && $addressId) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stmt = $pdo->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$addressId, $userId]);
        $pdo->commit();
        $message = 'Default address updated!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Failed to update default address';
    }
}

// get addresses
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();

// get address for editing
$editAddress = null;
if ($addressAction === 'edit' && $addressId) {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$addressId, $userId]);
    $editAddress = $stmt->fetch();
}

// get user data
$stmt = $pdo->prepare("SELECT id, username, email, profile_photo, user_role FROM $tableName WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /login/login.php');
    exit;
}

$userDisplayId = $user['id'];
$username = $user['username'];
$email = $user['email'];
$profilePhoto = $user['profile_photo'];
$userRole = $user['user_role'] ?? $userType ?? 'user';
$photoPath = $profilePhoto ? '/page/uploads/profiles/' . $profilePhoto : '';
if ($profilePhoto) {
    $_SESSION['profile_photo'] = $profilePhoto;
} else {
    unset($_SESSION['profile_photo']);
}

// User/Member layout with regular header
include __DIR__ . '/../sb_head.php';
?>
    <link rel="stylesheet" href="/css/user.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <main class="container">
        <div class="page-header" style="margin-top: 2rem;">
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
                        <label for="user_id">User ID</label>
                        <input type="text" id="user_id" class="form-control" 
                               value="<?= htmlspecialchars($userDisplayId) ?>" disabled readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="role">Role</label>
                        <input type="text" id="role" class="form-control" 
                               value="<?= htmlspecialchars(ucfirst($userRole)) ?>" disabled readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                    </div>
                </div>
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

        <!-- Address Management -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Addresses</h2>
                <?php if ($addressAction !== 'add' && !$editAddress): ?>
                    <a href="?address_action=add" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add Address
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($addressAction === 'add' || $editAddress): ?>
                <!-- Add/Edit Address Form -->
                <form method="POST">
                    <input type="hidden" name="address_id" value="<?= $editAddress['id'] ?? 0 ?>">
                    <div class="form-group">
                        <label for="street">Street Address *</label>
                        <input type="text" id="street" name="street" class="form-control" 
                               value="<?= htmlspecialchars($editAddress['street'] ?? '') ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group" style="flex:1;">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" class="form-control" 
                                   value="<?= htmlspecialchars($editAddress['city'] ?? '') ?>" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label for="state">State *</label>
                            <input type="text" id="state" name="state" class="form-control" 
                                   value="<?= htmlspecialchars($editAddress['address_state'] ?? '') ?>" required>
                        </div>
                        <div class="form-group" style="flex:1;">
                            <label for="zip_code">Zip Code *</label>
                            <input type="text" id="zip_code" name="zip_code" class="form-control" 
                                   value="<?= htmlspecialchars($editAddress['zip_code'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_default" value="1" 
                                   <?= ($editAddress['is_default'] ?? 0) ? 'checked' : '' ?>>
                            Set as default address
                        </label>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" name="save_address" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?= $editAddress ? 'Update Address' : 'Save Address' ?>
                        </button>
                        <a href="?" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <!-- Address List -->
                <?php if (empty($addresses)): ?>
                    <div class="empty-state">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>No addresses yet</h3>
                        <p>Add your first address to get started</p>
                        <a href="?address_action=add" class="btn btn-success mt-2">
                            <i class="fas fa-plus"></i> Add Address
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                        <div class="address-card <?= $addr['is_default'] ? 'default' : '' ?>">
                            <div class="address-header">
                                <div>
                                    <?php if ($addr['is_default']): ?>
                                        <span class="default-badge">Default</span>
                                    <?php endif; ?>
                                </div>
                                <div class="address-actions">
                                    <?php if (!$addr['is_default']): ?>
                                        <a href="?address_action=set_default&address_id=<?= $addr['id'] ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-star"></i> Set Default
                                        </a>
                                    <?php endif; ?>
                                    <a href="?address_action=edit&address_id=<?= $addr['id'] ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?address_action=delete&address_id=<?= $addr['id'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this address?');">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                            </div>
                            <div class="address-details">
                                <p><strong><?= htmlspecialchars($addr['street']) ?></strong></p>
                                <p><?= htmlspecialchars($addr['city']) ?>, <?= htmlspecialchars($addr['address_state']) ?> <?= htmlspecialchars($addr['zip_code']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

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
<?php
include __DIR__ . '/../sb_foot.php';
?>

