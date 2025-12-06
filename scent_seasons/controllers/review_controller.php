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
// (原有逻辑，只有 POST 且不是 delete 时执行)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $action != 'delete') {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = clean_input($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        die("Invalid rating.");
    }

    if (empty($comment)) {
        die("Comment cannot be empty.");
    }

    try {
        $sql = "INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $product_id, $rating, $comment]);

        header("Location: ../views/member/product_detail.php?id=$product_id&msg=review_added");
        exit();
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
}
