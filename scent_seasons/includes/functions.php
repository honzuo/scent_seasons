<?php
// includes/functions.php

function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    // $data = htmlspecialchars($data); // 防止存入数据库时转义单引号
    return $data;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// 检查是否是 Superadmin (最高权限)
function is_superadmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

// 检查是否是管理层 (Admin 或 Superadmin 都可以)
function is_admin()
{
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
}

function display_error($errors, $field)
{
    if (isset($errors[$field])) {
        // [修改] 移除了内联 style，样式现在由 common.css 中的 .error-msg 类控制
        echo '<span class="error-msg">' . $errors[$field] . '</span>';
    }
}

// 强制检查管理层权限
function require_admin()
{
    if (!is_admin()) {
        header("Location: ../../views/public/login.php?error=unauthorized");
        exit();
    }
}

// [新增] 强制检查超级管理员权限
function require_superadmin()
{
    if (!is_superadmin()) {
        die("Access Denied: Only Superadmin can access this page.");
    }
}

// [新增] 记录操作日志
function log_activity($pdo, $action, $details = "")
{
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR']; // 获取用户 IP

        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details, $ip]);
    }
}
?>