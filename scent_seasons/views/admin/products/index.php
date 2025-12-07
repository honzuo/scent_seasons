<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 1. 获取分类 (供 Create/Edit 弹窗下拉菜单使用)
$stmt_cats = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC");
$categories = $stmt_cats->fetchAll();

// 2. 搜索逻辑 (只针对活跃商品)
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// 3. 查询【活跃商品】 (显示在主列表)
$sql = "SELECT p.*, c.category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.category_id 
        WHERE p.is_deleted = 0 AND p.name LIKE ?";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%"]);
$products = $stmt->fetchAll();

// 4. 查询【回收站商品】 (显示在 Recycle Bin 弹窗)
$sql_trash = "SELECT p.*, c.category_name 
              FROM products p 
              JOIN categories c ON p.category_id = c.category_id 
              WHERE p.is_deleted = 1 ORDER BY p.product_id DESC";
$stmt_trash = $pdo->query($sql_trash);
$deleted_products = $stmt_trash->fetchAll();

$page_title = "Product Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="flex-between mb-20">
    <h2>Product List</h2>

    <button id="btn-open-recycle" class="btn-orange">
        View Recycle Bin (<?php echo count($deleted_products); ?>)
    </button>
</div>

<div class="flex-between mb-20">
    <button id="btn-open-create" class="btn-blue">+ Add New Product</button>

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
                        <button type="button"
                            class="btn-blue js-open-edit"
                            data-id="<?php echo $p['product_id']; ?>"
                            data-name="<?php echo htmlspecialchars($p['name']); ?>"
                            data-cat="<?php echo $p['category_id']; ?>"
                            data-price="<?php echo $p['price']; ?>"
                            data-stock="<?php echo $p['stock']; ?>"
                            data-desc="<?php echo htmlspecialchars($p['description']); ?>"
                            data-img="<?php echo $p['image_path']; ?>">
                            Edit
                        </button>

                        <button type="button"
                            class="btn-red js-open-trash"
                            data-id="<?php echo $p['product_id']; ?>">
                            Trash
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" class="text-center text-gray" style="padding:20px;">No active products found.</td>
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
                        <option value="<?php echo $cat['category_id']; ?>">
                            <?php echo $cat['category_name']; ?>
                        </option>
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
                <input type="file" name="image" accept="image/*" required class="input-file-custom">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-disabled js-close-modal">Cancel</button>
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
                        <option value="<?php echo $cat['category_id']; ?>">
                            <?php echo $cat['category_name']; ?>
                        </option>
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
                <label>Current Image: <span id="edit_current_img_name" class="text-preview-info"></span></label>
                <br>
                <small class="text-muted">Upload new image to replace:</small>
                <input type="file" name="image" accept="image/*" class="input-file-custom">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-disabled js-close-modal">Cancel</button>
                <button type="submit" class="btn-blue">Update Product</button>
            </div>
        </form>
    </div>
</div>

<div id="trashConfirmModal" class="modal-overlay">
    <div class="modal-box small text-center">
        <h3 class="mt-0 text-red-bold" style="color:#c0392b;">Move to Trash?</h3>
        <p class="text-gray mb-20">
            Are you sure you want to move this product to the trash? <br>
            It will be hidden from the shop but can be restored later.
        </p>

        <form action="../../../controllers/product_controller.php" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="product_id" id="trash_confirm_id" value="">

            <div class="modal-actions center">
                <button type="button" class="btn-disabled js-close-modal">Cancel</button>
                <button type="submit" class="btn-red">Confirm Trash</button>
            </div>
        </form>
    </div>
</div>

<div id="recycleBinModal" class="modal-overlay">
    <div class="modal-box large">
        <div class="modal-header">
            <h3>Recycle Bin (<?php echo count($deleted_products); ?> items)</h3>
            <button class="modal-close-btn js-close-modal">&times;</button>
        </div>

        <?php if (count($deleted_products) == 0): ?>
            <p class="text-center text-gray" style="padding:20px;">The trash is empty.</p>
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
                                    <img src="../../../images/products/<?php echo $dp['image_path']; ?>" class="thumbnail" style="width:40px; height:40px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $dp['name']; ?></td>
                            <td>$<?php echo $dp['price']; ?></td>
                            <td>
                                <form action="../../../controllers/product_controller.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="product_id" value="<?php echo $dp['product_id']; ?>">
                                    <button type="submit" class="btn-green" style="font-size:0.8em; padding:5px 10px;">Restore</button>
                                </form>

                                <form action="../../../controllers/product_controller.php" method="POST" style="display:inline; margin-left:5px;" onsubmit="return confirm('WARNING: This will delete the product PERMANENTLY. Continue?');">
                                    <input type="hidden" name="action" value="delete_permanent">
                                    <input type="hidden" name="product_id" value="<?php echo $dp['product_id']; ?>">
                                    <button type="submit" class="btn-darkred">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div class="modal-actions">
            <button class="btn-disabled js-close-modal">Close</button>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // --- 1. 打开模态框逻辑 ---

        // 打开 "Add New Product"
        $('#btn-open-create').on('click', function() {
            $('#createProductModal').css('display', 'flex');
        });

        // 打开 "Recycle Bin"
        $('#btn-open-recycle').on('click', function() {
            $('#recycleBinModal').css('display', 'flex');
        });

        // 打开 "Edit" (使用事件委托，兼容未来可能动态添加的行)
        $(document).on('click', '.js-open-edit', function() {
            // 从按钮的 data-* 属性中获取数据
            let btn = $(this);
            let id = btn.data('id');
            let name = btn.data('name');
            let cat = btn.data('cat');
            let price = btn.data('price');
            let stock = btn.data('stock');
            let desc = btn.data('desc');
            let img = btn.data('img');

            // 填充表单
            $('#edit_product_id').val(id);
            $('#edit_name').val(name);
            $('#edit_category_id').val(cat);
            $('#edit_price').val(price);
            $('#edit_stock').val(stock);
            $('#edit_description').val(desc);

            let imgText = img ? "(" + img + ")" : "(No Image)";
            $('#edit_current_img_name').text(imgText);

            // 显示模态框
            $('#editProductModal').css('display', 'flex');
        });

        // 打开 "Trash Confirm"
        $(document).on('click', '.js-open-trash', function() {
            let id = $(this).data('id'); // 获取 data-id
            $('#trash_confirm_id').val(id);
            $('#trashConfirmModal').css('display', 'flex');
        });

        // --- 2. 关闭模态框逻辑 ---

        // 点击所有带 js-close-modal 类的按钮（Cancel/Close/X）
        $(document).on('click', '.js-close-modal', function() {
            // 找到最近的父级 .modal-overlay 并隐藏
            $(this).closest('.modal-overlay').css('display', 'none');
        });

        // 点击模态框外部区域关闭
        $(window).on('click', function(event) {
            // 检查点击的目标是否是模态框背景层 (modal-overlay)
            if ($(event.target).hasClass('modal-overlay')) {
                $(event.target).css('display', 'none');
            }
        });

        // --- 3. 自动打开逻辑 (处理 URL 参数) ---

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('open_trash')) {
            $('#recycleBinModal').css('display', 'flex');
        }
    });
</script>

<?php require $path . 'includes/footer.php'; ?>