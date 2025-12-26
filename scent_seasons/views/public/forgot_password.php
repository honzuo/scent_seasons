<?php
session_start();
require '../../includes/functions.php';

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$success = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
unset($_SESSION['errors']);
unset($_SESSION['success_msg']);

$page_title = "Forgot Password - Scent Seasons";
$path = "../../";
require $path . 'includes/header.php';

// 调试：显示当前路径
// echo "Current file: " . __FILE__ . "<br>";
// echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
?>

<div class="auth-box">
    <h2>Forgot Password</h2>
    <p class="text-muted text-center">Enter your email address and we'll send you a verification code.</p>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <!-- 修改form action的路径 -->
    <form action="<?php echo $path; ?>controllers/auth_forgot_password.php" method="POST">
        <div class="form-group">
            <label>Email Address:</label>
            <input type="email" name="email" required autofocus>
            <?php display_error($errors, 'email'); ?>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn-blue w-100">Send Verification Code</button>
        </div>

        <div class="text-center mt-10">
            <a href="login.php" class="text-link-gray">Back to Login</a>
        </div>
    </form>
</div>

<?php require $path . 'includes/footer.php'; ?>