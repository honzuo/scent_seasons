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
        echo '<span class="error-msg" style="color:red; font-size:0.8em;">' . $errors[$field] . '</span>';
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
?>