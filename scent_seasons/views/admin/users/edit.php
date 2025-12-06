<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_superadmin();

if (!isset($_GET['id'])) { header("Location: index.php"); exit(); }
$id = intval($_GET['id']);

// 获取管理员信息 (确保只能编辑 role='admin' 的人)
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'admin'");
$stmt->execute([$id]);
$admin = $stmt->fetch();

if (!$admin) { die("Admin user not found."); }

$page_title = "Edit Admin";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="container" style="max-width: 600px;">
    <h2>Edit Admin: <?php echo $admin['full_name']; ?></h2>

    <form action="../../../controllers/admin_user_controller.php" method="POST">
        <input type="hidden" name="action" value="update_admin">
        <input type="hidden" name="user_id" value="<?php echo $admin['user_id']; ?>">

        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="full_name" value="<?php echo $admin['full_name']; ?>" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?php echo $admin['email']; ?>" required>
        </div>

        <div class="form-group">
            <label>Reset Password (Optional):</label>
            <input type="password" name="password" placeholder="Leave empty to keep current password">
            <small style="color:gray;">Only fill this if you want to change the admin's password.</small>
        </div>

        <br>
        <button type="submit" class="btn-blue">Update Admin</button>
        <a href="index.php" style="margin-left:15px; text-decoration:none; color:#666;">Cancel</a>
    </form>
</div>

<?php require $path . 'includes/footer.php'; ?>