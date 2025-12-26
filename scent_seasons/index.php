<?php

session_start();
require 'includes/functions.php';


if (isset($_SESSION['user_id'])) {
    if (is_admin()) {
       
        header("Location: views/admin/dashboard.php");
    } else {
       
        header("Location: views/member/home.php");
    }
} else {
 
    header("Location: views/member/home.php");
}
exit();
