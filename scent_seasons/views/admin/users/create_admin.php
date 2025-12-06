<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';

// 只有超级管理员可以看这个页面
require_superadmin();

$page_title = "Create New Admin";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="container" style="max-width: 600px;">
    <h2>Create New Admin Account</h2>
    <p style="color:gray;">Only Superadmin can perform this action.</p>

    <?php if (isset($_GET['error'])): ?>
        <p style="color:red; background:#ffebee; padding:10px; border-radius:4px;">
            <?php
            if ($_GET['error'] == 'mismatch') echo "Passwords do not match.";
            elseif ($_GET['error'] == 'exists') echo "Email already exists.";
            else echo "Please fill in all fields.";
            ?>
        </p>
    <?php endif; ?>

    <form action="../../../controllers/admin_user_controller.php" method="POST">
        <input type="hidden" name="action" value="create_admin">

        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="full_name" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>
        </div>

        <br>
        <button type="submit" class="btn-blue">Create Admin</button>
        <a href="../dashboard.php" style="margin-left:15px; text-decoration:none; color:#666;">Cancel</a>
    </form>
</div>

<?php require $path . 'includes/footer.php'; ?>