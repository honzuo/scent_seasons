<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

$stmt_cats = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $stmt_cats->fetchAll();

$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$sql = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.is_deleted = 0 AND p.name LIKE ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%"]);
$products = $stmt->fetchAll();

$sql_trash = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.is_deleted = 1 ORDER BY p.product_id DESC";
$deleted_products = $pdo->query($sql_trash)->fetchAll();

$page_title = "Product Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="flex-between mb-20">
    <h2>Product List</h2>
    <button onclick="openRecycleBinModal()" class="btn-red" style="background:#e67e22;">
        View Recycle Bin (<?php echo count($deleted_products); ?>)
    </button>
</div>

<div class="flex-between mb-20">
    <button onclick="openCreateModal()" class="btn-blue">+ Add New Product</button>
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" placeholder="Search product..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert <?php echo ($_GET['msg'] == 'deleted_permanent') ? 'alert-error' : 'alert-success'; ?>">
        <?php
        if ($_GET['msg'] == 'added') echo "Product added successfully.";
        elseif ($_GET['msg'] == 'trashed') echo "Product moved to trash.";
        elseif ($_GET['msg'] == 'restored') echo "Product restored from trash.";
        elseif ($_GET['msg'] == 'deleted_permanent') echo "Product permanently deleted.";
        elseif ($_GET['msg'] == 'updated') echo "Product updated successfully.";
        ?>
    </div>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['image_path']): ?>
                            <img src="../../../images/products/<?php echo $p['image_path']; ?>" class="thumbnail">
                        <?php else: ?>
                            No Image
                        <?php endif; ?>
                    </td>
                    <td><?php echo $p['name']; ?></td>
                    <td><?php echo $p['category_name']; ?></td>
                    <td>$<?php echo $p['price']; ?></td>
                    <td><?php echo $p['stock']; ?></td>
                    <td>
                        <button type="button" class="btn-blue"
                            data-id="<?php echo $p['product_id']; ?>"
                            data-name="<?php echo htmlspecialchars($p['name']); ?>"
                            data-cat="<?php echo $p['category_id']; ?>"
                            data-price="<?php echo $p['price']; ?>"
                            data-stock="<?php echo $p['stock']; ?>"
                            data-desc="<?php echo htmlspecialchars($p['description']); ?>"
                            data-img="<?php echo $p['image_path']; ?>"
                            onclick="openEditModal(this)">Edit</button>
                        <button type="button" onclick="openTrashConfirmModal(<?php echo $p['product_id']; ?>)" class="btn-red">Trash</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center">No active products found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<div id="createProductModal" class="modal-overlay">
    <div class="modal-box medium">
        <h3 class="mt-0">Add New Product</h3>
        <form action="../../../controllers/product_controller.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Product Name:</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" required>
                    <option value="">Select Category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-gap-10">
                <div class="form-group w-100">
                    <label>Price ($):</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group w-100">
                    <label>Stock:</label>
                    <input type="number" name="stock" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Image:</label>
                <input type="file" name="image" accept="image/*" required>
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeCreateModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-green">Save Product</button>
            </div>
        </form>
    </div>
</div>

<div id="editProductModal" class="modal-overlay">
    <div class="modal-box medium">
        <h3 class="mt-0">Edit Product</h3>
        <form action="../../../controllers/product_controller.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="product_id" id="edit_product_id">
            <div class="form-group">
                <label>Product Name:</label>
                <input type="text" name="name" id="edit_name" required>
            </div>
            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" id="edit_category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-gap-10">
                <div class="form-group w-100">
                    <label>Price ($):</label>
                    <input type="number" step="0.01" name="price" id="edit_price" required>
                </div>
                <div class="form-group w-100">
                    <label>Stock:</label>
                    <input type="number" name="stock" id="edit_stock" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" id="edit_description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Current Image: <span id="edit_current_img_name" style="font-weight:normal; color:gray; font-size:0.9em;"></span></label>
                <input type="file" name="image" accept="image/*">
            </div>
            <div class="modal-actions">
                <button type="button" onclick="closeEditModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-blue">Update Product</button>
            </div>
        </form>
    </div>
</div>

<div id="trashConfirmModal" class="modal-overlay">
    <div class="modal-box small text-center">
        <h3 class="mt-0" style="color:#c0392b;">Move to Trash?</h3>
        <p class="mb-20">Are you sure you want to move this product to the trash? <br> It will be hidden from the shop.</p>
        <form action="../../../controllers/product_controller.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="trash_confirm_id" value="">
            <div class="modal-actions center">
                <button type="button" onclick="closeTrashConfirmModal()" class="btn-disabled">Cancel</button>
                <button type="submit" class="btn-red">Confirm Trash</button>
            </div>
        </form>
    </div>
</div>

<div id="recycleBinModal" class="modal-overlay">
    <div class="modal-box large">
        <div class="modal-header">
            <h3>Recycle Bin (<?php echo count($deleted_products); ?> items)</h3>
            <button onclick="closeRecycleBinModal()" class="modal-close-btn">&times;</button>
        </div>

        <?php if (count($deleted_products) == 0): ?>
            <p class="text-center">The trash is empty.</p>
        <?php else: ?>
            <table class="table-list">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deleted_products as $dp): ?>
                        <tr>
                            <td>
                                <?php if ($dp['image_path']): ?>
                                    <img src="../../../images/products/<?php echo $dp['image_path']; ?>" style="width:40px; height:40px; object-fit:cover;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $dp['name']; ?></td>
                            <td>$<?php echo $dp['price']; ?></td>
                            <td>
                                <form action="../../../controllers/product_controller.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="product_id" value="<?php echo $dp['product_id']; ?>">
                                    <button type="submit" class="btn-green">Restore</button>
                                </form>
                                <form action="../../../controllers/product_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('WARNING: Permanent Delete?');">
                                    <input type="hidden" name="action" value="delete_permanent">
                                    <input type="hidden" name="product_id" value="<?php echo $dp['product_id']; ?>">
                                    <button type="submit" class="btn-red" style="background:darkred;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="modal-actions">
            <button onclick="closeRecycleBinModal()" class="btn-disabled">Close</button>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createProductModal').style.display = 'flex';
    }

    function closeCreateModal() {
        document.getElementById('createProductModal').style.display = 'none';
    }

    function openTrashConfirmModal(id) {
        document.getElementById('trash_confirm_id').value = id;
        document.getElementById('trashConfirmModal').style.display = 'flex';
    }

    function closeTrashConfirmModal() {
        document.getElementById('trashConfirmModal').style.display = 'none';
    }

    function openEditModal(btn) {
        document.getElementById('edit_product_id').value = btn.getAttribute('data-id');
        document.getElementById('edit_name').value = btn.getAttribute('data-name');
        document.getElementById('edit_category_id').value = btn.getAttribute('data-cat');
        document.getElementById('edit_price').value = btn.getAttribute('data-price');
        document.getElementById('edit_stock').value = btn.getAttribute('data-stock');
        document.getElementById('edit_description').value = btn.getAttribute('data-desc');
        let img = btn.getAttribute('data-img');
        document.getElementById('edit_current_img_name').innerText = img ? "(" + img + ")" : "(No Image)";
        document.getElementById('editProductModal').style.display = 'flex';
    }

    function closeEditModal() {
        document.getElementById('editProductModal').style.display = 'none';
    }

    function openRecycleBinModal() {
        document.getElementById('recycleBinModal').style.display = 'flex';
    }

    function closeRecycleBinModal() {
        document.getElementById('recycleBinModal').style.display = 'none';
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('open_trash')) openRecycleBinModal();

    window.onclick = function(event) {
        let modals = ['createProductModal', 'editProductModal', 'trashConfirmModal', 'recycleBinModal'];
        modals.forEach(id => {
            if (event.target == document.getElementById(id)) document.getElementById(id).style.display = 'none';
        });
    }
</script>

<?php require $path . 'includes/footer.php'; ?>