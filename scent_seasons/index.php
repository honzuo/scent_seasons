<?php
// index.php - Redirect to appropriate home page
session_start();
require 'includes/functions.php';

// Redirect based on user role
if (isset($_SESSION['user_id'])) {
    if (is_admin()) {
        // Admin users go to admin dashboard
        header("Location: views/admin/dashboard.php");
    } else {
        // Member users go to member home page
        header("Location: views/member/home.php");
    }
} else {
    // [修改] 未登录用户现在也直接去首页，而不是强制登录
    header("Location: views/member/home.php");
}
exit();
