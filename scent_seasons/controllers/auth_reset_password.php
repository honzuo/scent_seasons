<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
        header("Location: ../views/public/forgot_password.php");
        exit();
    }

    $email = $_SESSION['reset_email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 验证密码
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } else {
        // 使用密码强度验证
        $validation = validate_password_strength($password);
        if (!$validation['valid']) {
            $errors['password'] = implode(' ', $validation['errors']);
        }
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // 更新密码
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        
        if ($stmt->execute([$hashed_password, $email])) {
            // 重置失败登录次数和锁定状态
            $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE email = ?");
            $stmt->execute([$email]);

            // 清除会话
            unset($_SESSION['reset_email']);
            unset($_SESSION['reset_verified']);

            $_SESSION['success_msg'] = "Password reset successful! Please login with your new password.";
            header("Location: ../views/public/login.php");
            exit();
        } else {
            $errors['password'] = "Failed to reset password. Please try again.";
        }
    }

    $_SESSION['errors'] = $errors;
    header("Location: ../views/public/reset_password.php");
    exit();
}