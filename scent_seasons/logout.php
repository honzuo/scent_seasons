<?php
// logout.php
session_start();

// 1. 引入必要文件
require 'config/database.php';
require 'includes/functions.php';

// 2. 记录登出日志
if (isset($_SESSION['user_id'])) {

    // [关键修改] 增加权限判断：只有管理员/超级管理员才记录日志
    // 普通会员 (member) 登出不记录，保持日志干净
    if (isset($_SESSION['role']) && ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'superadmin')) {
        log_activity($pdo, "Logout", "Admin logged out.");
    }
}

// 3. 销毁会话
session_destroy();

// 4. 跳转回登录页
header("Location: views/public/login.php");
exit();
