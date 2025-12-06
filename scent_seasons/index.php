<?php
// index.php - 网站入口
require 'config/database.php'; // 保留数据库连接以便检查状态

// --- 1. 设置 Header 参数 ---
$page_title = "Welcome - Scent Seasons";
$path = "./"; // 根目录，不需要回退，直接用 "./"
// $extra_css = ""; // 首页暂时只用 common.css 就够了

require $path . 'includes/header.php';
?>

<div style="text-align: center; padding: 50px 0;">
    <h1 style="font-size: 3em; margin-bottom: 10px;">Welcome to Scent Seasons</h1>
    <p style="font-size: 1.2em; color: #666;">Discover your signature fragrance.</p>

    <div style="margin: 30px auto; padding: 15px; background: white; border: 1px solid #ddd; border-radius: 8px; display: inline-block;">
        <strong>System Status:</strong>
        Database Connection
        <?php echo isset($pdo) ? "<span style='color:green; font-weight:bold;'>Active</span>" : "<span style='color:red; font-weight:bold;'>Failed</span>"; ?>
    </div>

    <br><br>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <p>Please login or register to start shopping.</p>
        <div style="margin-top: 20px;">
            <a href="views/public/login.php" class="btn-blue">Login</a>
            <a href="views/public/register.php" class="btn-green" style="margin-left: 15px;">Register Now</a>
        </div>
    <?php else: ?>
        <p>Welcome back, <strong><?php echo $_SESSION['user_name'] ?? 'User'; ?></strong>!</p>
        <div style="margin-top: 20px;">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <a href="views/admin/dashboard.php" class="btn-blue">Go to Dashboard</a>
            <?php else: ?>
                <a href="views/member/home.php" class="btn-green">Go to Shop</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php require $path . 'includes/footer.php'; ?>