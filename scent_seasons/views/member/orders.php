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
    <div style="text-align: center; margin: 30px auto; padding: 40px; background: #fff; border-radius: 18px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 600px;">
        <div style="font-size: 64px; margin-bottom: 20px;">🎉</div>
        <h2 style="color: #27ae60; margin-bottom: 10px;">Order Successful!</h2>
        <p style="color: #666; margin-bottom: 30px;">Thank you for your purchase. We have received your order.</p>

        <a href="home.php" class="btn-blue" style="padding: 12px 30px; font-size: 16px;">Back to Home</a>
    </div>
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