<?php
session_start();
require '../../includes/functions.php';


if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
    header("Location: forgot_password.php");
    exit();
}

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['errors']);

$page_title = "Reset Password - Scent Seasons";
$path = "../../";
require $path . 'includes/header.php';
?>

<div class="auth-box">
    <h2>Reset Password</h2>
    <p class="text-muted text-center">Enter your new password below.</p>

    <form action="<?php echo $path; ?>controllers/auth_reset_password.php" method="POST">
        <div class="form-group">
            <label>New Password:</label>
            <div class="password-wrapper">
                <input type="password" name="password" class="validate-password" required autofocus>
                <button type="button" class="toggle-password" aria-label="Show password" tabindex="-1">
                    <span class="eye-icon">👁️‍🗨️</span>
                </button>
            </div>
            
        
            <div class="password-strength-container">
                <div class="password-strength-bar">
                    <div class="password-strength-fill"></div>
                </div>
                <div class="password-strength-text"></div>
            </div>
            

            <div class="password-requirements">
                <h4>Password Requirements:</h4>
                <ul>
                    <li>At least 8 characters</li>
                    <li>One uppercase letter (A-Z)</li>
                    <li>One lowercase letter (a-z)</li>
                    <li>One number (0-9)</li>
                    <li>One special character (!@#$%^&*)</li>
                </ul>
            </div>
            
            <?php display_error($errors, 'password'); ?>
        </div>

        <div class="form-group">
            <label>Confirm Password:</label>
            <div class="password-wrapper">
                <input type="password" name="confirm_password" required>
                <button type="button" class="toggle-password" aria-label="Show password" tabindex="-1">
                    <span class="eye-icon">👁️‍🗨️</span>
                </button>
            </div>
            <?php display_error($errors, 'confirm_password'); ?>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn-green w-100">Reset Password</button>
        </div>
    </form>
</div>


<script src="<?php echo $path; ?>js/password_validation.js"></script>

<?php require $path . 'includes/footer.php'; ?>