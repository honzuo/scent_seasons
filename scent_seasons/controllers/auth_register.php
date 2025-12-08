<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

$errors = [];

// 只有 POST 请求才处理
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. 接收并清洗数据
    $name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. 服务器端验证
    if (empty($name)) {
        $errors['name'] = "Name is required.";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    // 密码验证
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } else {
        // 使用强密码验证
        $validation = validate_password_strength($password);
        if (!$validation['valid']) {
            $errors['password'] = implode(' ', $validation['errors']);
        }
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // 检查邮箱是否已存在
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors['email'] = "Email already registered.";
    }

    // 3. 处理头像上传
    $photo_name = 'default.jpg';
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_name = uniqid('user_') . "." . $ext;
            $destination = "../images/uploads/" . $new_name;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $destination)) {
                $photo_name = $new_name;
            } else {
                $errors['photo'] = "Failed to upload image.";
            }
        } else {
            $errors['photo'] = "Only JPG, JPEG, PNG allowed.";
        }
    }

    // 4. 如果没有错误，写入数据库
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, password, role, profile_photo) VALUES (?, ?, ?, 'member', ?)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $email, $hashed_password, $photo_name]);

            // 注册成功，跳转到登录页
            $_SESSION['success_msg'] = "Registration successful! Please login.";
            header("Location: ../views/public/login.php");
            exit();
        } catch (PDOException $e) {
            $errors['db'] = "Database error: " . $e->getMessage();
        }
    } else {
        // 如果有错误，把错误信息存入 Session 传回页面显示
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: ../views/public/register.php");
        exit();
    }
}