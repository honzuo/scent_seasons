<?php
session_start();
require '../../includes/functions.php';
require_admin(); // 强制检查是否是管理员

$page_title = "Admin Dashboard";
$path = "../../"; // views/admin/dashboard.php 回根目录是 2 层
$extra_css = "admin.css"; // 引用 admin.css

require $path . 'includes/header.php';
?>

<h1>Welcome, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin'; ?></h1>
<p>Manage your perfume shop from here.</p>

<div class="dashboard-grid">
    <div class="card">
        <h3>Products</h3>
        <p>Manage perfumes, stock & prices.</p>
        <a href="products/index.php">Go to Products &rarr;</a>
    </div>

    <div class="card">
        <h3>Members</h3>
        <p>View registered members.</p>
        <a href="members/index.php">Go to Members &rarr;</a>
    </div>

    <div class="card">
        <h3>Orders</h3>
        <p>View customer orders.</p>
        <a href="orders/index.php">Go to Orders &rarr;</a>
    </div>

    <div class="card">
        <h3>Categories</h3>
        <p>Manage product categories.</p>
        <a href="categories/index.php">Go to Categories &rarr;</a>
    </div>

    <div class="card">
        <h3>Reviews</h3>
        <p>Moderating user reviews.</p>
        <a href="reviews/index.php">Go to Reviews &rarr;</a>
    </div>

    <div class="card">
        <h3>Activity Logs</h3>
        <p>Track system activities.</p>
        <a href="logs/index.php">View Logs &rarr;</a>
    </div>

    <div class="card" style="border-top: 4px solid #0c9ef5;">
        <h3 style="color:#0c9ef5;">Chat</h3>
        <p>Chat with members in real time.</p>
        <a href="chat/index.php" style="color:#0c9ef5;">Open Chat &rarr;</a>
    </div>

    <div class="card" style="border-top: 4px solid #0071e3;">
        <h3 style="color:#0071e3;">Reports</h3>
        <p>Product performance & analytics.</p>
        <a href="reports/index.php" style="color:#0071e3;">View Reports &rarr;</a>
    </div>

    <?php if (is_superadmin()): ?>
        <div class="card" style="border-top: 4px solid #8e44ad;">
            <h3 style="color:#8e44ad;">Admin Maintenance</h3>
            <p>Manage system administrators.</p>
            <a href="users/index.php" style="color:#8e44ad;">Manage Admins &rarr;</a>
        </div>
    <?php endif; ?>

</div>

<?php require $path . 'includes/footer.php'; ?>