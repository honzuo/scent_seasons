<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 搜索逻辑
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// 只查询 role = 'member' 的用户
$sql = "SELECT * FROM users WHERE role = 'member' AND (full_name LIKE ? OR email LIKE ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$members = $stmt->fetchAll();

$page_title = "Member Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Member Management</h2>

<div style="display:flex; justify-content:space-between; margin-bottom: 20px;">
    <div></div>
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search name or email..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] == 'blocked'): ?>
        <p style="color:red; font-weight:bold;">User has been blocked.</p>
    <?php elseif ($_GET['msg'] == 'unblocked'): ?>
        <p style="color:green; font-weight:bold;">User has been unblocked.</p>
    <?php endif; ?>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Info</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($members as $m): ?>
            <tr style="<?php echo ($m['is_blocked'] == 1) ? 'background-color:#fff5f5;' : ''; ?>">
                <td>#<?php echo $m['user_id']; ?></td>
                <td>
                    <img src="../../../images/uploads/<?php echo $m['profile_photo']; ?>" class="thumbnail" style="border-radius: 50%;">
                </td>
                <td>
                    <strong><?php echo $m['full_name']; ?></strong><br>
                    <a href="mailto:<?php echo $m['email']; ?>" style="color: #666; text-decoration: none;">
                        <?php echo $m['email']; ?>
                    </a><br>
                    <small style="color:#999;">Joined: <?php echo isset($m['created_at']) ? date('Y-m-d', strtotime($m['created_at'])) : '-'; ?></small>
                </td>
                <td>
                    <?php if ($m['is_blocked'] == 1): ?>
                        <span style="color:white; background:red; padding:3px 8px; border-radius:4px; font-size:0.8em;">BLOCKED</span>
                    <?php else: ?>
                        <span style="color:white; background:green; padding:3px 8px; border-radius:4px; font-size:0.8em;">ACTIVE</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="../orders/index.php?user_id=<?php echo $m['user_id']; ?>" class="btn-blue" style="padding: 5px 10px; font-size: 0.8em; margin-right: 5px; text-decoration:none;">Orders</a>

                    <form action="../../../controllers/member_controller.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_block">
                        <input type="hidden" name="user_id" value="<?php echo $m['user_id']; ?>">

                        <?php if ($m['is_blocked'] == 1): ?>
                            <input type="hidden" name="is_blocked" value="0">
                            <button type="submit" class="btn-green" style="padding: 5px 10px; font-size: 0.8em;" onclick="return confirm('Unblock this user?');">Unblock</button>
                        <?php else: ?>
                            <input type="hidden" name="is_blocked" value="1">
                            <button type="submit" class="btn-red" style="padding: 5px 10px; font-size: 0.8em;" onclick="return confirm('Block this user? They will not be able to login.');">Block</button>
                        <?php endif; ?>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($members) == 0): ?>
    <p style="text-align: center; color: gray; margin-top: 20px;">No members found.</p>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>