<?php
session_start();
require '../../includes/functions.php';

// 获取 Session 中的错误信息
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$old = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : [];

// 清除 Session 错误，避免刷新页面时错误还在
unset($_SESSION['errors']);
unset($_SESSION['old_input']);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Register - Scent Seasons</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>

<body>
    <nav>
        <a href="../../index.php" class="logo">Scent Seasons</a>
        <ul>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Member Registration</h2>

        <form action="../../controllers/auth_register.php" method="POST" enctype="multipart/form-data" id="registerForm">

            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" value="<?php echo isset($old['full_name']) ? $old['full_name'] : ''; ?>" required>
                <?php display_error($errors, 'name'); ?>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" value="<?php echo isset($old['email']) ? $old['email'] : ''; ?>" required>
                <?php display_error($errors, 'email'); ?>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
                <?php display_error($errors, 'password'); ?>
            </div>

            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required>
                <?php display_error($errors, 'confirm_password'); ?>
            </div>

            <div class="form-group">
                <label>Profile Photo:</label>
                <input type="file" name="profile_photo" accept="image/*">
                <?php display_error($errors, 'photo'); ?>
            </div>

            <br>
            <button type="submit">Register</button>
        </form>
    </div>
</body>

</html>