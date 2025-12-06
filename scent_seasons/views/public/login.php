<?php
session_start();
require '../../includes/functions.php';
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
unset($_SESSION['errors']);
unset($_SESSION['success_msg']);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login - Scent Seasons</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>

<body>
    <nav>
        <a href="../../index.php" class="logo">Scent Seasons</a>
        <ul>
            <li><a href="register.php">Register</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Login</h2>

        <?php if ($msg): ?>
            <p style="color: green;"><?php echo $msg; ?></p>
        <?php endif; ?>

        <form action="../../controllers/auth_login.php" method="POST">
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <?php display_error($errors, 'login'); ?>

            <br>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>