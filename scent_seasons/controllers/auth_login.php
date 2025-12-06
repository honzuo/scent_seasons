<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors['login'] = "Please enter email and password.";
    } else {
        // 查询用户
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 验证密码 [cite: 78]
        if ($user && password_verify($password, $user['password'])) {
            // 登录成功：设置 Session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_photo'] = $user['profile_photo'];

            // 根据角色跳转
            if ($user['role'] == 'admin' || $user['role'] == 'superadmin') {
                // ... Session 设置之后 ...
                log_activity($pdo, "Login", "User logged in.");
                header("Location: ../views/admin/dashboard.php"); // 我们稍后创建这个
            } else {
                header("Location: ../views/member/home.php"); // 我们稍后创建这个
            }
            exit();
        } else {
            $errors['login'] = "Invalid email or password.";
        }
    }

    // 登录失败
    $_SESSION['errors'] = $errors;
    header("Location: ../views/public/login.php");
    exit();
}
