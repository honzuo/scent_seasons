<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

// 必须登录
if (!is_logged_in()) {
    header("Location: ../views/public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$action = isset($_POST['action']) ? $_POST['action'] : '';

// --- 1. 更新基本资料 (名字, 邮箱, 头像) ---
if ($action == 'update_info') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);

    // 简单的验证
    if (empty($full_name) || empty($email)) {
        header("Location: ../views/member/profile.php?error=empty_fields");
        exit();
    }

    // 处理头像上传
    // 先获取旧头像，以防没上传新图
    $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $current_photo = $stmt->fetchColumn();
    $final_photo = $current_photo;

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = uniqid('user_') . "." . $ext;
            $destination = "../images/uploads/" . $new_name;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
                $final_photo = $new_name;
                // 更新 Session 里的头像，让 Header 里的头像立马变
                $_SESSION['profile_photo'] = $new_name;
            }
        }
    }

    // 更新数据库
    $sql = "UPDATE users SET full_name = ?, email = ?, profile_photo = ? WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$full_name, $email, $final_photo, $user_id]);

    // 更新 Session 里的名字
    $_SESSION['user_name'] = $full_name;

    header("Location: ../views/member/profile.php?msg=info_updated");
    exit();
}

// --- 2. 修改密码 ---
if ($action == 'change_password') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 获取用户当前的哈希密码
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // 验证旧密码
    if (!password_verify($current_password, $user['password'])) {
        header("Location: ../views/member/profile.php?error=wrong_password");
        exit();
    }

    // 验证新密码
    if ($new_password !== $confirm_password) {
        header("Location: ../views/member/profile.php?error=password_mismatch");
        exit();
    }

    if (strlen($new_password) < 6) {
        header("Location: ../views/member/profile.php?error=password_short");
        exit();
    }

    // 更新密码
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$new_hash, $user_id]);

    header("Location: ../views/member/profile.php?msg=password_changed");
    exit();
}

if ($action == 'add_address') {
    $user_id = $_SESSION['user_id'];
    $address = clean_input($_POST['address']);
    $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, address_text) VALUES (?, ?)");
    $stmt->execute([$user_id, $address]);
    header("Location: ../views/member/profile.php?msg=address_added");
    exit();
}

if ($action == 'delete_address') {
    $address_id = intval($_POST['address_id']);
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE address_id = ? AND user_id = ?");
    $stmt->execute([$address_id, $_SESSION['user_id']]);
    header("Location: ../views/member/profile.php?msg=address_deleted");
    exit();
}
?>