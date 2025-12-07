<?php
session_start();
include '../sb_base.php';

$userId = 1; // can set who login

// user update
if(isset($_POST['update_profile'])){
    $username = $_POST['username'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE users SET username=?, email=? WHERE id=?");
    $stmt->bind_param("ssi", $username, $email, $userId);
    $stmt->execute();
    $stmt->close();
}

// update password
if(isset($_POST['update_password'])){
    $current = trim($_POST['current_password']); // 用户输入的原密码
    $new = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);


    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id=?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($dbPassword);
    $stmt->fetch();
    $stmt->close();

    if($current !== $dbPassword){
        $passwordMessage = "Current password incorrect!";
    } elseif($new !== $confirm){
        $passwordMessage = "New password and confirm password do not match!";
    } else {
        
        $stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");
        $stmt->bind_param("si", $new, $userId);
        $stmt->execute();
        $stmt->close();
        $passwordMessage = "Password updated successfully!";
    }
}


$stmt = $conn->prepare("SELECT username, email FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chapter One | Admin Panel</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Georgia", serif;
        }

        body {
            background-color: #f9f7f2;
            color: #333;
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            transition: all 0.3s;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 1.5rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-info {
            padding: 1.5rem 1rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #ecf0f1;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #2c3e50;
            font-size: 2rem;
        }

        .admin-name {
            font-weight: bold;
            margin-bottom: 0.25rem;
        }

        .admin-role {
            color: #bdc3c7;
            font-size: 0.9rem;
        }

        .sidebar-menu {
            padding: 1rem 0;
        }

        .menu-item {
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            transition: background-color 0.3s;
        }

        .menu-item:hover, .menu-item.active {
            background-color: #34495e;
        }

        .menu-item i {
            margin-right: 0.8rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 1.5rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #ddd;
        }

        .page-title {
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: #e74c3c;
            color: white;
        }

        .btn-secondary {
            background-color: #ecf0f1;
            color: #2c3e50;
        }

        /* Dashboard Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 4px;
            padding: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.5rem;
        }

        .books-icon {
            background-color: rgba(52, 152, 219, 0.2);
            color: #3498db;
        }

        .sales-icon {
            background-color: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .users-icon {
            background-color: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
        }

        .revenue-icon {
            background-color: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 0.25rem;
        }

        .stat-info p {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        /* Content Sections */
        .content-section {
            background: white;
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #ecf0f1;
        }

        .section-title {
            font-size: 1.4rem;
            color: #2c3e50;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .status {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-active {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
        }

        .status-pending {
            background-color: rgba(241, 196, 15, 0.2);
            color: #f39c12;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.3rem 0.6rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }

        .edit-btn {
            background-color: #3498db;
            color: white;
        }

        .delete-btn {
            background-color: #e74c3c;
            color: white;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
            }

            .sidebar .logo-text, 
            .sidebar .menu-text,
            .admin-info .admin-name,
            .admin-info .admin-role {
                display: none;
            }

            .main-content {
                margin-left: 70px;
            }

            .admin-avatar {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .form-row {
                flex-direction: column;
            }
        }
   
</style>
</head>
<body>
   <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <span class="logo-text">Chapter One</span>
        </div>
        
        <div class="admin-info">
            <div class="admin-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="admin-name">Admin User</div>
            <div class="admin-role">Administrator</div>
        </div>
        
        <div class="sidebar-menu">
            <a href="#" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="#" class="menu-item">
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
            <a href="#" class="menu-item">
                <i class="fas fa-users"></i>
                <span class="menu-text">Customers</span>
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-chart-bar"></i>
                <span class="menu-text">Reports</span>
            </a>
            <a href="profileupdate.php" class="menu-item">
                <i class="fas fa-cog"></i>
                <span class="menu-text">Settings</span>
            </a>
        </div>
    </div>






<!-- Main Content -->
<div class="main-content">
    <div class="header"><h1 class="page-title">Profile Settings</h1></div>

    <!-- Update Profile Details -->
    <div class="content-section">
        <div class="section-header"><h2 class="section-title">Update Profile Details</h2></div>
        <form method="POST">
            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>">
                </div>
                <div class="form-group" style="flex:1;">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>">
                </div>
            </div>
            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <!-- Upload Profile Photo (form only, no functionality) -->
<div class="content-section">
    <div class="section-header"><h2 class="section-title">Upload Profile Photo</h2></div>
    <form id="photoForm" method="POST" enctype="multipart/form-data">
        <div style="text-align:center;margin-bottom:1rem;">
            <img id="photoPreview" src="" style="width:150px;height:150px;border-radius:50%;object-fit:cover;border:2px solid #ddd;">
        </div>
        <div class="form-group">
            <label for="photo">Select New Profile Photo</label>
            <input type="file" id="photo" name="photo" class="form-control" accept="image/*">
        </div>
        <button type="submit" class="btn btn-primary">Upload Photo</button>
    </form>
</div>


    <!-- Update Password -->
    <div class="content-section">
        <div class="section-header"><h2 class="section-title">Update Password</h2></div>
        <form method="POST">
            <?php if(isset($passwordMessage)) echo "<p>$passwordMessage</p>"; ?>
            <div class="form-group">
                <label for="current_password">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="form-control">
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:1;">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control">
                </div>
                <div class="form-group" style="flex:1;">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                </div>
            </div>
            <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
        </form>
    </div>
</div>
</body>
</html>
