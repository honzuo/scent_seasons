<?php
session_start();
require '../../includes/functions.php';

$page_title = "Login - Scent Seasons";
$path = "../../";
require $path . 'includes/header.php';

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$msg = isset($_SESSION['success_msg']) ? $_SESSION['success_msg'] : '';
unset($_SESSION['errors']);
unset($_SESSION['success_msg']);
?>

<div class="auth-box">
    <h2>Login</h2>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?php echo $msg; ?></div>
    <?php endif; ?>

    <form action="../../controllers/auth_login.php" method="POST">
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" required autofocus>
        </div>
        
        <div class="form-group">
            <label>Password:</label>
            <div class="password-wrapper">
                <input type="password" name="password" required>
                <button type="button" class="toggle-password" aria-label="Show password">
                    <span class="eye-icon">👁️‍🗨️</span>
                </button>
            </div>
        </div>

        <?php display_error($errors, 'login'); ?>

        <div class="auth-links">
            <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn-blue w-100">Login</button>
        </div>

        <div class="text-center mt-10" style="font-size: 0.9em;">
            Don't have an account? <a href="register.php" style="color:#0071e3;">Register here</a>
        </div>
    </form>
</div>

<script src="<?php echo $path; ?>js/password_validation.js"></script>
<?php require $path . 'includes/footer.php'; ?>