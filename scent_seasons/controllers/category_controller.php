<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 强制管理员权限
require_admin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- 1. 添加分类 (ADD) ---
    if ($action == 'add') {
        $name = clean_input($_POST['category_name']);

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->execute([$name]);
            header("Location: ../views/admin/categories/index.php?msg=added");
        } else {
            header("Location: ../views/admin/categories/create.php?error=empty");
        }
        exit();
    }

    // --- 2. 修改分类 (UPDATE) ---
    elseif ($action == 'update') {
        $id = intval($_POST['category_id']);
        $name = clean_input($_POST['category_name']);

        if (!empty($name)) {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
            $stmt->execute([$name, $id]);
            header("Location: ../views/admin/categories/index.php?msg=updated");
        } else {
            header("Location: ../views/admin/categories/edit.php?id=$id&error=empty");
        }
        exit();
    }

    // --- 3. 删除分类 (DELETE) ---
    elseif ($action == 'delete') {
        $id = intval($_POST['category_id']);

        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$id]);
            header("Location: ../views/admin/categories/index.php?msg=deleted");
        } catch (PDOException $e) {
            // 如果分类下有产品，是删不掉的 (外键约束)
            // 跳转回去并显示错误
            header("Location: ../views/admin/categories/index.php?error=in_use");
        }
        exit();
    }
}
?>