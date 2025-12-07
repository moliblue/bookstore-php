<?php
// start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SB | Sell Book Online</title>
    <link rel="stylesheet" href="/css/sb_style.css">
</head>

<body>
    <header>
        <a href="/index.php" class="logo" title="Back to Home">
            SB <i class="fas fa-home" style="font-size: 0.7em; margin-left: 4px;"></i>
        </a>
        <div class="header-right">
            <nav>
                <ul>
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/page/about.php">About</a></li>
                    <li><a href="/page/product_view.php">Books</a></li>
                    <li><a href="/page/promotion.php">Promotion</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="/page/cart_view.php">Cart</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/page/cart_view.php" class="cart-icon-link" title="View Cart">
                    <i class="fas fa-shopping-cart"></i>
                    <?php
                    require_once __DIR__ . '/page/cart.php';
                    $cartCount = cart_item_count();
                    if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <div class="profile-dropdown">
                    <button class="profile-icon" onclick="toggleProfileDropdown()" title="Profile">
                        <?php
                        $profileImage = '/images/login.jpg';
                        if (!empty($_SESSION['profile_photo'])) {
                            $profileImage = '/page/uploads/profiles/' . $_SESSION['profile_photo'];
                        }
                        ?>
                        <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile" class="profile-image">
                    </button>
                    <div class="dropdown-menu" id="profileDropdown">
                        <a href="/page/<?= ($_SESSION['user_type'] ?? '') === 'admin' ? 'admin_profile.php' : 'user_profile.php' ?>">Profile</a>
                        <a href="/page/order_history.php">Order History</a>
                        <a href="/login/logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="/login/login.php" class="login-link">Login</a>
            <?php endif; ?>
        </div>
    </header>
    <script>
        function toggleProfileDropdown() {
            document.getElementById('profileDropdown').classList.toggle('show');
        }

        // close dropdown when clicking outside the profile icon
        window.onclick = function(event) {
            if (!event.target.matches('.profile-icon') && !event.target.closest('.profile-dropdown')) {
                var dropdown = document.getElementById('profileDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    </script>