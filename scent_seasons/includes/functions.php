<?php
// includes/functions.php

function clean_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function is_superadmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

function is_admin()
{
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
}

function display_error($errors, $field)
{
    if (isset($errors[$field])) {
        echo '<span class="error-msg">' . $errors[$field] . '</span>';
    }
}

function require_admin()
{
    if (!is_admin()) {
        header("Location: ../../views/public/login.php?error=unauthorized");
        exit();
    }
}

function require_superadmin()
{
    if (!is_superadmin()) {
        die("Access Denied: Only Superadmin can access this page.");
    }
}

function log_activity($pdo, $action, $details = "")
{
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'];

        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details, $ip]);
    }
}

// ========== 新增：密码强度验证函数 ==========

/**
 * 验证密码强度
 * @param string $password 要验证的密码
 * @return array ['valid' => bool, 'errors' => array]
 */
function validate_password_strength($password)
{
    $errors = [];

    // 最小长度检查
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    // 大写字母检查
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    }

    // 小写字母检查
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    }

    // 数字检查
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    }

    // 特殊字符检查
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*).";
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * 获取密码强度等级
 * @param string $password
 * @return string 'weak', 'medium', 'strong'
 */
function get_password_strength($password)
{
    $strength = 0;

    if (strlen($password) >= 8) $strength++;
    if (preg_match('/[A-Z]/', $password)) $strength++;
    if (preg_match('/[a-z]/', $password)) $strength++;
    if (preg_match('/[0-9]/', $password)) $strength++;
    if (preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) $strength++;

    if ($strength <= 2) {
        return 'weak';
    } elseif ($strength <= 3) {
        return 'medium';
    } else {
        return 'strong';
    }
}

// 提取 YouTube Video ID 的函数
function getYoutubeId($url) {
    $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';
    if (preg_match($pattern, $url, $match)) {
        return $match[1];
    }
    return null;
}