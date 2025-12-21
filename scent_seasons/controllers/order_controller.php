<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 验证登录
if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// ============================================
// 1. [Admin] 更新订单状态
// ============================================
if ($action == 'update_status') {
    require_admin();

    $order_id = intval($_POST['order_id']);
    $status = clean_input($_POST['status']);

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$status, $order_id]);

    log_activity($pdo, "Update Order Status", "Order ID: $order_id to $status");

    $redirect = "../views/admin/orders/index.php?msg=updated";
    if (isset($_POST['filter_user_id']) && $_POST['filter_user_id'] > 0) {
        $redirect .= "&user_id=" . $_POST['filter_user_id'];
    }

    header("Location: $redirect");
    exit();
}

// ============================================
// 2. [Member] 结账 (Checkout & PayPal)
// ============================================
if ($action == 'checkout') {
    $user_id = $_SESSION['user_id'];
    $selected_ids = [];
    $paypal_tx_id = null;
    $address = ''; 

    // 接收 JSON 数据
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if ($contentType === "application/json") {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $selected_ids = isset($decoded['selected_items']) ? $decoded['selected_items'] : [];
            $paypal_tx_id = isset($decoded['transaction_id']) ? $decoded['transaction_id'] : null;
            $address = isset($decoded['address']) ? clean_input($decoded['address']) : '';
        }
    }

    // 验证
    if (empty($selected_ids)) {
        echo json_encode(['success' => false, 'message' => 'No items selected']);
        exit();
    }
    if (empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Shipping address is required']);
        exit();
    }

    try {
        $placeholders = implode(',', array_fill(0, count($selected_ids), '?'));
        $sql = "SELECT c.quantity, p.product_id, p.name, p.price, p.stock 
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

        // 计算总额
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }
      
        $status = 'pending'; 

        // 创建订单
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, address, status, transaction_id, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $total_amount, $address, $status, $paypal_tx_id]);
        $order_id = $pdo->lastInsertId();

        // 插入订单项目和更新库存
        $sql_item = "INSERT INTO order_items (order_id, product_id, quantity, price_each) VALUES (?, ?, ?, ?)";
        $stmt_item = $pdo->prepare($sql_item);
        $sql_stock = "UPDATE products SET stock = stock - ? WHERE product_id = ?";
        $stmt_stock = $pdo->prepare($sql_stock);

        foreach ($cart_items as $item) {
            $stmt_item->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
            $stmt_stock->execute([$item['quantity'], $item['product_id']]);
        }

        // 清空购物车和心愿单
        $sql_delete = "DELETE FROM cart WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute($params);

        $sql_delete_wishlist = "DELETE FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete_wishlist = $pdo->prepare($sql_delete_wishlist);
        $stmt_delete_wishlist->execute($params);

        $pdo->commit();

        // 发送订单收据邮件
        try {
            if (file_exists(__DIR__ . '/../includes/mailer.php')) {
                require_once __DIR__ . '/../includes/mailer.php';
                
                if (function_exists('send_order_receipt')) {
                    $stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
                    $stmt_user->execute([$user_id]);
                    $user = $stmt_user->fetch();

                    if ($user && !empty($user['email'])) {
                        $order_data = [
                            'order_id' => $order_id,
                            'order_date' => date('Y-m-d H:i:s'),
                            'total_amount' => $total_amount,
                            'status' => $status,
                            'transaction_id' => $paypal_tx_id,
                            'items' => []
                        ];
                        
                        foreach ($cart_items as $item) {
                            $order_data['items'][] = [
                                'product_name' => $item['name'],
                                'quantity' => $item['quantity'],
                                'price_each' => $item['price']
                            ];
                        }
                        
                        send_order_receipt($user['email'], $user['full_name'], $order_data);
                    }
                }
            }
        } catch (Exception $email_error) {
            error_log("Email error in checkout: " . $email_error->getMessage());
        }

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
        error_log("❌ Order creation failed: " . $e->getMessage());
        
        if ($contentType === "application/json") {
            echo json_encode(['success' => false, 'message' => 'System error processing order.']);
            exit();
        } else {
            die("Order failed: System error processing order.");
        }
    }
}

// ============================================
// 3. [Member] 取消订单
// ============================================
if ($action == 'cancel') {
    error_log("========================================");
    error_log("🔴 CANCEL ACTION TRIGGERED");
    error_log("POST data: " . print_r($_POST, true));
    error_log("========================================");
    
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    error_log("User ID: $user_id, Order ID: $order_id");
    
    // 获取取消原因
    $reason_select = isset($_POST['cancel_reason']) ? clean_input($_POST['cancel_reason']) : '';
    $custom_reason = isset($_POST['custom_reason']) ? clean_input($_POST['custom_reason']) : '';
    $final_reason = ($reason_select === 'Other' && !empty($custom_reason)) ? $custom_reason : $reason_select;

    // 验证订单
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error'] = "Order not found.";
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }

    if ($order['status'] != 'pending') {
        $_SESSION['error'] = "Only pending orders can be cancelled.";
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 获取订单商品详情
        $sql_items = "SELECT oi.quantity, oi.product_id, p.name as product_name 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.product_id 
                      WHERE oi.order_id = ?";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([$order_id]);
        $items = $stmt_items->fetchAll();

        // 恢复库存
        $sql_restore = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
        $stmt_restore = $pdo->prepare($sql_restore);

        foreach ($items as $item) {
            $result = $stmt_restore->execute([$item['quantity'], $item['product_id']]);
            error_log("✓ Restored stock for product ID {$item['product_id']}: +{$item['quantity']}");
        }

        // 更新订单状态
        $stmt_update = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
        $stmt_update->execute([$order_id]);

        $pdo->commit();

        // 记录日志
        error_log("✅ Order #$order_id cancelled successfully by user #$user_id. Reason: $final_reason");

        header("Location: ../views/member/order_detail.php?id=$order_id&msg=cancelled");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Cancel Error: " . $e->getMessage());
        error_log("Error details: " . print_r($e, true));
        $_SESSION['error'] = "Failed to cancel order. Please try again.";
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }
}

// ============================================
// 4. [Member] 退货订单 (Return Order)
// ============================================
if ($action == 'return') {
    error_log("========================================");
    error_log("🔄 RETURN ACTION TRIGGERED");
    error_log("POST data: " . print_r($_POST, true));
    error_log("========================================");
    
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    error_log("User ID: $user_id, Order ID: $order_id");
    
    // 获取退货原因
    $return_reason_select = isset($_POST['return_reason']) ? clean_input($_POST['return_reason']) : '';
    $custom_return_reason = isset($_POST['custom_return_reason']) ? clean_input($_POST['custom_return_reason']) : '';
    $return_notes = isset($_POST['return_notes']) ? clean_input($_POST['return_notes']) : '';
    
    $final_return_reason = ($return_reason_select === 'Other' && !empty($custom_return_reason)) 
                          ? $custom_return_reason 
                          : $return_reason_select;

    // 验证订单
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error'] = "Order not found.";
        error_log("❌ Order not found: Order ID $order_id, User ID $user_id");
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }

    // 只有已完成的订单才能退货
    if ($order['status'] != 'completed') {
        $_SESSION['error'] = "Only completed orders can be returned. Current status: " . $order['status'];
        error_log("❌ Invalid status for return: Order #$order_id status is '{$order['status']}'");
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 获取订单商品详情
        $sql_items = "SELECT oi.quantity, oi.product_id, p.name as product_name 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.product_id 
                      WHERE oi.order_id = ?";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([$order_id]);
        $items = $stmt_items->fetchAll();

        if (empty($items)) {
            throw new Exception("No items found for order #$order_id");
        }

        error_log("📦 Processing return for " . count($items) . " items");

        // 恢复库存（退货时将商品退回库存）
        $sql_restore = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
        $stmt_restore = $pdo->prepare($sql_restore);

        foreach ($items as $item) {
            $result = $stmt_restore->execute([$item['quantity'], $item['product_id']]);
            error_log("✓ Restored stock for product ID {$item['product_id']} ({$item['product_name']}): +{$item['quantity']}");
        }

        // 更新订单状态为 'returned'
        $stmt_update = $pdo->prepare("UPDATE orders SET status = 'returned' WHERE order_id = ?");
        $result = $stmt_update->execute([$order_id]);

        if (!$result) {
            throw new Exception("Failed to update order status");
        }

        $pdo->commit();

        // 记录日志
        $log_message = "Order #$order_id returned by user #$user_id. Reason: $final_return_reason";
        if (!empty($return_notes)) {
            $log_message .= " | Notes: $return_notes";
        }
        error_log("✅ " . $log_message);

        // 可以在这里发送退货确认邮件给用户和管理员
        // send_return_confirmation_email($user_id, $order_id, $final_return_reason);

        header("Location: ../views/member/order_detail.php?id=$order_id&msg=returned");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Return Error: " . $e->getMessage());
        error_log("Error details: " . print_r($e, true));
        $_SESSION['error'] = "Failed to process return: " . $e->getMessage();
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }
}

// ============================================
// 5. 默认重定向（防止直接访问)
// ============================================
if (isset($_SERVER["CONTENT_TYPE"]) && trim($_SERVER["CONTENT_TYPE"]) === "application/json") {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} else {
    header("Location: ../views/member/orders.php");
}
exit();
?>