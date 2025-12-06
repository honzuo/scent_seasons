<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 获取所有分类
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_id ASC");
$categories = $stmt->fetchAll();

$page_title = "Category Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Category Management</h2>

<div style="display:flex; justify-content:space-between; margin-bottom: 20px;">
    <button onclick="openCreateModal()" class="btn-blue" style="cursor:pointer;">+ Add New Category</button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <p style="color:green; font-weight:bold; background:#e8f5e9; padding:10px; border-radius:4px;">
        <?php
        if ($_GET['msg'] == 'added') echo "Category added successfully.";
        elseif ($_GET['msg'] == 'updated') echo "Category updated successfully.";
        elseif ($_GET['msg'] == 'deleted') echo "Category deleted successfully.";
        ?>
    </p>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <p style="color:red; font-weight:bold; background:#ffebee; padding:10px; border-radius:4px;">
        <?php
        if ($_GET['error'] == 'empty') echo "Category name cannot be empty.";
        elseif ($_GET['error'] == 'in_use') echo "Error: Cannot delete this category because it contains products.";
        ?>
    </p>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td>#<?php echo $c['category_id']; ?></td>
                <td><?php echo htmlspecialchars($c['category_name']); ?></td>
                <td>
                    <button type="button"
                        class="btn-blue"
                        style="font-size:0.8em; cursor:pointer;"
                        data-id="<?php echo $c['category_id']; ?>"
                        data-name="<?php echo htmlspecialchars($c['category_name']); ?>"
                        onclick="openEditModal(this)">
                        Edit
                    </button>

                    <button type="button"
                        onclick="openDeleteModal(<?php echo $c['category_id']; ?>)"
                        class="btn-red"
                        style="font-size:0.8em; cursor:pointer;">
                        Delete
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<div id="createCategoryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:8px; width:400px; position:relative;">
        <h3 style="margin-top:0;">Add New Category</h3>

        <form action="../../../controllers/category_controller.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Category Name:</label>
                <input type="text" name="category_name" required placeholder="e.g., Floral" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div style="text-align:right; margin-top:20px;">
                <button type="button" onclick="closeCreateModal()" class="btn-blue" style="background:gray; margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-green">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="editCategoryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:8px; width:400px; position:relative;">
        <h3 style="margin-top:0;">Edit Category</h3>

        <form action="../../../controllers/category_controller.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="category_id" id="edit_category_id">

            <div class="form-group">
                <label>Category Name:</label>
                <input type="text" name="category_name" id="edit_category_name" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div style="text-align:right; margin-top:20px;">
                <button type="button" onclick="closeEditModal()" class="btn-blue" style="background:gray; margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-blue">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteCategoryModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:8px; width:400px; text-align:center;">
        <h3 style="margin-top:0; color:#c0392b;">Delete Category?</h3>
        <p style="color:gray; margin-bottom:20px;">
            Are you sure you want to delete this category? <br>
            <span style="font-size:0.85em; color:red;">Note: You cannot delete a category if it has products assigned to it.</span>
        </p>

        <form action="../../../controllers/category_controller.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="category_id" id="delete_category_id" value="">

            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" onclick="closeDeleteModal()" class="btn-blue" style="background:gray;">Cancel</button>
                <button type="submit" class="btn-red">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Create Modal ---
    function openCreateModal() {
        document.getElementById('createCategoryModal').style.display = 'flex';
    }

    function closeCreateModal() {
        document.getElementById('createCategoryModal').style.display = 'none';
    }

    // --- Edit Modal (填充数据) ---
    function openEditModal(btn) {
        let id = btn.getAttribute('data-id');
        let name = btn.getAttribute('data-name');

        document.getElementById('edit_category_id').value = id;
        document.getElementById('edit_category_name').value = name;

        document.getElementById('editCategoryModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editCategoryModal').style.display = 'none';
    }

    // --- Delete Modal ---
    function openDeleteModal(id) {
        document.getElementById('delete_category_id').value = id;
        document.getElementById('deleteCategoryModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteCategoryModal').style.display = 'none';
    }

    // --- Auto Open Create Modal (on error) ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('open_create')) {
        openCreateModal();
    }

    // --- Click Outside to Close ---
    window.onclick = function(event) {
        let modals = ['createCategoryModal', 'editCategoryModal', 'deleteCategoryModal'];
        modals.forEach(function(id) {
            let m = document.getElementById(id);
            if (event.target == m) {
                m.style.display = 'none';
            }
        });
    }
</script>

<?php require $path . 'includes/footer.php'; ?>