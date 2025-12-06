<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 获取所有订单，连同用户名一起查出来
$sql = "SELECT o.*, u.full_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        ORDER BY o.order_date DESC";
$orders = $pdo->query($sql)->fetchAll();

$page_title = "Order Management";
$path = "../../../"; // 注意这里是三层！
$extra_css = "admin.css"; // 引用 admin.css
?>

<h2>All Customer Orders</h2>

<table style="width:100%; border-collapse:collapse;">
    <thead>
        <tr style="background:#f2f2f2;">
            <th style="padding:10px; border:1px solid #ddd;">Order ID</th>
            <th style="padding:10px; border:1px solid #ddd;">Customer</th>
            <th style="padding:10px; border:1px solid #ddd;">Date</th>
            <th style="padding:10px; border:1px solid #ddd;">Total</th>
            <th style="padding:10px; border:1px solid #ddd;">Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td style="padding:10px; border:1px solid #ddd;">#<?php echo $o['order_id']; ?></td>
                <td style="padding:10px; border:1px solid #ddd;">
                    <?php echo $o['full_name']; ?><br>
                    <small style="color:gray;"><?php echo $o['email']; ?></small>
                </td>
                <td style="padding:10px; border:1px solid #ddd;"><?php echo $o['order_date']; ?></td>
                <td style="padding:10px; border:1px solid #ddd;">$<?php echo $o['total_amount']; ?></td>
                <td style="padding:10px; border:1px solid #ddd;"><?php echo ucfirst($o['status']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require $path . 'includes/footer.php'; ?>