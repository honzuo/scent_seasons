<?php
session_start();
require '../../includes/functions.php';


if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['errors']);

$page_title = "Verify Code - Scent Seasons";
$path = "../../";
require $path . 'includes/header.php';
?>

<div class="auth-box">
    <h2>Verify Code</h2>
    <p class="text-muted text-center">
        We've sent a 6-digit verification code to<br>
        <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
    </p>

    <form action="<?php echo $path; ?>controllers/auth_verify_otp.php" method="POST">
        <div class="form-group">
            <label>Verification Code:</label>
            <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" 
                   placeholder="Enter 6-digit code" required autofocus 
                   class="otp-input">
            <?php display_error($errors, 'otp'); ?>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn-blue w-100">Verify Code</button>
        </div>

        <div class="text-center mt-10">
            <a href="forgot_password.php" class="text-link-gray">Resend Code</a> | 
            <a href="login.php" class="text-link-gray">Back to Login</a>
        </div>
    </form>
</div>

<?php require $path . 'includes/footer.php'; ?>