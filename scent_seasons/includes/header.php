<?php
// 1. 确保 Session 开启
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 默认路径设置
if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_count = $stmt->fetchColumn() ?: 0;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo $path; ?>css/common.css">
    <?php if (isset($extra_css)): ?>
        <link rel="stylesheet" href="<?php echo $path; ?>css/<?php echo $extra_css; ?>">
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    <nav>
        <?php
        // [修改] Logo 永远指向新的首页
        $home_link = $path . "views/member/home.php";

        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'superadmin') {
                $home_link = $path . "views/admin/dashboard.php";
            }
        }
        ?>
        <a href="<?php echo $home_link; ?>" class="logo">Scent Seasons</a>

        <ul>
            <?php if (isset($_SESSION['user_id'])): ?>

                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'superadmin'): ?>
                    <li><a href="<?php echo $path; ?>views/admin/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $path; ?>views/admin/products/index.php">Products</a></li>
                    <li><a href="<?php echo $path; ?>views/admin/orders/index.php">Orders</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $path; ?>views/member/home.php">Home</a></li>
                    <li><a href="<?php echo $path; ?>views/member/shop.php">Shop</a></li>
                    <li>
                        <a href="<?php echo $path; ?>views/member/cart.php">
                            Cart (<?php echo $cart_count; ?>)
                        </a>
                    </li>
                    <li><a href="<?php echo $path; ?>views/member/orders.php">Orders</a></li>
                    <li><a href="<?php echo $path; ?>views/member/profile.php">Profile</a></li>
                <?php endif; ?>

                <li><a href="<?php echo $path; ?>logout.php">Logout</a></li>

            <?php else: ?>
                <li><a href="<?php echo $path; ?>views/public/login.php">Login</a></li>
                <li><a href="<?php echo $path; ?>views/public/register.php">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">