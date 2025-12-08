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
    $paypal_tx_id = null;
    $address = ''; // 初始化地址变量

    // A. 接收 JSON 数据
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if ($contentType === "application/json") {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $selected_ids = isset($decoded['selected_items']) ? $decoded['selected_items'] : [];
            $paypal_tx_id = isset($decoded['transaction_id']) ? $decoded['transaction_id'] : null;
            // [新增] 接收地址
            $address = isset($decoded['address']) ? clean_input($decoded['address']) : '';
        }
    }

    // 验证部分
    if (empty($selected_ids)) {
        echo json_encode(['success' => false, 'message' => 'No items selected']);
        exit();
    }
    // [新增] 验证地址
    if (empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Shipping address is required']);
        exit();
    }

    try {
        // ... (查询商品部分保持不变) ...
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
            echo json_encode(['success' => false, 'message' => 'Error: No items found.']);
            exit();
        }

        $pdo->beginTransaction();

        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }

        $status = 'pending'; // 保持 Pending 状态

        // [修改] 插入订单时带上 address
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, address, status, transaction_id, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $total_amount, $address, $status, $paypal_tx_id]);
        $order_id = $pdo->lastInsertId();

        // ... (插入 items 和删除购物车的代码保持不变) ...
        // 插入订单详情
        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        $sql_stock = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
        $stmt_stock = $pdo->prepare($sql_stock);

        foreach ($cart_items as $item) {
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        $sql_delete = "DELETE FROM cart WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute($params);

        // 删除 Wishlist
        $sql_delete_wishlist = "DELETE FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete_wishlist = $pdo->prepare($sql_delete_wishlist);
        $stmt_delete_wishlist->execute($params);

        $pdo->commit();

        echo json_encode(['success' => true, 'order_id' => $order_id]);
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}
