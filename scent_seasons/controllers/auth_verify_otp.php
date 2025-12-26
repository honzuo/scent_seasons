<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['reset_email'])) {
        header("Location: ../views/public/forgot_password.php");
        exit();
    }

    $email = $_SESSION['reset_email'];
    $otp = clean_input($_POST['otp']);

    // === 调试信息（测试完成后删除） ===
    error_log("=== OTP VERIFICATION DEBUG ===");
    error_log("Email from session: " . $email);
    error_log("OTP from form: " . $otp);
    error_log("OTP length: " . strlen($otp));
    
    if (empty($otp)) {
        $errors['otp'] = "Please enter the verification code.";
    } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
        $errors['otp'] = "Invalid code format. Must be 6 digits.";
        error_log("OTP format validation failed");
    } else {
        // 查询数据库中的OTP
        $stmt = $pdo->prepare("
            SELECT * FROM password_resets 
            WHERE email = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$email]);
        $reset = $stmt->fetch();

        // === 调试信息 ===
        if ($reset) {
            error_log("Database OTP: " . $reset['otp_code']);
            error_log("Database expires_at: " . $reset['expires_at']);
            error_log("Current time: " . date('Y-m-d H:i:s'));
            error_log("OTP match: " . ($reset['otp_code'] == $otp ? 'YES' : 'NO'));
            error_log("Time valid: " . (strtotime($reset['expires_at']) > time() ? 'YES' : 'NO'));
        } else {
            error_log("No OTP record found in database");
        }

        // 先检查OTP是否存在
        if (!$reset) {
            $errors['otp'] = "No verification code found. Please request a new one.";
        }
        // 检查OTP是否匹配（注意：数据库存储的可能是字符串）
        elseif ($reset['otp_code'] != $otp) {
            $errors['otp'] = "Invalid verification code. Please check and try again.";
            error_log("OTP mismatch - DB: '" . $reset['otp_code'] . "' vs Input: '" . $otp . "'");
        }
        // 检查是否过期
        elseif (strtotime($reset['expires_at']) <= time()) {
            $errors['otp'] = "Verification code has expired. Please request a new one.";
            error_log("OTP expired");
        }
        // 验证成功
        else {
            error_log("OTP verification successful!");
            
            // 标记为已验证
            $_SESSION['reset_verified'] = true;
            
            // 删除已使用的OTP
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$email]);

            header("Location: ../views/public/reset_password.php");
            exit();
        }
    }

    $_SESSION['errors'] = $errors;
    header("Location: ../views/public/verify_otp.php");
    exit();
}