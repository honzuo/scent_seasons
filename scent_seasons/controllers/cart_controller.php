<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 必须登录才能操作购物车
if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// 1. 加入购物车 (ADD)
if ($action == 'add') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($product_id > 0 && $quantity > 0) {
        // 检查该用户是否已经加过这个商品
        $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            // 如果已存在，更新数量 (原有数量 + 新增数量)
            $new_qty = $existing['quantity'] + $quantity;
            $update = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $update->execute([$new_qty, $user_id, $product_id]);
        } else {
            // 如果不存在，插入新记录
            $insert = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
            $insert->execute([$user_id, $product_id, $quantity]);
        }
    }

    // --- 修改点：添加成功后，直接跳转到购物车页面 ---
    header("Location: ../views/member/cart.php");
    exit();
}

// 2. 移除商品 (REMOVE)
if ($action == 'remove') {
    $product_id = intval($_POST['product_id']);

    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    header("Location: ../views/member/cart.php");
    exit();
}

// 3. 更新数量 (UPDATE)
if ($action == 'update') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($quantity > 0) {
        $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$quantity, $user_id, $product_id]);
    } else {
        // 如果数量改为0或负数，直接删除
        $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
    }

    header("Location: ../views/member/cart.php");
    exit();
}
?>