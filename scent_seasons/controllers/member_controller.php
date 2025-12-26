<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';


require_admin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';


if ($action == 'toggle_block') {
    $user_id = intval($_POST['user_id']);
    $block_status = intval($_POST['is_blocked']); 

    
    if ($user_id == $_SESSION['user_id']) {
        die("Cannot block yourself.");
    }


    $stmt = $pdo->prepare("UPDATE users SET is_blocked = ? WHERE user_id = ?");
    $stmt->execute([$block_status, $user_id]);

    $action_name = ($block_status == 1) ? "Block User" : "Unblock User";
    log_activity($pdo, $action_name, "User ID: $user_id");

    $msg = ($block_status == 1) ? "blocked" : "unblocked";
    header("Location: ../views/admin/members/index.php?msg=$msg");
    exit();
}

