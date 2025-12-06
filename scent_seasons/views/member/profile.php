<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = "My Profile - Scent Seasons";
$path = "../../";
$extra_css = "shop.css";
require $path . 'includes/header.php';
?>

<div class="container" style="max-width:900px;">
    <h2>My Profile</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['msg'] == 'info_updated') echo "Profile updated successfully!";
            elseif ($_GET['msg'] == 'password_changed') echo "Password changed successfully!";
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php
            if ($_GET['error'] == 'wrong_password') echo "Current password is incorrect.";
            elseif ($_GET['error'] == 'password_mismatch') echo "New passwords do not match.";
            elseif ($_GET['error'] == 'password_short') echo "Password must be at least 6 characters.";
            else echo "An error occurred.";
            ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">
        <div class="profile-card">
            <h3 class="mt-0">Edit Personal Info</h3>
            <form action="../../controllers/profile_controller.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_info">

                <div class="profile-avatar-container">
                    <img src="../../images/uploads/<?php echo $user['profile_photo']; ?>" class="profile-avatar">
                    <br>
                    <label class="mt-10" style="cursor: pointer; color: blue; font-size: 0.9em; display: inline-block;">
                        Change Photo
                        <input type="file" name="profile_photo" accept="image/*" style="display: none;" onchange="alert('Photo selected! Click Update to save.')">
                    </label>
                </div>

                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address:</label>
                    <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Role:</label>
                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled style="background: #eee; cursor: not-allowed;">
                </div>

                <button type="submit" class="btn-blue w-100">Update Profile</button>
            </form>
        </div>

        <div class="profile-card">
            <h3 class="mt-0">Change Password</h3>
            <form action="../../controllers/profile_controller.php" method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Current Password:</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label>New Password:</label>
                    <input type="password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password:</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-green w-100">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>