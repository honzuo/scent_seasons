<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';


require_superadmin();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

// --- 1. create Admin ---
if ($action == 'create_admin') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    
    if (empty($full_name) || empty($email) || empty($password)) {
        header("Location: ../views/admin/users/index.php?error=empty");
        exit();
    }

    if ($password !== $confirm_password) {
        header("Location: ../views/admin/users/index.php?error=mismatch");
        exit();
    }

    
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        header("Location: ../views/admin/users/index.php?error=exists");
        exit();
    }

    $photo_name = 'default.jpg';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (full_name, email, password, role, profile_photo) VALUES (?, ?, ?, 'admin', ?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$full_name, $email, $hashed_password, $photo_name])) {
        log_activity($pdo, "Create Admin", "Created admin: $email");
       
        header("Location: ../views/admin/users/index.php?msg=created");
    } else {
        die("Database error");
    }
    exit();
}

// --- 2. delete Admin ---
if ($action == 'delete') {
    $user_id = intval($_POST['user_id']);

    if ($user_id == $_SESSION['user_id']) {
        die("Cannot delete yourself.");
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'admin'");
    $stmt->execute([$user_id]);

    log_activity($pdo, "Delete Admin", "Deleted user ID: $user_id");
    header("Location: ../views/admin/users/index.php?msg=deleted");
    exit();
}


if ($action == 'toggle_block') {
    $user_id = intval($_POST['user_id']);
    $block_status = intval($_POST['is_blocked']);

    if ($user_id == $_SESSION['user_id']) {
        die("Cannot block yourself.");
    }

    $stmt = $pdo->prepare("UPDATE users SET is_blocked = ? WHERE user_id = ? AND role = 'admin'");
    $stmt->execute([$block_status, $user_id]);

    $status_msg = ($block_status == 1) ? "Blocked" : "Unblocked";
    log_activity($pdo, "$status_msg Admin", "User ID: $user_id");

    header("Location: ../views/admin/users/index.php?msg=updated");
    exit();
}

// 4. update Admin information
if ($action == 'update_admin') {
    $user_id = intval($_POST['user_id']);
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password']; 

  
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET full_name = ?, email = ?, password = ? WHERE user_id = ? AND role = 'admin'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $email, $hashed_password, $user_id]);
    } else {
        $sql = "UPDATE users SET full_name = ?, email = ? WHERE user_id = ? AND role = 'admin'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$full_name, $email, $user_id]);
    }

    log_activity($pdo, "Update Admin", "Updated admin ID: $user_id");
    header("Location: ../views/admin/users/index.php?msg=updated");
    exit();
}
