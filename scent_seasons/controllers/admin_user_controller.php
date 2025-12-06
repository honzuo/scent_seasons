<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 只有超级管理员可以访问
require_superadmin();

$action = isset($_POST['action']) ? $_POST['action'] : '';

// --- 创建新的 Admin ---
if ($action == 'create_admin') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 简单验证
    if (empty($full_name) || empty($email) || empty($password)) {
        header("Location: ../views/admin/users/create_admin.php?error=empty");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: ../views/admin/users/create_admin.php?error=mismatch");
        exit();
    }

    // 检查邮箱是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        header("Location: ../views/admin/users/create_admin.php?error=exists");
        exit();
    }

    // 默认头像
    $photo_name = 'default.jpg';

    // 密码加密
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 插入数据库 (强制 role = 'admin')
    $sql = "INSERT INTO users (full_name, email, password, role, profile_photo) VALUES (?, ?, ?, 'admin', ?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$full_name, $email, $hashed_password, $photo_name])) {
        header("Location: ../views/admin/dashboard.php?msg=admin_created");
    } else {
        die("Database error");
    }
    exit();
}
?>