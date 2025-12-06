<?php
// config/database.php

$host = 'localhost';
$db_name = 'scent_seasons_db'; // 确保这里跟你在 PHPMyAdmin 创建的名字一样
$username = 'root'; // XAMPP 默认是 root
$password = '';     // XAMPP 默认密码为空

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);

    // 设置 PDO 错误模式为异常，这样写代码出错时会报错，方便调试
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 默认获取关联数组
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 实际生产中不要直接 echo 错误，但作业调试需要
    die("Connection failed: " . $e->getMessage());
}
?>