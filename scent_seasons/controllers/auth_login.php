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
        // 1. 查找用户
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // --- [新增] 检查 1: 是否被管理员永久封禁 ---
            if ($user['is_blocked'] == 1) {
                $errors['login'] = "Your account has been blocked by admin.";
                $_SESSION['errors'] = $errors;
                header("Location: ../views/public/login.php");
                exit();
            }

            // --- [新增] 检查 2: 是否处于临时锁定状态 ---
            if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
                // 计算还剩多少分钟
                $remaining = ceil((strtotime($user['lock_until']) - time()) / 60);
                $errors['login'] = "Account locked due to too many failed attempts. Please try again in $remaining minutes.";

                $_SESSION['errors'] = $errors;
                header("Location: ../views/public/login.php");
                exit();
            }

            // --- 3. 验证密码 ---
            if (password_verify($password, $user['password'])) {
                // A. 密码正确：重置失败计数器
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);

                // 登录成功逻辑 (设置 Session)
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_photo'] = $user['profile_photo'];

                // 记录日志
                if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'superadmin')) {
                    log_activity($pdo, "Login", "User logged in.");
                    header("Location: ../views/admin/dashboard.php");
                } else {
                    header("Location: ../views/member/home.php");
                }
                exit();
            } else {
                // B. 密码错误：增加失败次数
                $attempts = $user['failed_attempts'] + 1;

                if ($attempts >= 5) {
                    // 超过 5 次，锁定 10 分钟
                    // 这里的 '+10 minutes' 就是锁定时间，你可以改成 '+1 hour' 等
                    $lock_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                    // 更新数据库：设置锁定时间，并重置次数(或者保留次数也可以，这里我们重置方便下一次计数)
                    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = ? WHERE user_id = ?");
                    $stmt->execute([$lock_time, $user['user_id']]);

                    $errors['login'] = "Too many failed attempts. Account locked for 10 minutes.";
                } else {
                    // 还没到 5 次，只更新次数
                    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
                    $stmt->execute([$attempts, $user['user_id']]);

                    $remaining_tries = 5 - $attempts;
                    $errors['login'] = "Invalid password. You have $remaining_tries attempts remaining.";
                }
            }
        } else {
            // 用户不存在 (为了安全，不要提示“用户不存在”，统称“无效的邮箱或密码”)
            $errors['login'] = "Invalid email or password.";
        }
    }

    // 登录失败通用跳转
    $_SESSION['errors'] = $errors;
    header("Location: ../views/public/login.php");
    exit();
}
