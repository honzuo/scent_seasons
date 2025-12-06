<?php
session_start();

// 初始化购物车 Session
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// 1. 加入购物车
if ($action == 'add') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    if ($product_id > 0 && $quantity > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity; // 如果已有，增加数量
        } else {
            $_SESSION['cart'][$product_id] = $quantity; // 如果没有，新增
        }
    }
    // 跳回来源页面
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

// 2. 移除商品
if ($action == 'remove') {
    $product_id = intval($_POST['product_id']);
    unset($_SESSION['cart'][$product_id]);
    header("Location: ../views/member/cart.php");
    exit();
}

// 3. 更新数量 (在购物车页面修改数量)
if ($action == 'update') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    if ($quantity > 0) {
        $_SESSION['cart'][$product_id] = $quantity;
    } else {
        unset($_SESSION['cart'][$product_id]);
    }
    header("Location: ../views/member/cart.php");
    exit();
}
