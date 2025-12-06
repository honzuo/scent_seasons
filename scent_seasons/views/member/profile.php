<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 从数据库获取最新用户信息
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$page_title = "My Profile - Scent Seasons";
$path = "../../";
$extra_css = "shop.css"; // 复用一下 shop.css 里的样式

require $path . 'includes/header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <h2>My Profile</h2>

    <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] == 'info_updated'): ?>
            <p style="color: green; background: #e8f5e9; padding: 10px; border-radius: 4px;">Profile updated successfully!</p>
        <?php elseif ($_GET['msg'] == 'password_changed'): ?>
            <p style="color: green; background: #e8f5e9; padding: 10px; border-radius: 4px;">Password changed successfully!</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <p style="color: red; background: #ffebee; padding: 10px; border-radius: 4px;">
            <?php 
                if ($_GET['error'] == 'wrong_password') echo "Current password is incorrect.";
                elseif ($_GET['error'] == 'password_mismatch') echo "New passwords do not match.";
                elseif ($_GET['error'] == 'password_short') echo "Password must be at least 6 characters.";
                else echo "An error occurred.";
            ?>
        </p>
    <?php endif; ?>

    <div style="display: flex; gap: 30px; margin-top: 20px; flex-wrap: wrap;">
        
        <div style="flex: 1; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
            <h3 style="margin-top: 0;">Edit Personal Info</h3>
            
            <form action="../../controllers/profile_controller.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_info">
                
                <div style="text-align: center; margin-bottom: 20px;">
                    <img src="../../images/uploads/<?php echo $user['profile_photo']; ?>" 
                         style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid #eee;">
                    <br>
                    <label style="cursor: pointer; color: blue; font-size: 0.9em; display: inline-block; margin-top: 10px;">
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

                <button type="submit" class="btn-blue" style="width: 100%;">Update Profile</button>
            </form>
        </div>

        <div style="flex: 1; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); height: fit-content;">
            <h3 style="margin-top: 0;">Change Password</h3>
            
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

                <button type="submit" class="btn-green" style="width: 100%;">Change Password</button>
            </form>
        </div>

    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>