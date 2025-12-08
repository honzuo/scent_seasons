<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// --- 1. [Admin] 更新订单状态 ---
if ($action == 'update_status') {
    require_admin(); // 必须是管理员

    $order_id = intval($_POST['order_id']);
    $status = clean_input($_POST['status']);

    // 更新状态
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);

    // 记录日志
    log_activity($pdo, "Update Order Status", "Order ID: $order_id to $status");

    // 跳回订单列表
    // 检查是否有筛选用户，如果有，跳回去时也带上
    $redirect = "../views/admin/orders/index.php?msg=updated";
    if (isset($_POST['filter_user_id']) && $_POST['filter_user_id'] > 0) {
        $redirect .= "&user_id=" . $_POST['filter_user_id'];
    }

    header("Location: $redirect");
    exit();
}

// --- 2. [Member] 结账 (Checkout) ---
if ($action == 'checkout') {
    $user_id = $_SESSION['user_id'];
    $selected_ids = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];

    if (empty($selected_ids) || !is_array($selected_ids)) {
        header("Location: ../views/member/cart.php");
        exit();
    }

    try {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $sql = "SELECT c.quantity, p.product_id, p.price, p.stock 
                FROM cart c 
                JOIN products p ON c.product_id = p.product_id 
                WHERE c.user_id = ? AND c.product_id IN ($placeholders)";
        $params = array_merge([$user_id], $selected_ids);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $cart_items = $stmt->fetchAll();

        if (empty($cart_items)) {
            die("Error: No items found.");
        }

        $pdo->beginTransaction();

        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, order_date) VALUES (?, ?, 'pending', NOW())");
        $stmt->execute([$user_id, $total_amount]);
        $order_id = $pdo->lastInsertId();

        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        $sql_stock = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
        $stmt_stock = $pdo->prepare($sql_stock);

        foreach ($cart_items as $item) {
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        // 从购物车删除已结账的商品
        $sql_delete = "DELETE FROM cart WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute($params);

        // ====== [新增功能] 从 Wishlist 中删除已购买的商品 ======
        // 构建删除 wishlist 的 SQL
        $sql_delete_wishlist = "DELETE FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete_wishlist = $pdo->prepare($sql_delete_wishlist);
        $stmt_delete_wishlist->execute($params); // 使用相同的参数 (user_id + product_ids)
        // ======================================================

        $pdo->commit();
        header("Location: ../views/member/orders.php?msg=success");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Order failed: " . $e->getMessage());
    }
}
?>