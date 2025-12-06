<?php
// fix_password.php
require 'config/database.php';

// 设置你想要的新密码
$new_password = 'abcd1234';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// 强制更新 ID 为 1 的用户 (通常是第一个创建的管理员)
// 或者你可以把 WHERE 条件改成 email = 'admin@scent.com'
$sql = "UPDATE users SET password = ? WHERE user_id = 1";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hashed_password]);
    echo "<h1 style='color:green'>Password Reset Successfully!</h1>";
    echo "<p>User ID 1 password has been reset to: <strong>$new_password</strong></p>";
    echo "<p>Please delete this file after use.</p>";
    echo "<a href='views/public/login.php'>Go to Login</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>