<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";


require_once __DIR__ . '/functions.php';


if (!is_admin()) {
    header("Location: " . $path . "views/public/login.php?error=unauthorized");
    exit();
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
            <a href="<?php echo $path; ?>views/admin/dashboard.php" class="logo">Scent Seasons</a>

            <ul>

                <li><a href="<?php echo $path; ?>views/admin/dashboard.php">Dashboard</a></li>
                <li><a href="<?php echo $path; ?>views/admin/products/index.php">Products</a></li>
                <li><a href="<?php echo $path; ?>views/admin/orders/index.php">Orders</a></li>
                <li><a href="<?php echo $path; ?>views/admin/promotion.php">Promotions</a></li>
                <li><a href="<?php echo $path; ?>views/admin/chat/index.php">Chat</a></li>
                <li><a href="<?php echo $path; ?>views/admin/reports/index.php">Reports</a></li>
                <li><a href="<?php echo $path; ?>logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">

