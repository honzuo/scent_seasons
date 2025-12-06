<?php
session_start();
require '../../includes/functions.php';

// --- 设置 Header 参数 ---
$page_title = "Login - Scent Seasons";
$path = "../../"; // 因为在 views/public/，回根目录需要两层
// $extra_css = ""; // 登录页不需要额外css，这行可以不写

require $path . 'includes/header.php'; // 引入头部
// -----------------------

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
unset($_SESSION['errors']);
unset($_SESSION['success_msg']);
?>
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

<?php require $path . 'includes/footer.php'; ?>