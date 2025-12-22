<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 只有管理员能访问
require_admin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ==========================================
    // [新增] 视频处理逻辑 (由 Step 3 添加)
    // ==========================================
    $final_video_db_id = null; // 默认为空

    // 1. 如果管理员输入了新的 YouTube URL
    if (!empty($_POST['new_video_url'])) {
        $newUrl = clean_input($_POST['new_video_url']);
        // 注意：这里调用了 Step 2 在 functions.php 中添加的 getYoutubeId 函数
        $extractedId = getYoutubeId($newUrl); 

        if ($extractedId) {
            // 先检查数据库是否已存在该视频ID，避免重复
            $check = $pdo->prepare("SELECT id FROM youtube_videos WHERE video_id = ?");
            $check->execute([$extractedId]);
            $exist = $check->fetch();

            if ($exist) {
                $final_video_db_id = $exist['id'];
            } else {
                // 如果不存在，插入新视频
                $stmt_vid = $pdo->prepare("INSERT INTO youtube_videos (video_id, url) VALUES (?, ?)");
                $stmt_vid->execute([$extractedId, $newUrl]);
                $final_video_db_id = $pdo->lastInsertId();
            }
        }
    } 
    // 2. 如果没有输入新URL，但从下拉菜单选择了现有视频
    elseif (!empty($_POST['existing_video_id'])) {
        $final_video_db_id = intval($_POST['existing_video_id']);
    }
    // ==========================================
    // [结束] 视频处理逻辑
    // ==========================================


    // --- 1. 添加产品 (CREATE) ---
    if ($action == 'add') {
        $name = clean_input($_POST['name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $description = clean_input($_POST['description']);

        $image_path = 'default_product.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('prod_') . '.' . $ext;
            $target = "../images/products/" . $new_name;
            if (!is_dir("../images/products/")) mkdir("../images/products/", 0777, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = $new_name;
            }
        }

        // [修改] SQL 语句加入 youtube_video_id
        $sql = "INSERT INTO products (name, category_id, price, stock, description, image_path, youtube_video_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        // [修改] 参数数组加入 $final_video_db_id
        $stmt->execute([$name, $category_id, $price, $stock, $description, $image_path, $final_video_db_id]);

        log_activity($pdo, "Add Product", "Name: $name");
        header("Location: ../views/admin/products/index.php?msg=added");
        exit();
    }

    // --- 2. 软删除 (Soft Delete) ---
    elseif ($action == 'delete') {
        $id = intval($_POST['product_id']);

        $stmt = $pdo->prepare("SELECT name FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        $prod_name = $prod ? $prod['name'] : "Unknown";

        // 标记为已删除
        $stmt = $pdo->prepare("UPDATE products SET is_deleted = 1 WHERE product_id = ?");
        $stmt->execute([$id]);

        log_activity($pdo, "Soft Delete Product", "Product ID: $id, Name: $prod_name");
        header("Location: ../views/admin/products/index.php?msg=trashed");
        exit();
    }

    // --- 3. 恢复 (RESTORE) ---
    elseif ($action == 'restore') {
        $id = intval($_POST['product_id']);

        $stmt = $pdo->prepare("SELECT name FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        $prod_name = $prod ? $prod['name'] : "Unknown";

        // 恢复为正常状态
        $stmt = $pdo->prepare("UPDATE products SET is_deleted = 0 WHERE product_id = ?");
        $stmt->execute([$id]);

        log_activity($pdo, "Restore Product", "Product ID: $id, Name: $prod_name");

        // [关键] 恢复后带上 open_trash=1，自动重新打开回收站弹窗
        header("Location: ../views/admin/products/index.php?msg=restored&open_trash=1");
        exit();
    }

    // --- 4. 彻底删除 (PERMANENT DELETE) ---
    elseif ($action == 'delete_permanent') {
        $id = intval($_POST['product_id']);

        $stmt = $pdo->prepare("SELECT image_path, name FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        $prod_name = $prod ? $prod['name'] : "Unknown";

        if ($prod && $prod['image_path'] != 'default_product.jpg') {
            $file = "../images/products/" . $prod['image_path'];
            if (file_exists($file)) unlink($file);
        }

        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);

        log_activity($pdo, "Permanent Delete Product", "Product ID: $id, Name: $prod_name");

        // [关键] 彻底删除后带上 open_trash=1
        header("Location: ../views/admin/products/index.php?msg=deleted_permanent&open_trash=1");
        exit();
    }

    // --- 5. 修改产品 (UPDATE) ---
    elseif ($action == 'update') {
        $id = intval($_POST['product_id']);
        $name = clean_input($_POST['name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $description = clean_input($_POST['description']);

        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $old_product = $stmt->fetch();
        $final_image = $old_product['image_path'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('prod_') . '.' . $ext;
            $target = "../images/products/" . $new_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $final_image = $new_name;
            }
        }

        // [修改] SQL 语句加入 youtube_video_id
        $sql = "UPDATE products SET name=?, category_id=?, price=?, stock=?, description=?, image_path=?, youtube_video_id=? WHERE product_id=?";
        $stmt = $pdo->prepare($sql);
        // [修改] 参数数组加入 $final_video_db_id
        $stmt->execute([$name, $category_id, $price, $stock, $description, $final_image, $final_video_db_id, $id]);

        log_activity($pdo, "Update Product", "Product ID: $id, Name: $name");
        header("Location: ../views/admin/products/index.php?msg=updated");
        exit();
    }
}
?>