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

// --- 2. [Member] 结账 (Checkout & PayPal) ---
if ($action == 'checkout') {
    $user_id = $_SESSION['user_id'];
    $selected_ids = [];
    $paypal_tx_id = null; // 用于存储 PayPal 交易号

    // A. 检查是否是 JSON 请求 (来自 PayPal JS SDK)
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if ($contentType === "application/json") {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $selected_ids = isset($decoded['selected_items']) ? $decoded['selected_items'] : [];
            $paypal_tx_id = isset($decoded['transaction_id']) ? $decoded['transaction_id'] : null;
        }
    }
    // B. 检查是否是普通表单 POST (兼容旧逻辑，如果有的话)
    else {
        $selected_ids = isset($_POST['selected_items']) ? $_POST['selected_items'] : [];
    }

    // 如果没选商品，报错
    if (empty($selected_ids) || !is_array($selected_ids)) {
        if ($contentType === "application/json") {
            echo json_encode(['success' => false, 'message' => 'No items selected']);
            exit();
        } else {
            header("Location: ../views/member/cart.php");
            exit();
        }
    }

    try {
        // 构建 SQL 查询选中的商品
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
            if ($contentType === "application/json") {
                echo json_encode(['success' => false, 'message' => 'Error: No items found.']);
                exit();
            } else {
                die("Error: No items found.");
            }
        }

        // 开启事务
        $pdo->beginTransaction();

        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }

        // 插入订单 (如果是 PayPal 支付，直接标记为 completed)
        // 插入订单 (即使是 PayPal 支付，初始状态也设为 pending，等待管理员处理)
        $status = 'pending';

        // 注意：如果你没加 transaction_id 字段，把下面这行里的 transaction_id 删掉
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, status, transaction_id, order_date) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $total_amount, $status, $paypal_tx_id]);
        $order_id = $pdo->lastInsertId();

        // 插入订单详情 & 扣减库存
        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        $sql_stock = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
        $stmt_stock = $pdo->prepare($sql_stock);

        foreach ($cart_items as $item) {
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        // 删除购物车
        $sql_delete = "DELETE FROM cart WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute($params);

        // 删除 Wishlist (如果存在)
        $sql_delete_wishlist = "DELETE FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete_wishlist = $pdo->prepare($sql_delete_wishlist);
        $stmt_delete_wishlist->execute($params);

        $pdo->commit();

        // 返回结果
        if ($contentType === "application/json") {
            echo json_encode(['success' => true, 'order_id' => $order_id]);
            exit();
        } else {
            header("Location: ../views/member/orders.php?msg=success");
            exit();
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        if ($contentType === "application/json") {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        } else {
            die("Order failed: " . $e->getMessage());
        }
    }
}
