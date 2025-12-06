<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 强制管理员权限
require_admin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// --- 删除会员 ---
if ($action == 'delete') {
    $user_id = intval($_POST['user_id']);

    // 防止删除自己 (虽然逻辑上只会列出 role='member'，但加个保险)
    if ($user_id == $_SESSION['user_id']) {
        die("Cannot delete yourself.");
    }

    // 1. 获取用户头像 (为了删文件)
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // 2. 如果头像不是默认的，就从文件夹删掉
    if ($user && $user['profile_photo'] != 'default.jpg') {
        $file_path = "../images/uploads/" . $user['profile_photo'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    // 3. 从数据库删除 (因为我们在建表时设置了 ON DELETE CASCADE，用户的订单和购物车也会自动删除)
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);

    header("Location: ../views/admin/members/index.php?msg=deleted");
    exit();
}
?>