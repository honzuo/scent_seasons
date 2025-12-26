<?php
session_start();
require '../../includes/functions.php';


$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$old = isset($_SESSION['old_input']) ? $_SESSION['old_input'] : [];


unset($_SESSION['errors']);
unset($_SESSION['old_input']);


$page_title = "Register - Scent Seasons";
$path = "../../";

require $path . 'includes/header.php';
?>

<div class="auth-box">
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
            <div class="password-wrapper">
                <input type="password" name="password" class="validate-password" required>
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

        <div class="form-group">
            <label>Profile Photo:</label>
            <input type="file" name="profile_photo" accept="image/*" class="input-file-simple">
            <?php display_error($errors, 'photo'); ?>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn-green w-100">Register</button>
        </div>

        <div class="text-center mt-10" style="font-size: 0.9em;">
            Already have an account? <a href="login.php" style="color:#3498db;">Login here</a>
        </div>
    </form>
</div>

<script src="<?php echo $path; ?>js/password_validation.js"></script>
<?php require $path . 'includes/footer.php'; ?>