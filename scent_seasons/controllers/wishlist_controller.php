<?php
// controllers/wishlist_controller.php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 必须登录才能操作收藏夹
if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// --- 1. 添加到收藏夹 (ADD) ---
if ($action == 'add') {
    $product_id = intval($_POST['product_id']);

    if ($product_id > 0) {
        try {
            // 检查是否已经在收藏夹
            $stmt = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);

            if ($stmt->rowCount() == 0) {
                // 不存在，添加到收藏夹
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

    // 跳回产品详情页
    header("Location: ../views/member/product_detail.php?id=$product_id&wishlist=$message");
    exit();
}

// --- 2. 从收藏夹移除 (REMOVE) ---
if ($action == 'remove') {
    $product_id = intval($_POST['product_id']);

    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);

    // 检查是从哪个页面来的
    $from = isset($_POST['from']) ? $_POST['from'] : 'profile';

    if ($from == 'detail') {
        header("Location: ../views/member/product_detail.php?id=$product_id&wishlist=removed");
    } else {
        // 从 Profile 页面删除，跳回 Profile
        header("Location: ../views/member/profile.php?msg=wishlist_removed");
    }
    exit();
}
