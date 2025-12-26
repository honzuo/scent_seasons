<?php
session_start();
require '../config/database.php';
require '../includes/functions.php';

$errors = [];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

   
    $name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

  
    if (empty($name)) {
        $errors['name'] = "Name is required.";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } else {
       
        $validation = validate_password_strength($password);
        if (!$validation['valid']) {
            $errors['password'] = implode(' ', $validation['errors']);
        }
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = "Passwords do not match.";
    }


    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors['email'] = "Email already registered.";
    }

 
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


    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (full_name, email, password, role, profile_photo) VALUES (?, ?, ?, 'member', ?)";
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $email, $hashed_password, $photo_name]);

       
            $_SESSION['success_msg'] = "Registration successful! Please login.";
            header("Location: ../views/public/login.php");
            exit();
        } catch (PDOException $e) {
            $errors['db'] = "Database error: " . $e->getMessage();
        }
    } else {
       
        $_SESSION['errors'] = $errors;
        $_SESSION['old_input'] = $_POST;
        header("Location: ../views/public/register.php");
        exit();
    }
}