<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';


require_admin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {


    if ($action == 'add') {
        $name = clean_input($_POST['category_name']);

        if (!empty($name)) {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
            $stmt->execute([$name]);

            log_activity($pdo, "Add Category", "Name: $name");
            header("Location: ../views/admin/categories/index.php?msg=added");
        } else {
           
            header("Location: ../views/admin/categories/index.php?error=empty&open_create=1");
        }
        exit();
    }

   
    elseif ($action == 'update') {
        $id = intval($_POST['category_id']);
        $name = clean_input($_POST['category_name']);

        if (!empty($name)) {
            $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE category_id = ?");
            $stmt->execute([$name, $id]);

            log_activity($pdo, "Update Category", "ID: $id, Name: $name");
            header("Location: ../views/admin/categories/index.php?msg=updated");
        } else {
            header("Location: ../views/admin/categories/index.php?error=empty");
        }
        exit();
    }

   
    elseif ($action == 'delete') {
        $id = intval($_POST['category_id']);

    
        $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ?");
        $stmt->execute([$id]);
        $cat = $stmt->fetch();
        $cat_name = $cat ? $cat['category_name'] : 'Unknown';

        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
            $stmt->execute([$id]);

            log_activity($pdo, "Delete Category", "ID: $id, Name: $cat_name");
            header("Location: ../views/admin/categories/index.php?msg=deleted");
        } catch (PDOException $e) {
            
            header("Location: ../views/admin/categories/index.php?error=in_use");
        }
        exit();
    }
}
?>