<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 必须登录才能结账
if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'checkout') {
    $user_id = $_SESSION['user_id'];
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

    if (empty($cart)) {
        header("Location: ../views/member/home.php");
        exit();
    }

    try {
        // 1. 开启事务 (Transaction) - 保证数据要么全写入，要么全不写入
        $pdo->beginTransaction();

        // 2. 重新计算总价 (安全起见，不要只信前端传来的数据)
        $total_amount = 0;
        $product_ids = array_keys($cart);
        // 这一步在实际项目中很关键，防止用户篡改价格
        // 但为了作业简单，我们这里简化处理，假设库存充足，直接从数据库读价格算总账

        // 3. 创建订单主记录 (Insert into orders)
        // 注意：总价我们稍后更新，先插个 0 或者在下面算出总价再插
        // 这里为了简单，我们先算出总价
        foreach ($cart as $pid => $qty) {
            $stmt = $pdo->prepare("SELECT price FROM products WHERE product_id = ?");
            $stmt->execute([$pid]);
            $p = $stmt->fetch();
            $total_amount += ($p['price'] * $qty);
        }

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, order_date) VALUES (?, ?, 'pending', NOW())");
        $stmt->execute([$user_id, $total_amount]);
        $order_id = $pdo->lastInsertId(); // 获取刚生成的订单号

        // 4. 创建订单详情 (Insert into order_items) & 扣减库存
        foreach ($cart as $pid => $qty) {
            // 获取当前价格 (防止以后涨价影响历史订单)
            $stmt = $pdo->prepare("SELECT price FROM products WHERE product_id = ?");
            $stmt->execute([$pid]);
            $product = $stmt->fetch();

            // 插入详情
            $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)";
            $stmt_item = $pdo->prepare($sql_item);
            $stmt_item->execute([$order_id, $pid, $qty, $product['price']]);

            // 扣减库存 (Stock Handling)
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ?");
            $stmt_stock->execute([$qty, $pid]);
        }

        // 5. 提交事务
        $pdo->commit();

        // 6. 清空购物车
        unset($_SESSION['cart']);

        // 7. 跳转到订单成功页或列表页
        header("Location: ../views/member/orders.php?msg=success");
        exit();
    } catch (Exception $e) {
        // 如果出错，回滚所有操作
        $pdo->rollBack();
        die("Order failed: " . $e->getMessage());
    }
}
?>