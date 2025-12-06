<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_id ASC");
$categories = $stmt->fetchAll();

$page_title = "Category Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Category Management</h2>

<div class="mb-20">
    <button onclick="openCreateModal()" class="btn-blue">+ Add New Category</button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['msg'] == 'added') echo "Category added successfully.";
        elseif ($_GET['msg'] == 'updated') echo "Category updated successfully.";
        elseif ($_GET['msg'] == 'deleted') echo "Category deleted successfully.";
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <?php
        if ($_GET['error'] == 'empty') echo "Category name cannot be empty.";
        elseif ($_GET['error'] == 'in_use') echo "Error: Cannot delete this category because it contains products.";
        ?>
    </div>
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
                    <button type="button" class="btn-blue"
                        data-id="<?php echo $c['category_id']; ?>"
                        data-name="<?php echo htmlspecialchars($c['category_name']); ?>"
                        onclick="openEditModal(this)">
                        Edit
                    </button>

                    <button type="button" onclick="openDeleteModal(<?php echo $c['category_id']; ?>)" class="btn-red">
                        Delete
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div id="createCategoryModal" class="modal-overlay">
    <div class="modal-box small">
        <h3 class="mt-0">Add New Category</h3>
        <form action="../../../controllers/category_controller.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Category Name:</label>
                <input type="text" name="category_name" required placeholder="e.g., Floral">
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeCreateModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-green">Save</button>
            </div>
        </form>
    </div>
</div>

<div id="editCategoryModal" class="modal-overlay">
    <div class="modal-box small">
        <h3 class="mt-0">Edit Category</h3>
        <form action="../../../controllers/category_controller.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="category_id" id="edit_category_id">
            <div class="form-group">
                <label>Category Name:</label>
                <input type="text" name="category_name" id="edit_category_name" required>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeEditModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-blue">Update</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteCategoryModal" class="modal-overlay">
    <div class="modal-box small text-center">
        <h3 class="mt-0" style="color:#c0392b;">Delete Category?</h3>
        <p class="mb-20">
            Are you sure you want to delete this category? <br>
            <span style="font-size:0.85em; color:red;">Note: You cannot delete a category if it has products assigned to it.</span>
        </p>
        <form action="../../../controllers/category_controller.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="category_id" id="delete_category_id" value="">
            <div class="modal-actions center">
                <button type="button" onclick="closeDeleteModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-red">Confirm Delete</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createCategoryModal').style.display = 'flex';
    }

    function closeCreateModal() {
        document.getElementById('createCategoryModal').style.display = 'none';
    }

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

    function openDeleteModal(id) {
        document.getElementById('delete_category_id').value = id;
        document.getElementById('deleteCategoryModal').style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteCategoryModal').style.display = 'none';
    }

    // Auto Open & Outside Click
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('open_create')) openCreateModal();
    window.onclick = function(event) {
        let modals = ['createCategoryModal', 'editCategoryModal', 'deleteCategoryModal'];
        modals.forEach(function(id) {
            let m = document.getElementById(id);
            if (event.target == m) m.style.display = 'none';
        });
    }
</script>

<?php require $path . 'includes/footer.php'; ?>