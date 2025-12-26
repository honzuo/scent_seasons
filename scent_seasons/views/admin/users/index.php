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

<div class="flex-between mb-20">
    <button onclick="openCreateAdminModal()" class="btn-blue">+ Create New Admin</button>

    <form method="GET" action="" class="search-form">
        <input type="text" name="search" placeholder="Search admin..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert <?php echo ($_GET['msg'] == 'deleted' || $_GET['msg'] == 'blocked') ? 'alert-error' : 'alert-success'; ?>">
        <?php
        if ($_GET['msg'] == 'created') echo "New Admin created successfully!";
        elseif ($_GET['msg'] == 'updated') echo "Admin details updated successfully.";
        elseif ($_GET['msg'] == 'blocked') echo "Admin has been blocked.";
        elseif ($_GET['msg'] == 'unblocked') echo "Admin has been unblocked.";
        elseif ($_GET['msg'] == 'deleted') echo "Admin deleted successfully.";
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <?php
        if ($_GET['error'] == 'mismatch') echo "Passwords do not match.";
        elseif ($_GET['error'] == 'exists') echo "Email already exists.";
        else echo "Please fill in all fields.";
        ?>
    </div>
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
            <tr class="<?php echo ($a['is_blocked'] == 1) ? 'bg-red-light' : ''; ?>">
                <td>#<?php echo $a['user_id']; ?></td>
                <td>
                    <img src="../../../images/uploads/<?php echo $a['profile_photo']; ?>" class="thumbnail thumbnail-circle">
                </td>
                <td>
                    <strong><?php echo $a['full_name']; ?></strong><br>
                    <small class="text-gray"><?php echo $a['email']; ?></small>
                </td>
                <td>
                    <?php if ($a['is_blocked'] == 1): ?>
                        <span class="badge badge-red">BLOCKED</span>
                    <?php else: ?>
                        <span class="text-green-bold">Active</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button"
                        class="btn-blue btn-sm"
                        data-id="<?php echo $a['user_id']; ?>"
                        data-fullname="<?php echo htmlspecialchars($a['full_name']); ?>"
                        data-email="<?php echo htmlspecialchars($a['email']); ?>"
                        onclick="openEditAdminModal(this)">
                        Edit
                    </button>

                    <form action="../../../controllers/admin_user_controller.php" method="POST" class="form-inline">
                        <input type="hidden" name="action" value="toggle_block">
                        <input type="hidden" name="user_id" value="<?php echo $a['user_id']; ?>">

                        <?php if ($a['is_blocked'] == 1): ?>
                            <input type="hidden" name="is_blocked" value="0">
                            <button type="submit" class="btn-green btn-sm" onclick="return confirm('Unblock this admin?');">Unblock</button>
                        <?php else: ?>
                            <input type="hidden" name="is_blocked" value="1">
                            <button type="submit" class="btn-red btn-sm" onclick="return confirm('Block this admin?');">Block</button>
                        <?php endif; ?>
                    </form>

                    <form action="../../../controllers/admin_user_controller.php" method="POST" class="form-inline" onsubmit="return confirm('Are you sure? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo $a['user_id']; ?>">
                        <button type="submit" class="btn-darkred btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($admins) == 0): ?>
    <p class="text-center text-gray mt-20">No other admins found.</p>
<?php endif; ?>


<div id="createAdminModal" class="modal-overlay">
    <div class="modal-box small">
        <h3 class="mt-0">Create New Admin</h3>
        <p class="text-gray mb-20">Enter details for the new administrator.</p>

        <form action="../../../controllers/admin_user_controller.php" method="POST">
            <input type="hidden" name="action" value="create_admin">

            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password:</label>
                <input type="password" name="confirm_password" required class="mb-20">
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeCreateAdminModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-blue">Create</button>
            </div>
        </form>
    </div>
</div>

<div id="editAdminModal" class="modal-overlay">
    <div class="modal-box small">
        <h3 class="mt-0">Edit Admin</h3>

        <form action="../../../controllers/admin_user_controller.php" method="POST">
            <input type="hidden" name="action" value="update_admin">
            <input type="hidden" name="user_id" id="edit_user_id">

            <div class="form-group">
                <label>Full Name:</label>
                <input type="text" name="full_name" id="edit_full_name" required>
            </div>

            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" id="edit_email" required>
            </div>

            <div class="form-group">
                <label>Reset Password (Optional):</label>
                <input type="password" name="password" placeholder="Leave empty to keep current">
                <small class="text-muted-small">Only fill if you want to change password.</small>
            </div>

            <div class="modal-actions">
                <button type="button" onclick="closeEditAdminModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-blue">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateAdminModal() {
        document.getElementById('createAdminModal').style.display = 'flex';
    }

    function closeCreateAdminModal() {
        document.getElementById('createAdminModal').style.display = 'none';
    }

    function openEditAdminModal(btn) {
        let id = btn.getAttribute('data-id');
        let fullname = btn.getAttribute('data-fullname');
        let email = btn.getAttribute('data-email');

        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_full_name').value = fullname;
        document.getElementById('edit_email').value = email;

        document.getElementById('editAdminModal').style.display = 'flex';
    }

    function closeEditAdminModal() {
        document.getElementById('editAdminModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let createModal = document.getElementById('createAdminModal');
        let editModal = document.getElementById('editAdminModal');
        if (event.target == createModal) closeCreateAdminModal();
        if (event.target == editModal) closeEditAdminModal();
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        openCreateAdminModal();
    }
</script>

<?php require $path . 'includes/footer.php'; ?>