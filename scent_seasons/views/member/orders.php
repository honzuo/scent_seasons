<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取当前用户的订单
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$page_title = "Shop - Scent Seasons";
$path = "../../";
$extra_css = "shop.css"; // 引用 shop.css

require $path . 'includes/header.php';
?>

<h2>My Order History</h2>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
    <p style="color:green; font-weight:bold;">Order placed successfully! Thank you.</p>
<?php endif; ?>

<?php if (count($orders) > 0): ?>
    <table class="table-list">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?php echo $o['order_id']; ?></td>
                    <td><?php echo $o['order_date']; ?></td>
                    <td>$<?php echo $o['total_amount']; ?></td>
                    <td>
                        <?php
                        $status = ucfirst($o['status']);
                        if ($o['status'] == 'completed') echo "<span class='text-green-bold'>$status</span>";
                        else echo $status;
                        ?>
                    </td>
                    <td>
                        <a href="order_detail.php?id=<?php echo $o['order_id']; ?>" class="btn-blue" style="padding:5px 12px; font-size:12px;">View Items</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>You haven't placed any orders yet.</p>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>