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
    // Non-logged-in users go to login page
    header("Location: views/public/login.php");
}
exit();
?>