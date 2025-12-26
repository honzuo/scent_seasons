<?php
// controllers/wishlist_controller.php
session_start();
require '../config/database.php';
require '../includes/functions.php';


if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';


if ($action == 'add') {
    $product_id = intval($_POST['product_id']);

    if ($product_id > 0) {
        try {
      
            $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);

            if ($stmt->rowCount() == 0) {
              
                $insert = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
                $insert->execute([$user_id, $product_id]);
                $message = "added";
            } else {
                $message = "exists";
            }
        } catch (PDOException $e) {
            $message = "error";
        }
    }

 
    header("Location: ../views/member/product_detail.php?id=$product_id&wishlist=$message");
    exit();
}


if ($action == 'remove') {
    $product_id = intval($_POST['product_id']);

    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);


    $from = isset($_POST['from']) ? $_POST['from'] : 'profile';

    if ($from == 'detail') {
        header("Location: ../views/member/product_detail.php?id=$product_id&wishlist=removed");
    } else {
       
        header("Location: ../views/member/profile.php?msg=wishlist_removed");
    }
    exit();
}
