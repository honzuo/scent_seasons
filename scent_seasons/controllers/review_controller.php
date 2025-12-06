<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 基础检查：必须登录
if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// --- 1. 管理员删除评价 (DELETE) ---
if ($action == 'delete') {
    // 关键：只有管理员才能删除
    require_admin();

    $review_id = intval($_POST['review_id']);

    $stmt = $pdo->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);

    header("Location: ../views/admin/reviews/index.php?msg=deleted");
    exit();
}

// --- 2. 用户提交评价 (CREATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $action != 'delete') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = clean_input($_POST['comment']);

    // 1. 基础验证
    if ($rating < 1 || $rating > 5) {
        die("Invalid rating.");
    }
    if (empty($comment)) {
        die("Comment cannot be empty.");
    }

    // 2. [安全检查] 再次确认该用户是否购买过且订单已完成
    $sql_check = "SELECT COUNT(*) FROM orders o 
                  JOIN order_items oi ON o.order_id = oi.order_id 
                  WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'";
    $stmt = $pdo->prepare($sql_check);
    $stmt->execute([$user_id, $product_id]);

    if ($stmt->fetchColumn() == 0) {
        die("Error: Unauthorized review attempt.");
    }

    // 3. [安全检查] 防止重复评价
    $sql_exist = "SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?";
    $stmt = $pdo->prepare($sql_exist);
    $stmt->execute([$user_id, $product_id]);

    if ($stmt->fetchColumn() > 0) {
        die("Error: You have already reviewed this product.");
    }

    try {
        $sql = "INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $product_id, $rating, $comment]);

        // 成功后跳回产品详情页
        header("Location: ../views/member/product_detail.php?id=$product_id&msg=review_added");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
?>