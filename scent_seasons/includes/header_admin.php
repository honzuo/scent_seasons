<?php
// includes/header_admin.php - Admin Navigation Only
// This header is ONLY for admin/superadmin users
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";

// Load functions to use is_admin() helper
require_once __DIR__ . '/functions.php';

// Security check - only admins can use this header
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
                <!-- Admin navigation - only for admin/superadmin roles -->
                <li><a href="<?php echo $path; ?>views/admin/dashboard.php">Dashboard</a></li>
                <li><a href="<?php echo $path; ?>views/admin/products/index.php">Products</a></li>
                <li><a href="<?php echo $path; ?>views/admin/orders/index.php">Orders</a></li>
                <li><a href="<?php echo $path; ?>logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">

