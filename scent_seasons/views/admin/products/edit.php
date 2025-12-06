<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

// 1. 获取当前产品信息
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found!");
}

// 2. 获取分类列表
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="../../../css/style.css">
</head>

<body>
    <div class="container">
        <h2>Edit Product: <?php echo $product['name']; ?></h2>

        <form action="../../../controllers/product_controller.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">

            <div class="form-group">
                <label>Product Name:</label>
                <input type="text" name="name" value="<?php echo $product['name']; ?>" required>
            </div>

            <div class="form-group">
                <label>Category:</label>
                <select name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"
                            <?php if ($cat['category_id'] == $product['category_id']) echo 'selected'; ?>>
                            <?php echo $cat['category_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Price ($):</label>
                <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>
            </div>

            <div class="form-group">
                <label>Stock Quantity:</label>
                <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
            </div>

            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="4" style="width:100%"><?php echo $product['description']; ?></textarea>
            </div>

            <div class="form-group">
                <label>Current Image:</label><br>
                <img src="../../../images/products/<?php echo $product['image_path']; ?>" style="width:100px;">
                <br><br>
                <label>Change Image (Optional):</label>
                <input type="file" name="image" accept="image/*">
                <p style="font-size:0.8em; color:gray;">Leave empty to keep current image.</p>
            </div>

            <br>
            <button type="submit" style="background:blue; color:white; padding:10px;">Update Product</button>
            <a href="index.php">Cancel</a>
        </form>
    </div>
</body>

</html>