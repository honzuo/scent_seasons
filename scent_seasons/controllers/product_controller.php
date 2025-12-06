<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 只有管理员能访问此逻辑
require_admin();

// 获取要执行的动作 (add, update, delete)
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. 添加产品 (CREATE) ---
    if ($action == 'add') {
        $name = clean_input($_POST['name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $description = clean_input($_POST['description']);

        // 图片上传逻辑
        $image_path = 'default_product.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('prod_') . '.' . $ext;
            $target = "../images/products/" . $new_name;

            // 自动创建文件夹
            if (!is_dir("../images/products/")) mkdir("../images/products/", 0777, true);

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = $new_name;
            }
        }

        $sql = "INSERT INTO products (name, category_id, price, stock, description, image_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $category_id, $price, $stock, $description, $image_path]);

        log_activity($pdo, "Add Product", "Name: $name");

        header("Location: ../views/admin/products/index.php?msg=added");
        exit();
    }

    // --- 2. 删除产品 (DELETE) ---
    elseif ($action == 'delete') {
        $id = intval($_POST['product_id']);

        // 先删除旧图片 (可选优化)
        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if ($prod && $prod['image_path'] != 'default_product.jpg') {
            @unlink("../images/products/" . $prod['image_path']);
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);

        // [新增] 记录日志
        log_activity($pdo, "Delete Product", "Product ID: $id");

        header("Location: ../views/admin/products/index.php?msg=deleted");
        exit();
    }

    // --- 3. 修改产品 (UPDATE) ---
    elseif ($action == 'update') {
        $id = intval($_POST['product_id']);
        $name = clean_input($_POST['name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $description = clean_input($_POST['description']);

        // 1. 先获取旧图片名称，以防用户没上传新图
        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $old_product = $stmt->fetch();
        $final_image = $old_product['image_path'];

        // 2. 检查是否有上传新图片
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('prod_') . '.' . $ext;
            $target = "../images/products/" . $new_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $final_image = $new_name; // 使用新图片
                // (可选) 这里可以加一行代码删除旧图片 unlink(...)
            }
        }

        // 3. 更新数据库
        $sql = "UPDATE products SET name=?, category_id=?, price=?, stock=?, description=?, image_path=? WHERE product_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $category_id, $price, $stock, $description, $final_image, $id]);

        log_activity($pdo, "Update Product", "Product ID: $id");

        header("Location: ../views/admin/products/index.php?msg=updated");
        exit();
    }
}
