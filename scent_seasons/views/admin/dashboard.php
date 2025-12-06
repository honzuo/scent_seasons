<?php
session_start();
require '../../includes/functions.php';
require_admin(); // 强制检查是否是管理员

$page_title = "Admin Dashboard";
$path = "../../"; // views/admin/dashboard.php 回根目录是 2 层
$extra_css = "admin.css"; // 引用 admin.css

require $path . 'includes/header.php';
?>

<h1>Welcome, Admin!</h1>
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
        <a href="#">Go to Members &rarr;</a>
    </div>

    <div class="card">
        <h3>Orders</h3>
        <p>View customer orders.</p>
        <a href="orders/index.php">Go to Orders &rarr;</a>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>