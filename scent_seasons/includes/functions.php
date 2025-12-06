<?php
// includes/functions.php

// 1. 数据清洗函数 (防止 XSS 攻击)
function clean_input($data)
{
    $data = trim($data);            // 去除前后空格
    $data = stripslashes($data);    // 去除反斜杠
    $data = htmlspecialchars($data); // 转义 HTML 特殊字符
    return $data;
}

// 2. 检查用户是否已登录
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

// 3. 检查是否是管理员
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// 4. 显示错误信息的 Helper (用于在表单旁边显示红字)
function display_error($errors, $field)
{
    if (isset($errors[$field])) {
        echo '<span class="error-msg" style="color:red; font-size:0.8em;">' . $errors[$field] . '</span>';
    }
}

// 5. 强制检查管理员权限 (如果不通过直接踢回登录页)
function require_admin()
{
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../../public/login.php?error=unauthorized");
        exit();
    }
}
?>
