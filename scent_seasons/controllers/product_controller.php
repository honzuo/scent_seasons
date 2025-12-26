<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

require_admin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 公用函数：处理多图上传 ---
    function uploadGalleryImages($pdo, $product_id, $files) {
        if (isset($files['name']) && is_array($files['name'])) {
            $total_files = count($files['name']);
            
            for ($i = 0; $i < $total_files; $i++) {
                if ($files['error'][$i] == 0) {
                    $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    // 只允许图片
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $new_name = uniqid('gallery_') . '.' . $ext;
                        $target = "../images/products/" . $new_name;
                        
                        // 确保目录存在
                        if (!is_dir("../images/products/")) mkdir("../images/products/", 0777, true);
                        
                        if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                            // 插入数据库
                            $stmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                            $stmt->execute([$product_id, $new_name]);
                        }
                    }
                }
            }
        }
    }

    // ==========================================
    // [新增] 视频处理逻辑 (保留你之前的代码)
    // ==========================================
    $final_video_db_id = null;
    if (!empty($_POST['new_video_url'])) {
        $newUrl = clean_input($_POST['new_video_url']);
        $extractedId = getYoutubeId($newUrl); 
        if ($extractedId) {
            $check = $pdo->prepare("SELECT id FROM youtube_videos WHERE video_id = ?");
            $check->execute([$extractedId]);
            $exist = $check->fetch();
            if ($exist) {
                $final_video_db_id = $exist['id'];
            } else {
                $stmt_vid = $pdo->prepare("INSERT INTO youtube_videos (video_id, url) VALUES (?, ?)");
                $stmt_vid->execute([$extractedId, $newUrl]);
                $final_video_db_id = $pdo->lastInsertId();
            }
        }
    } elseif (!empty($_POST['existing_video_id'])) {
        $final_video_db_id = intval($_POST['existing_video_id']);
    }

    // --- 1. 添加产品 (CREATE) ---
    if ($action == 'add') {
        $name = clean_input($_POST['name']);
        $category_id = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $description = clean_input($_POST['description']);

        // 处理主图 (Main Image)
        $image_path = 'default_product.jpg';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $new_name = uniqid('prod_') . '.' . $ext;
            $target = "../images/products/" . $new_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $image_path = $new_name;
            }
        }

        $sql = "INSERT INTO products (name, category_id, price, stock, description, image_path, youtube_video_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $category_id, $price, $stock, $description, $image_path, $final_video_db_id]);
        
        $new_product_id = $pdo->lastInsertId();

        // [新增] 处理相册多图上传
        if (isset($_FILES['gallery_images'])) {
            uploadGalleryImages($pdo, $new_product_id, $_FILES['gallery_images']);
        }

        log_activity($pdo, "Add Product", "Name: $name");
        header("Location: ../views/admin/products/index.php?msg=added");
        exit();
    }

    // --- 2. 软删除 (Soft Delete) ---
    elseif ($action == 'delete') {
        $id = intval($_POST['product_id']);
        // ... (保持原有逻辑)
        $stmt = $pdo->prepare("UPDATE products SET is_deleted = 1 WHERE product_id = ?");
        $stmt->execute([$id]);
        header("Location: ../views/admin/products/index.php?msg=trashed");
        exit();
    }

    // --- 3. 恢复 (RESTORE) ---
    elseif ($action == 'restore') {
        $id = intval($_POST['product_id']);
        // ... (保持原有逻辑)
        $stmt = $pdo->prepare("UPDATE products SET is_deleted = 0 WHERE product_id = ?");
        $stmt->execute([$id]);
        header("Location: ../views/admin/products/index.php?msg=restored&open_trash=1");
        exit();
    }

    // --- 4. 彻底删除 (PERMANENT DELETE) ---
    elseif ($action == 'delete_permanent') {
        $id = intval($_POST['product_id']);
        
        // 删除主图
        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE product_id = ?");
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if ($prod && $prod['image_path'] != 'default_product.jpg') {
            $file = "../images/products/" . $prod['image_path'];
            if (file_exists($file)) unlink($file);
        }

        // [新增] 删除关联的相册图片文件
        $stmt_imgs = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt_imgs->execute([$id]);
        $gallery_imgs = $stmt_imgs->fetchAll();
        foreach ($gallery_imgs as $g_img) {
            $g_file = "../images/products/" . $g_img['image_path'];
            if (file_exists($g_file)) unlink($g_file);
        }

        // 级联删除会处理数据库记录，但我们手动删除了文件
        $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $stmt->execute([$id]);

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

        $sql = "UPDATE products SET name=?, category_id=?, price=?, stock=?, description=?, image_path=?, youtube_video_id=? WHERE product_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $category_id, $price, $stock, $description, $final_image, $final_video_db_id, $id]);

        // [新增] 处理相册多图上传 (追加)
        if (isset($_FILES['gallery_images'])) {
            uploadGalleryImages($pdo, $id, $_FILES['gallery_images']);
        }

        log_activity($pdo, "Update Product", "Product ID: $id, Name: $name");
        header("Location: ../views/admin/products/index.php?msg=updated");
        exit();
    }

    // --- 6. [新增] AJAX 删除单张相册图片 ---
    elseif ($action == 'delete_gallery_image') {
        $image_id = intval($_POST['image_id']);
        
        // 获取文件名以删除文件
        $stmt = $pdo->prepare("SELECT image_path FROM product_images WHERE id = ?");
        $stmt->execute([$image_id]);
        $img = $stmt->fetch();

        if ($img) {
            $file = "../images/products/" . $img['image_path'];
            if (file_exists($file)) unlink($file);
            
            // 删除数据库记录
            $delStmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
            $delStmt->execute([$image_id]);
            echo "success";
        } else {
            echo "error";
        }
        exit();
    }
}
?>