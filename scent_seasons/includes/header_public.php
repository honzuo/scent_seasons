<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($path)) $path = "./";
if (!isset($page_title)) $page_title = "Scent Seasons";
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
                <li><a href="<?php echo $path; ?>index.php">Home</a></li>
                <li><a href="<?php echo $path; ?>views/public/login.php">Login</a></li>
                <li><a href="<?php echo $path; ?>views/public/register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">

