<?php
session_start();
include '../sb_head.php';
require_once __DIR__ . '/../sb_base.php';

// initialize variables
$error = '';
$success = '';
$showForm = $_GET['form'] ?? 'login';

if (isset($_GET['form']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error = '';
    if ($_GET['form'] !== 'login') {
        $success = '';
    }
}

// after login then based on user role to redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: /page/adminPanel.php');
    } else {
        header('Location: /index.php');
    }
    exit;
}

// handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
        $showForm = 'login';
    } else {
        // check users table
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, user_role, profile_photo FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && $password === $user['password_hash']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_role'];
            if (!empty($user['profile_photo'])) {
                $_SESSION['profile_photo'] = $user['profile_photo'];
            } else {
                unset($_SESSION['profile_photo']);
            }
            
            if ($user['user_role'] === 'admin') {
                header('Location: /page/adminPanel.php');
            } else {
                header('Location: /index.php');
            }
            exit;
        }
        
        $error = 'Invalid username or password';
        $showForm = 'login';
    }
}

// register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields';
        $showForm = 'register';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
        $showForm = 'register';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
        $showForm = 'register';
    } else {
        // check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists';
            $showForm = 'register';
        } else {
            // insert new user (default role is 'user')
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, user_role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$username, $email, $password]);
            
            $success = 'Registration successful! You can now login.';
            $showForm = 'login';
        }
    }
}

// Handle success message from GET (for redirects from other pages)
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Handle error message from GET (for redirects from other pages)
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB | Sell Book Online</title>
    <link rel="stylesheet" href="/css/sb_style.css">
    <link rel="stylesheet" href="/css/user.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/sb_script.js"></script>
</head>

<body>
    <img src="/images/login_background.jpg" alt="background" class="background-image">

    <div class="login-container">
        <img src="/images/login.jpg" alt="User" class="user-icon">

        <?php if ($error): ?>
            <div class="message message-error" id="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message message-success" id="success-message">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php 
        // Determine which form to show
        $loginFormDisplay = ($showForm === 'register' || $showForm === 'forgotPassword') ? 'style="display:none;"' : '';
        $registerFormDisplay = ($showForm === 'register') ? '' : 'style="display:none;"';
        $forgotPasswordFormDisplay = ($showForm === 'forgotPassword') ? '' : 'style="display:none;"';
        ?>

        <form id="login-form" method="POST" action="" <?= $loginFormDisplay ?>>
            
            <h2>Login</h2>
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? $_GET['username'] ?? '') ?>" required />
            <input type="password" name="password" placeholder="Password" required />
            <span id="togglePassword" class="eye-icon">&#128065;</span>
            <button type="submit" name="login_submit">Login</button>

            <div class="login-links">
                <label><input type="checkbox" />Remember Me</label>
                <span class="forgotPassword link-span" data-get="forgotPassword">Forgot Password?</span>
            </div>

            <div class="login-links">
                <span class="link-span" data-get="register">Create an Account</span>
            </div>
        </form>

        <form id="register-form" <?= $registerFormDisplay ?> method="POST" action="">
            <h2>Register</h2>
            <input type="text" name="username" placeholder="Username" value="<?= htmlspecialchars($_POST['username'] ?? $_GET['reg_username'] ?? '') ?>" required />
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($_POST['email'] ?? $_GET['reg_email'] ?? '') ?>" required />
            <input type="password" name="password" placeholder="Password" required />
            <input type="password" name="confirm_password" placeholder="Confirm Password" required />
            <button type="submit" name="register_submit">REGISTER</button>

            <div class="login-links">
                <span class="link-span" data-get="login">Back to Login</span>
            </div>
        </form>

        <form id="updatePassword-form" <?= $forgotPasswordFormDisplay ?> method="POST" action="updatePassword.php">
            <h2>Update Password</h2>
            <input type="password" name="password" placeholder="Password" required />
            <input type="password" name="confirm_password" placeholder="Confirm Password" required />
            <button type="submit">Confirm</button>

            <div class="login-links">
                <span class="link-span" data-get="login">Back to Login</span>
            </div>
        </form>
    </div>
</body>

</html>