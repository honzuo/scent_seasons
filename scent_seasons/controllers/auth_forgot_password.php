<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';
require '../includes/mailer.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = clean_input($_POST['email']);

    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } else {
        // 检查邮箱是否存在
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // 生成6位数字OTP
            $otp = sprintf("%06d", mt_rand(0, 999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // 保存OTP到数据库
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (email, otp_code, expires_at) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                otp_code = VALUES(otp_code), 
                expires_at = VALUES(expires_at), 
                created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$email, $otp, $expires_at]);

            // 发送邮件
            if (send_otp_email($email, $user['full_name'], $otp)) {
                $_SESSION['reset_email'] = $email;
                $_SESSION['success_msg'] = "Verification code sent! Please check your email.";
                header("Location: ../views/public/verify_otp.php");
                exit();
            } else {
                $errors['email'] = "Failed to send email. Please try again.";
            }
        } else {
            // 为了安全，不要告诉用户邮箱不存在
            $_SESSION['reset_email'] = $email;
            $_SESSION['success_msg'] = "If this email exists, a verification code has been sent.";
            header("Location: ../views/public/verify_otp.php");
            exit();
        }
    }

    $_SESSION['errors'] = $errors;
    header("Location: ../views/public/forgot_password.php");
    exit();
}