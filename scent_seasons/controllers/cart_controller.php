<?php
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
    $quantity = intval($_POST['quantity']);

    if ($product_id > 0 && $quantity > 0) {
      
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
          
            $new_qty = $existing['quantity'] + $quantity;
            $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $update->execute([$new_qty, $user_id, $product_id]);
        } else {
           
            $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert->execute([$user_id, $product_id, $quantity]);
        }
    }

   
    header("Location: ../views/member/cart.php");
    exit();
}


if ($action == 'remove') {
    $product_id = intval($_POST['product_id']);

    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    header("Location: ../views/member/cart.php");
    exit();
}


if ($action == 'update') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    }

    header("Location: ../views/member/cart.php");
    exit();
}
?>