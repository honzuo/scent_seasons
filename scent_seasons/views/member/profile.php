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


$sql = "SELECT w.wishlist_id, w.created_at as added_at, p.* FROM wishlist w 
        JOIN products p ON w.product_id = p.product_id 
        WHERE w.user_id = ? AND p.is_deleted = 0
        ORDER BY w.created_at DESC"; 
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

$page_title = "My Profile - Scent Seasons";
$path = "../../";
$extra_css = "shop.css";
require $path . 'includes/header.php';
?>

<div class="container" style="max-width:1200px;">
    <h2>My Profile</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?php
            if ($_GET['msg'] == 'info_updated')
                echo "Profile updated successfully!";
            elseif ($_GET['msg'] == 'password_changed')
                echo "Password changed successfully!";
            elseif ($_GET['msg'] == 'wishlist_removed')
                echo "Item removed from wishlist.";
            elseif ($_GET['msg'] == 'address_added')
                echo "New address added successfully!";
            elseif ($_GET['msg'] == 'address_deleted')
                echo "Address deleted successfully!";
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            <?php
            if ($_GET['error'] == 'wrong_password')
                echo "Current password is incorrect.";
            elseif ($_GET['error'] == 'password_mismatch')
                echo "New passwords do not match.";
            elseif ($_GET['error'] == 'password_short')
                echo "Password must be at least 6 characters.";
            else
                echo "An error occurred.";
            ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
  
        <div class="profile-card">
            <h3 class="mt-0">Edit Personal Info</h3>
            <form action="../../controllers/profile_controller.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update_info">

                <div class="profile-avatar-container">
                    <img src="../../images/uploads/<?php echo $user['profile_photo']; ?>" class="profile-avatar">
                    <br>
                    <label class="mt-10"
                        style="cursor: pointer; color: #0071e3; font-size: 0.9em; display: inline-block;">
                        Change Photo
                        <input type="file" name="profile_photo" accept="image/*" style="display: none;"
                            onchange="alert('Photo selected! Click Update to save.')">
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
                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled
                        style="background: #f5f5f7; cursor: not-allowed; color: #86868b;">
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

    <div class="profile-card" style="margin-top: 20px; width: 100%;">
        <h3>My Address Book</h3>
        <?php
        $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $saved_addresses = $stmt->fetchAll();
        ?>
        <ul class="list-group">
            <?php foreach ($saved_addresses as $addr): ?>
                <li style="display:flex; justify-content:space-between; padding:10px; border-bottom:1px solid #eee;">
                    <span><?php echo htmlspecialchars($addr['address_text']); ?></span>
                    <form action="../../controllers/profile_controller.php" method="POST"
                        onsubmit="return confirm('Delete this address?')">
                        <input type="hidden" name="action" value="delete_address">
                        <input type="hidden" name="address_id" value="<?php echo $addr['address_id']; ?>">
                        <button type="submit"
                            style="color:red; border:none; background:none; cursor:pointer;">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>

        <form action="../../controllers/profile_controller.php" method="POST" style="margin-top:15px;">
            <input type="hidden" name="action" value="add_address">
            <div class="form-group">
                <textarea name="address" placeholder="Enter new address..." required
                    style="width:100%; height:60px;"></textarea>
            </div>
            <button type="submit" class="btn-blue">Add New Address</button>
        </form>
    </div>


    <div class="wishlist-section" style="margin-top: 60px;">
        <div class="flex-between mb-20">
            <div>
                <h2 style="margin-bottom: 8px;">My Wishlist</h2>
                <p class="text-muted" style="margin-top: 0;">Items you've saved for later
                    (<?php echo count($wishlist_items); ?>)</p>
            </div>
        </div>

        <?php if (count($wishlist_items) > 0): ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-item-card">
           
                        <form action="../../controllers/wishlist_controller.php" method="POST" class="wishlist-remove-form">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="from" value="profile">
                            <button type="submit" class="btn-remove-wishlist-compact" title="Remove">×</button>
                        </form>

                        <div class="wishlist-item-content">
                            <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                                <img src="../../images/products/<?php echo $item['image_path']; ?>"
                                    alt="<?php echo $item['name']; ?>" class="wishlist-item-img">
                            </a>

                            <div class="wishlist-item-info">
                                <h4 class="wishlist-item-name">
                                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>"
                                        style="text-decoration: none; color: #1d1d1f;">
                                        <?php echo $item['name']; ?>
                                    </a>
                                </h4>

                                <p class="wishlist-item-price">$<?php echo $item['price']; ?></p>

                                <div class="wishlist-item-meta">
                                    <?php if ($item['stock'] > 0): ?>
                                        <span class="stock-badge-mini stock-available">In Stock</span>
                                    <?php else: ?>
                                        <span class="stock-badge-mini stock-out">Out of Stock</span>
                                    <?php endif; ?>
                                    <span class="wishlist-date-mini">
                                        Added <?php echo date('M d, Y', strtotime($item['added_at'])); ?>
                                    </span>
                                </div>

                                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="btn-view-product">
                                    View Product
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist-compact">
                <div class="empty-icon-small">♡</div>
                <p style="color: #86868b; margin-bottom: 16px;">Your wishlist is empty</p>
                <a href="shop.php" class="btn-blue" style="font-size: 14px; padding: 8px 24px;">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>