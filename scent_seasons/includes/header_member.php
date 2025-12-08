<?php
// includes/header_member.php - Member Navigation Only
// This header is ONLY for regular member users (not admins)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";

// Load functions to use is_admin() helper
require_once __DIR__ . '/functions.php';

// Security check - members cannot use admin header, admins cannot use member header
if (is_admin()) {
    header("Location: " . $path . "views/admin/dashboard.php");
    exit();
}

// Get cart count for members
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo $path; ?>css/common.css">
    <?php if (isset($extra_css)): ?>
        <link rel="stylesheet" href="<?php echo $path; ?>css/<?php echo $extra_css; ?>">
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    <nav>
        <div class="nav-inner">
            <a href="<?php echo $path; ?>index.php" class="logo">Scent Seasons</a>

            <ul>
                <!-- Member navigation - regular users only -->
                <li><a href="<?php echo $path; ?>views/member/home.php">Home</a></li>
                <li><a href="<?php echo $path; ?>views/member/shop.php">Shop</a></li>
                <li>
                    <a href="<?php echo $path; ?>views/member/cart.php">
                        Cart (<?php echo $cart_count; ?>)
                    </a>
                </li>
                <li><a href="<?php echo $path; ?>views/member/orders.php">Orders</a></li>
                <li><a href="<?php echo $path; ?>views/member/profile.php">Profile</a></li>
                <li><a href="<?php echo $path; ?>logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">

