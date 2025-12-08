<?php
// controllers/chat_controller.php
session_start();
require '../config/database.php';
require '../includes/functions.php';

header('Content-Type: application/json');

// Ensure messages table exists (simple helper for this feature)
function ensure_messages_table($pdo)
{
    $sql = "CREATE TABLE IF NOT EXISTS messages (
                message_id INT AUTO_INCREMENT PRIMARY KEY,
                sender_id INT NOT NULL,
                receiver_id INT NULL,
                is_admin TINYINT(1) DEFAULT 0,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
}

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

    ensure_messages_table($pdo);
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, is_admin, message) VALUES (?, NULL, 0, ?)");
    $stmt->execute([$_SESSION['user_id'], $message]);
    json_response(['status' => 'success']);
}

// Member: fetch conversation with admin
if ($action === 'fetch_member') {
    if (!is_logged_in()) json_response(['status' => 'error', 'message' => 'Unauthorized']);
    ensure_messages_table($pdo);
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT message_id, sender_id, receiver_id, is_admin, message, created_at
                           FROM messages
                           WHERE sender_id = ? OR receiver_id = ?
                           ORDER BY created_at ASC");
    $stmt->execute([$user_id, $user_id]);
    $messages = $stmt->fetchAll();
    json_response(['status' => 'success', 'messages' => $messages]);
}

// Admin: list members with conversations
if ($action === 'admin_list_members') {
    require_admin();
    ensure_messages_table($pdo);
    $sql = "SELECT u.user_id, u.full_name, u.email, MAX(m.created_at) as last_ts
            FROM messages m
            JOIN users u ON u.user_id = CASE WHEN m.is_admin = 1 THEN m.receiver_id ELSE m.sender_id END
            WHERE u.role = 'member'
            GROUP BY u.user_id, u.full_name, u.email
            ORDER BY last_ts DESC";
    $members = $pdo->query($sql)->fetchAll();
    json_response(['status' => 'success', 'members' => $members]);
}

// Admin: fetch conversation with a member
if ($action === 'admin_fetch') {
    require_admin();
    $member_id = intval($_GET['member_id'] ?? 0);
    if ($member_id <= 0) json_response(['status' => 'error', 'message' => 'Invalid member']);
    ensure_messages_table($pdo);
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

    ensure_messages_table($pdo);
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, is_admin, message) VALUES (?, ?, 1, ?)");
    $stmt->execute([$_SESSION['user_id'], $member_id, $message]);
    json_response(['status' => 'success']);
}

// Default
json_response(['status' => 'error', 'message' => 'Invalid action']);

