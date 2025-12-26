<?php

session_start();


require 'config/database.php';
require 'includes/functions.php';


if (isset($_SESSION['user_id'])) {

  
    if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'superadmin')) {
        log_activity($pdo, "Logout", "Admin logged out.");
    }
}

session_destroy();


header("Location: views/public/login.php");
exit();
