<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 接收筛选参数
$filter_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// 构建 SQL 查询
$sql = "SELECT o.*, u.full_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id";

if ($filter_user_id > 0) {
    $sql .= " WHERE o.user_id = ?";
    $sql .= " ORDER BY o.order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$filter_user_id]);
} else {
    $sql .= " ORDER BY o.order_date DESC";
    $stmt = $pdo->query($sql);
}

$orders = $stmt->fetchAll();

// 动态设置标题
$page_title = ($filter_user_id > 0) ? "Orders for User #$filter_user_id" : "Order Management";

// --- 关键修复在这里 ---
$path = "../../../"; // 必须是 3 层！
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center;">
    <h2>
        <?php if ($filter_user_id > 0): ?>
            Orders for User #<?php echo $filter_user_id; ?>
        <?php else: ?>
            All Customer Orders
        <?php endif; ?>
    </h2>

    <?php if ($filter_user_id > 0): ?>
        <a href="index.php" class="btn-blue" style="font-size:0.8em;">Show All Orders</a>
    <?php endif; ?>
</div>

<?php if (count($orders) > 0): ?>
    <table class="table-list">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?php echo $o['order_id']; ?></td>
                    <td>
                        <?php echo $o['full_name']; ?><br>
                        <small style="color:gray;"><?php echo $o['email']; ?></small>
                    </td>
                    <td><?php echo $o['order_date']; ?></td>
                    <td>$<?php echo $o['total_amount']; ?></td>
                    <td><?php echo ucfirst($o['status']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="color: gray; margin-top: 20px;">No orders found.</p>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>