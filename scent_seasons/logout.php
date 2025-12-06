<?php
// logout.php
session_start();
session_destroy();
header("Location: views/public/login.php");
exit();
?>