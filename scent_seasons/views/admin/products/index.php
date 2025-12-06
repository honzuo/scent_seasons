<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 1. 判断是否查看回收站
$show_trash = (isset($_GET['status']) && $_GET['status'] == 'trash');
$is_deleted_val = $show_trash ? 1 : 0;

// 2. 获取分类 (弹窗用)
$stmt_cats = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $stmt_cats->fetchAll();

// 3. 搜索逻辑
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

$sql = "SELECT p.*, c.category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        WHERE p.is_deleted = ? AND p.name LIKE ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$is_deleted_val, "%$search%"]);
$products = $stmt->fetchAll();

$page_title = $show_trash ? "Recycle Bin" : "Product Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center;">
    <h2><?php echo $show_trash ? "Recycle Bin (Deleted Products)" : "Product List"; ?></h2>

    <div>
        <?php if ($show_trash): ?>
            <a href="index.php" class="btn-blue" style="background:#555;">&larr; Back to Active List</a>
        <?php else: ?>
            <a href="index.php?status=trash" class="btn-red" style="background:#e67e22;">View Trash &rarr;</a>
        <?php endif; ?>
    </div>
</div>

<div style="display:flex; justify-content:space-between; margin: 20px 0;">
    <?php if (!$show_trash): ?>
        <button onclick="openCreateModal()" class="btn-blue" style="cursor:pointer;">+ Add New Product</button>
    <?php else: ?>
        <div></div>
    <?php endif; ?>

    <form method="GET" action="">
        <?php if ($show_trash): ?><input type="hidden" name="status" value="trash"><?php endif; ?>
        <input type="text" name="search" placeholder="Search product..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <p style="padding:10px; border-radius:4px; font-weight:bold; 
        <?php echo ($_GET['msg'] == 'deleted_permanent') ? 'background:#ffebee; color:red;' : 'background:#e8f5e9; color:green;'; ?>">
        <?php
        if ($_GET['msg'] == 'added') echo "Product added successfully.";
        elseif ($_GET['msg'] == 'trashed') echo "Product moved to trash.";
        elseif ($_GET['msg'] == 'restored') echo "Product restored successfully.";
        elseif ($_GET['msg'] == 'deleted_permanent') echo "Product permanently deleted.";
        elseif ($_GET['msg'] == 'updated') echo "Product updated successfully.";
        ?>
    </p>
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
                        <?php if ($show_trash): ?>
                            <form action="../../../controllers/product_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('Restore this product?');">
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                <button type="submit" class="btn-green" style="font-size:0.8em;">Restore</button>
                            </form>

                            <form action="../../../controllers/product_controller.php" method="POST" style="display:inline; margin-left:5px;" onsubmit="return confirm('WARNING: This will delete the product PERMANENTLY. Continue?');">
                                <input type="hidden" name="action" value="delete_permanent">
                                <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                <button type="submit" class="btn-red" style="font-size:0.8em; background:darkred;">Delete</button>
                            </form>
                        <?php else: ?>
                            <a href="edit.php?id=<?php echo $p['product_id']; ?>" class="btn-blue" style="font-size:0.8em;">Edit</a>

                            <button type="button" onclick="openTrashModal(<?php echo $p['product_id']; ?>)" class="btn-red" style="font-size:0.8em;">Trash</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center; padding:20px;">No products found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>


<div id="createProductModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:8px; width:500px; max-height:90vh; overflow-y:auto;">
        <h3 style="margin-top:0;">Add New Product</h3>

        <form action="../../../controllers/product_controller.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Product Name:</label>
                <input type="text" name="name" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
            </div>

            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                    <option value="">Select Category...</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>">
                            <?php echo $cat['category_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Price ($):</label>
                    <input type="number" step="0.01" name="price" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Stock:</label>
                    <input type="number" name="stock" required style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;">
                </div>
            </div>

            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="3" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; font-family:inherit;"></textarea>
            </div>

            <div class="form-group">
                <label>Image:</label>
                <input type="file" name="image" accept="image/*" required style="width:100%; padding:8px; background:#f9f9f9; border:1px dashed #ccc;">
            </div>

            <div style="text-align:right; margin-top:20px;">
                <button type="button" onclick="closeCreateModal()" class="btn-blue" style="background:gray; margin-right:10px;">Cancel</button>
                <button type="submit" class="btn-green">Save Product</button>
            </div>
        </form>
    </div>
</div>

<div id="trashProductModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:2000;">
    <div style="background:white; padding:30px; border-radius:8px; width:400px; text-align:center;">
        <h3 style="margin-top:0; color:#c0392b;">Move to Trash?</h3>
        <p style="color:gray; margin-bottom:20px;">
            Are you sure you want to move this product to the trash? <br>
            It will be hidden from the shop but can be restored later.
        </p>

        <form action="../../../controllers/product_controller.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="trash_product_id" value="">

            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" onclick="closeTrashModal()" class="btn-blue" style="background:gray;">Cancel</button>
                <button type="submit" class="btn-red">Confirm Trash</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Create Modal Functions ---
    function openCreateModal() {
        document.getElementById('createProductModal').style.display = 'flex';
    }

    function closeCreateModal() {
        document.getElementById('createProductModal').style.display = 'none';
    }

    // --- Trash Modal Functions ---
    function openTrashModal(id) {
        // 把点击的商品 ID 填入隐藏输入框
        document.getElementById('trash_product_id').value = id;
        // 显示弹窗
        document.getElementById('trashProductModal').style.display = 'flex';
    }

    function closeTrashModal() {
        document.getElementById('trashProductModal').style.display = 'none';
    }

    // 点击背景关闭
    window.onclick = function(event) {
        let createModal = document.getElementById('createProductModal');
        let trashModal = document.getElementById('trashProductModal');

        if (event.target == createModal) {
            closeCreateModal();
        }
        if (event.target == trashModal) {
            closeTrashModal();
        }
    }
</script>

<?php require $path . 'includes/footer.php'; ?>