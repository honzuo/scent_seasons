<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 获取分类供下拉菜单使用
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

$page_title = "Product Management";
$path = "../../../"; // 注意这里是三层！
$extra_css = "admin.css"; // 引用 admin.css

require $path . 'includes/header.php';
?>

<div class="container">
    <h2>Add New Perfume</h2>
    <form action="../../../controllers/product_controller.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
            <label>Product Name:</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Category:</label>
            <select name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['category_id']; ?>">
                        <?php echo $cat['category_name']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Price ($):</label>
            <input type="number" step="0.01" name="price" required>
        </div>

        <div class="form-group">
            <label>Stock Quantity:</label>
            <input type="number" name="stock" required>
        </div>

        <div class="form-group">
            <label>Description:</label>
            <textarea name="description" rows="4" style="width:100%"></textarea>
        </div>

        <div class="form-group">
            <label>Product Image:</label>
            <input type="file" name="image" accept="image/*" required>
        </div>

        <br>
        <button type="submit" style="background:green; color:white; padding:10px;">Save Product</button>
        <a href="index.php">Cancel</a>
    </form>
</div>

<?php require $path . 'includes/footer.php'; ?>