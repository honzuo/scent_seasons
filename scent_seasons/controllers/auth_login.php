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
      
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
           
            if ($user['is_blocked'] == 1) {
                $errors['login'] = "Your account has been blocked by admin.";
                $_SESSION['errors'] = $errors;
                header("Location: ../views/public/login.php");
                exit();
            }

         
            if ($user['lock_until'] && strtotime($user['lock_until']) > time()) {
              
                $remaining = ceil((strtotime($user['lock_until']) - time()) / 60);
                $errors['login'] = "Account locked due to too many failed attempts. Please try again in $remaining minutes.";

                $_SESSION['errors'] = $errors;
                header("Location: ../views/public/login.php");
                exit();
            }

            
            if (password_verify($password, $user['password'])) {
              
                $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = NULL WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);

             
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['profile_photo'] = $user['profile_photo'];

              
                if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'superadmin')) {
                    log_activity($pdo, "Login", "User logged in.");
                    header("Location: ../views/admin/dashboard.php");
                } else {
                    header("Location: ../views/member/home.php");
                }
                exit();
            } else {
              
                $attempts = $user['failed_attempts'] + 1;

                if ($attempts >= 5) {
                
                    $lock_time = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                   
                    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, lock_until = ? WHERE user_id = ?");
                    $stmt->execute([$lock_time, $user['user_id']]);

                    $errors['login'] = "Too many failed attempts. Account locked for 10 minutes.";
                } else {
                  
                    $stmt = $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE user_id = ?");
                    $stmt->execute([$attempts, $user['user_id']]);

                    $remaining_tries = 5 - $attempts;
                    $errors['login'] = "Invalid password. You have $remaining_tries attempts remaining.";
                }
            }
        } else {
          
            $errors['login'] = "Invalid email or password.";
        }
    }

   
    $_SESSION['errors'] = $errors;
    header("Location: ../views/public/login.php");
    exit();
}
