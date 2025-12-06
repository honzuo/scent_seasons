<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_superadmin(); // 只有 Superadmin 能进

$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// 只查询 role = 'admin'
$sql = "SELECT * FROM users WHERE role = 'admin' AND (full_name LIKE ? OR email LIKE ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$admins = $stmt->fetchAll();

$page_title = "Admin Maintenance";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Admin Maintenance</h2>

<div style="display:flex; justify-content:space-between; margin-bottom: 20px;">
    <button onclick="openModal()" class="btn-blue" style="cursor:pointer;">+ Create New Admin</button>

    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search admin..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'created'): ?>
    <p style="color:green; font-weight:bold; background:#e8f5e9; padding:10px; border-radius:4px;">New Admin created successfully!</p>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <p style="color:red; background:#ffebee; padding:10px; border-radius:4px;">
        <?php
        if ($_GET['error'] == 'mismatch') echo "Passwords do not match.";
        elseif ($_GET['error'] == 'exists') echo "Email already exists.";
        else echo "Please fill in all fields.";
        ?>
    </p>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Name & Email</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($admins as $a): ?>
            <tr style="<?php echo ($a['is_blocked'] == 1) ? 'background-color:#fff5f5;' : ''; ?>">
                <td>#<?php echo $a['user_id']; ?></td>
                <td>
                    <img src="../../../images/uploads/<?php echo $a['profile_photo']; ?>" class="thumbnail" style="border-radius: 50%;">
                </td>
                <td>
                    <strong><?php echo $a['full_name']; ?></strong><br>
                    <small style="color:#666;"><?php echo $a['email']; ?></small>
                </td>
                <td>
                    <?php if ($a['is_blocked'] == 1): ?>
                        <span style="color:red; font-weight:bold;">BLOCKED</span>
                    <?php else: ?>
                        <span style="color:green;">Active</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit.php?id=<?php echo $a['user_id']; ?>" class="btn-blue" style="padding:5px 10px; font-size:0.8em;">Edit</a>

                    <form action="../../../controllers/admin_user_controller.php" method="POST" style="display:inline;">
                        <input type="hidden" name="action" value="toggle_block">
                        <input type="hidden" name="user_id" value="<?php echo $a['user_id']; ?>">

                        <?php if ($a['is_blocked'] == 1): ?>
                            <input type="hidden" name="is_blocked" value="0">
                            <button type="submit" class="btn-green" style="padding:5px 10px; font-size:0.8em;" onclick="return confirm('Unblock this admin?');">Unblock</button>
                        <?php else: ?>
                            <input type="hidden" name="is_blocked" value="1">
                            <button type="submit" class="btn-red" style="padding:5px 10px; font-size:0.8em;" onclick="return confirm('Block this admin?');">Block</button>
                        <?php endif; ?>
                    </form>

                    <form action="../../../controllers/admin_user_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $a['user_id']; ?>">
                        <button type="submit" class="btn-red" style="background:darkred; padding:5px 10px; font-size:0.8em;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($admins) == 0): ?>
    <p style="text-align: center; color: gray; margin-top: 20px;">No other admins found.</p>
<?php endif; ?>


<div id="createAdminModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:8px; width:400px; position:relative;">
        <h3 style="margin-top:0;">Create New Admin</h3>
        <p style="color:gray; font-size:0.9em; margin-bottom:20px;">Enter details for the new administrator.</p>

        <form action="../../../controllers/admin_user_controller.php" method="POST">
            <input type="hidden" name="action" value="create_admin">

            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required style="width:100%; padding:8px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required style="width:100%; padding:8px; margin-bottom:20px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div style="text-align:right;">
                <button type="button" onclick="closeModal()" class="btn-blue" style="background:gray; margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-blue">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 打开弹窗函数
    function openModal() {
        document.getElementById('createAdminModal').style.display = 'flex';
    }

    // 关闭弹窗函数
    function closeModal() {
        document.getElementById('createAdminModal').style.display = 'none';
    }

    // 点击弹窗外部背景也可以关闭
    window.onclick = function(event) {
        let modal = document.getElementById('createAdminModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    // 如果URL里有 open_modal=1 (说明刚才提交出错了)，自动重新打开弹窗
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        openModal();
    }
</script>

<?php require $path . 'includes/footer.php'; ?>