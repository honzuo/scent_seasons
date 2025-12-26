<?php
// controllers/chat_controller.php
session_start();
require '../config/database.php';
require '../includes/functions.php';

header('Content-Type: application/json');

function json_response($data)
{
    echo json_encode($data);
    exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// Member: send message
if ($action === 'send_member') {
    if (!is_logged_in()) json_response(['status' => 'error', 'message' => 'Unauthorized']);
    $message = trim($_POST['message'] ?? '');
    if ($message === '') json_response(['status' => 'error', 'message' => 'Message cannot be empty']);

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, is_admin, message, admin_read) VALUES (?, NULL, 0, ?, 0)");
    $stmt->execute([$_SESSION['user_id'], $message]);
    json_response(['status' => 'success']);
}

// Member: fetch conversation with admin
if ($action === 'fetch_member') {
    if (!is_logged_in()) json_response(['status' => 'error', 'message' => 'Unauthorized']);
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT message_id, sender_id, receiver_id, is_admin, message, created_at
                           FROM messages
                           WHERE sender_id = ? OR receiver_id = ?
                           ORDER BY created_at ASC");
    $stmt->execute([$user_id, $user_id]);
    $messages = $stmt->fetchAll();
    json_response(['status' => 'success', 'messages' => $messages]);
}

// Admin: list members with conversations + unread count
if ($action === 'admin_list_members') {
    require_admin();
    
    // 获取所有发过消息的用户
    $sql = "SELECT DISTINCT m.sender_id as user_id, u.full_name, u.email 
            FROM messages m 
            JOIN users u ON m.sender_id = u.user_id 
            WHERE m.is_admin = 0 
            ORDER BY (SELECT MAX(created_at) FROM messages WHERE sender_id = m.sender_id) DESC";
    $stmt = $pdo->query($sql);
    $members = $stmt->fetchAll();
    
    // 为每个用户统计未读消息数（使用 admin_read 字段）
    foreach ($members as &$member) {
        $sql_unread = "SELECT COUNT(*) as unread_count 
                       FROM messages 
                       WHERE sender_id = ? 
                       AND is_admin = 0 
                       AND admin_read = 0";
        $stmt_unread = $pdo->prepare($sql_unread);
        $stmt_unread->execute([$member['user_id']]);
        $unread = $stmt_unread->fetch();
        $member['unread_count'] = (int)$unread['unread_count'];
    }
    
    json_response(['status' => 'success', 'members' => $members]);
}

// Admin: fetch conversation with a member
if ($action === 'admin_fetch') {
    require_admin();
    $member_id = intval($_GET['member_id'] ?? 0);
    if ($member_id <= 0) json_response(['status' => 'error', 'message' => 'Invalid member']);
    $stmt = $pdo->prepare("SELECT message_id, sender_id, receiver_id, is_admin, message, created_at
                           FROM messages
                           WHERE (sender_id = ? AND is_admin = 0)
                              OR (receiver_id = ? AND is_admin = 1)
                           ORDER BY created_at ASC");
    $stmt->execute([$member_id, $member_id]);
    $messages = $stmt->fetchAll();
    json_response(['status' => 'success', 'messages' => $messages]);
}

// Admin: send message to member
if ($action === 'admin_send') {
    require_admin();
    $member_id = intval($_POST['member_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    if ($member_id <= 0) json_response(['status' => 'error', 'message' => 'Invalid member']);
    if ($message === '') json_response(['status' => 'error', 'message' => 'Message cannot be empty']);

    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, is_admin, message) VALUES (?, ?, 1, ?)");
    $stmt->execute([$_SESSION['user_id'], $member_id, $message]);
    json_response(['status' => 'success']);
}

// Admin: mark messages as read
if ($action === 'mark_read') {
    require_admin();
    $member_id = intval($_POST['member_id'] ?? 0);
    
    if ($member_id <= 0) {
        json_response(['status' => 'error', 'message' => 'Invalid member']);
    }
    
    // 将该用户发送给管理员的所有未读消息标记为已读
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET admin_read = 1 
        WHERE sender_id = ? 
        AND is_admin = 0 
        AND admin_read = 0
    ");
    
    $stmt->execute([$member_id]);
    $marked_count = $stmt->rowCount();
    
    json_response([
        'status' => 'success',
        'marked_count' => $marked_count,
        'message' => "Marked {$marked_count} message(s) as read"
    ]);
}

// Default
json_response(['status' => 'error', 'message' => 'Invalid action']);