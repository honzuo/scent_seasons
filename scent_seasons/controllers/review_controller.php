<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// --- 1. 删除 ---
if ($action == 'delete') {
    require_admin();
    $review_id = intval($_POST['review_id']);
    $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    header("Location: ../views/admin/reviews/index.php?msg=deleted");
    exit();
}

if ($action == 'reply') {
    require_admin(); // 必须是管理员

    $review_id = intval($_POST['review_id']);
    $reply_text = clean_input($_POST['admin_reply']);

    // 更新回复内容和时间
    $stmt = $pdo->prepare("UPDATE reviews SET admin_reply = ?, reply_at = NOW() WHERE review_id = ?");
    $stmt->execute([$reply_text, $review_id]);

    header("Location: ../views/admin/reviews/index.php?msg=replied");
    exit();
}

// --- 2. 提交评价 ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $action != 'delete') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $order_id = intval($_POST['order_id']); // 新增
    $rating = intval($_POST['rating']);
    $comment = clean_input($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        die("Invalid rating.");
    }
    if (empty($comment)) {
        die("Comment cannot be empty.");
    }

    // 验证：是否在该订单购买过且未评价
    $sql_check = "SELECT COUNT(*) FROM orders o 
                  JOIN order_items oi ON o.order_id = oi.order_id 
                  WHERE o.user_id = ? AND o.order_id = ? AND oi.product_id = ? AND o.status = 'completed'";
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute([$user_id, $order_id, $product_id]);

    if ($stmt->fetchColumn() == 0) {
        die("Error: Unauthorized review.");
    }

    // 防止重复评价 (按 order_id)
    $sql_exist = "SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?";
    $stmt = $pdo->prepare($sql_exist);
    $stmt->execute([$user_id, $product_id, $order_id]);

    if ($stmt->fetchColumn() > 0) {
        die("Error: Already reviewed this order item.");
    }

    try {
        // 插入时带上 order_id
        $sql = "INSERT INTO reviews (user_id, product_id, order_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $product_id, $order_id, $rating, $comment]);

        header("Location: ../views/member/product_detail.php?id=$product_id&msg=review_added");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
