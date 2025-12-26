<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';


if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';


if ($action == 'update_status') {
    require_admin();

    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = trim($_POST['status'] ?? '');
    

    if ($order_id <= 0 || empty($new_status)) {
        $_SESSION['error'] = "Invalid order ID or status.";
        header("Location: ../views/admin/orders/index.php");
        exit();
    }
    

    $valid_statuses = ['pending', 'completed', 'cancelled', 'returned', 'refunded'];
    if (!in_array($new_status, $valid_statuses)) {
        $_SESSION['error'] = "Invalid status value.";
        header("Location: ../views/admin/orders/index.php");
        exit();
    }

    try {
 
        $stmt = $pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            $_SESSION['error'] = "Order not found.";
            header("Location: ../views/admin/orders/index.php");
            exit();
        }
        
        $old_status = $order['status'];
        
       
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        
        log_activity($pdo, "Update Order Status", "Order #$order_id: $old_status → $new_status");
        
       
        $redirect_params = ['msg' => 'updated'];
        
       
        if (!empty($_POST['filter_user_id']) && intval($_POST['filter_user_id']) > 0) {
            $redirect_params['user_id'] = intval($_POST['filter_user_id']);
        }
        

        if (!empty($_POST['filter_status'])) {
            $redirect_params['status'] = clean_input($_POST['filter_status']);
        }
        
       
        if (!empty($_POST['search'])) {
            $redirect_params['search'] = clean_input($_POST['search']);
        }
        
       
        if (!empty($_POST['sort'])) {
            $redirect_params['sort'] = clean_input($_POST['sort']);
        }
        
  
        if (!empty($_POST['page']) && intval($_POST['page']) > 1) {
            $redirect_params['page'] = intval($_POST['page']);
        }
        
      
        $redirect_url = "../views/admin/orders/index.php?" . http_build_query($redirect_params);
        
        header("Location: $redirect_url");
        exit();
        
    } catch (Exception $e) {
        error_log("❌ Order status update failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update order status.";
        header("Location: ../views/admin/orders/index.php");
        exit();
    }
}


if ($action == 'checkout') {
    $user_id = $_SESSION['user_id'];
    $selected_ids = [];
    $paypal_tx_id = null;
    $address = ''; 

  
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

       
        $total_amount = 0;
        foreach ($cart_items as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }
      
        $status = 'pending'; 

      
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, address, status, transaction_id, order_date) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $total_amount, $address, $status, $paypal_tx_id]);
        $order_id = $pdo->lastInsertId();

        
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

        $sql_delete_wishlist = "DELETE FROM wishlist WHERE user_id = ? AND product_id IN ($placeholders)";
        $stmt_delete_wishlist = $pdo->prepare($sql_delete_wishlist);
        $stmt_delete_wishlist->execute($params);

        $pdo->commit();

       
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


if ($action == 'cancel') {
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
  
    $reason_select = isset($_POST['cancel_reason']) ? clean_input($_POST['cancel_reason']) : '';
    $custom_reason = isset($_POST['custom_reason']) ? clean_input($_POST['custom_reason']) : '';
    $final_reason = ($reason_select === 'Other' && !empty($custom_reason)) ? $custom_reason : $reason_select;


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

       
        $sql_items = "SELECT oi.quantity, oi.product_id, p.name as product_name 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.product_id 
                      WHERE oi.order_id = ?";
        $stmt_items = $pdo->prepare($sql_items);
        $stmt_items->execute([$order_id]);
        $items = $stmt_items->fetchAll();

        
        $sql_restore = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
        $stmt_restore = $pdo->prepare($sql_restore);

        foreach ($items as $item) {
            $stmt_restore->execute([$item['quantity'], $item['product_id']]);
        }

       
        $stmt_update = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
        $stmt_update->execute([$order_id]);

        $pdo->commit();

        header("Location: ../views/member/order_detail.php?id=$order_id&msg=cancelled");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Cancel Error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to cancel order. Please try again.";
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }
}


if ($action == 'return') {
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    

    $return_reason_select = isset($_POST['return_reason']) ? clean_input($_POST['return_reason']) : '';
    $custom_return_reason = isset($_POST['custom_return_reason']) ? clean_input($_POST['custom_return_reason']) : '';
    $return_notes = isset($_POST['return_notes']) ? clean_input($_POST['return_notes']) : '';
    
    $final_return_reason = ($return_reason_select === 'Other' && !empty($custom_return_reason)) 
                          ? $custom_return_reason 
                          : $return_reason_select;

  
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error'] = "Order not found.";
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }

    if ($order['status'] != 'completed') {
        $_SESSION['error'] = "Only completed orders can be returned.";
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }

    try {
        $pdo->beginTransaction();

      
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

      
        $sql_restore = "UPDATE products SET stock = stock + ? WHERE product_id = ?";
        $stmt_restore = $pdo->prepare($sql_restore);

        foreach ($items as $item) {
            $stmt_restore->execute([$item['quantity'], $item['product_id']]);
        }

      
        $stmt_update = $pdo->prepare("UPDATE orders SET status = 'returned' WHERE order_id = ?");
        $stmt_update->execute([$order_id]);

        $pdo->commit();

        header("Location: ../views/member/order_detail.php?id=$order_id&msg=returned");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ Return Error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to process return: " . $e->getMessage();
        header("Location: ../views/member/order_detail.php?id=$order_id");
        exit();
    }
}


if (isset($_SERVER["CONTENT_TYPE"]) && trim($_SERVER["CONTENT_TYPE"]) === "application/json") {
    echo json_encode(['success' => false, 'message' => 'Unknown action']);
} else {
    header("Location: ../views/member/orders.php");
}
exit();
?>