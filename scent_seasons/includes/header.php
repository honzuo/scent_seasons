<?php
// 1. 确保 Session 开启
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. 默认路径设置 (如果页面没传 path，默认是当前目录)
if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";

// 3. 计算购物车数量 (防止报错)
$cart_count = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
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
        $home_link = $path . "index.php"; // 默认游客去首页
        if (isset($_SESSION['role'])) {
            if ($_SESSION['role'] == 'admin') $home_link = $path . "views/admin/dashboard.php";
            if ($_SESSION['role'] == 'member') $home_link = $path . "views/member/home.php";
        }
        ?>
        <a href="<?php echo $home_link; ?>" class="logo">Scent Seasons</a>

        <ul>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="<?php echo $path; ?>views/admin/dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $path; ?>views/admin/products/index.php">Products</a></li>
                    <li><a href="<?php echo $path; ?>views/admin/orders/index.php">Orders</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $path; ?>views/member/home.php">Shop</a></li>
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