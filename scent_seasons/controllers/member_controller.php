<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 强制管理员权限
require_admin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// --- 封禁/解封用户 (Block / Unblock) ---
if ($action == 'toggle_block') {
    $user_id = intval($_POST['user_id']);
    $block_status = intval($_POST['is_blocked']); // 1 = 要封禁, 0 = 要解封

    // 防止封禁自己 (虽然只有 admin 能进这里，但逻辑上要严谨)
    if ($user_id == $_SESSION['user_id']) {
        die("Cannot block yourself.");
    }

    // 更新状态
    $stmt = $pdo->prepare("UPDATE users SET is_blocked = ? WHERE user_id = ?");
    $stmt->execute([$block_status, $user_id]);

    $action_name = ($block_status == 1) ? "Block User" : "Unblock User";
    log_activity($pdo, $action_name, "User ID: $user_id");

    $msg = ($block_status == 1) ? "blocked" : "unblocked";
    header("Location: ../views/admin/members/index.php?msg=$msg");
    exit();
}

// (原来的 delete 逻辑你可以选择保留作为彻底删除，或者直接删掉代码)
// 如果你彻底不想让管理员删除用户，就把下面的 delete 代码删掉即可。
