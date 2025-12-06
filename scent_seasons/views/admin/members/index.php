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
$path = "../../../"; // 回到根目录需要3层
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

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Join Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td>#<?php echo $m['user_id']; ?></td>
                <td>
                    <img src="../../../images/uploads/<?php echo $m['profile_photo']; ?>" class="thumbnail" style="border-radius: 50%;">
                </td>
                <td><?php echo $m['full_name']; ?></td>
                <td>
                    <a href="mailto:<?php echo $m['email']; ?>" style="color: #2c3e50; text-decoration: none;">
                        <?php echo $m['email']; ?>
                    </a>
                </td>
                <td><?php echo isset($m['created_at']) ? date('Y-m-d', strtotime($m['created_at'])) : '-'; ?></td>
                <td>
                    <a href="../orders/index.php?user_id=<?php echo $m['user_id']; ?>" class="btn-green" style="padding: 5px 10px; font-size: 0.8em; margin-right: 5px; text-decoration:none;">Orders</a>

                    <form action="../../../controllers/member_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this member? This will also delete their order history.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $m['user_id']; ?>">
                        <button type="submit" class="btn-red">Delete</button>
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