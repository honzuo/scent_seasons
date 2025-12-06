<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'checkout') {
    $user_id = $_SESSION['user_id'];

    // 获取前端传来的选中的商品 ID 数组
    $selected_ids = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];

    // 安全检查：如果没选东西，踢回去
    if (empty($selected_ids) || !is_array($selected_ids)) {
        header("Location: ../views/member/cart.php");
        exit();
    }

    try {
        // 1. 构建动态 SQL 查询选中的商品
        // 我们需要生成类似 (?, ?, ?) 的占位符
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));

        $sql = "SELECT c.quantity, p.product_id, p.price, p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.product_id 
                WHERE c.user_id = ? AND c.product_id IN ($placeholders)";

        // 准备参数：第一个是 user_id，后面跟着所有的 product_id
        $params = array_merge([$user_id], $selected_ids);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll();

        if (empty($cart_items)) {
            die("Error: No items found for checkout.");
        }

        // 2. 开启事务
        $pdo->beginTransaction();

        // 3. 计算这些选中商品的总价
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }

        // 4. 创建订单
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, order_date) VALUES (?, ?, 'pending', NOW())");
        $stmt->execute([$user_id, $total_amount]);
        $order_id = $pdo->lastInsertId();

        // 5. 插入订单详情并扣减库存
        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);

        $sql_stock = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
        $stmt_stock = $pdo->prepare($sql_stock);

        foreach ($cart_items as $item) {
            // 插入 Order Item
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

            // 扣减库存
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        // 6. 关键修改：只从购物车删除“已结算”的商品
        // (未勾选的商品要保留在 cart 表里)
        $sql_delete = "DELETE FROM cart WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute($params); // 参数也是 user_id + selected_ids

        // 7. 提交事务
        $pdo->commit();

        header("Location: ../views/member/orders.php?msg=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Order failed: " . $e->getMessage());
    }
}
