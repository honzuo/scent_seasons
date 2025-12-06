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
    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <thead>
            <tr style="background:#eee;">
                <th style="padding:10px; border:1px solid #ddd;">Order ID</th>
                <th style="padding:10px; border:1px solid #ddd;">Date</th>
                <th style="padding:10px; border:1px solid #ddd;">Total</th>
                <th style="padding:10px; border:1px solid #ddd;">Status</th>
                <th style="padding:10px; border:1px solid #ddd;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td style="padding:10px; border:1px solid #ddd;">#<?php echo $o['order_id']; ?></td>
                    <td style="padding:10px; border:1px solid #ddd;"><?php echo $o['order_date']; ?></td>
                    <td style="padding:10px; border:1px solid #ddd;">$<?php echo $o['total_amount']; ?></td>
                    <td style="padding:10px; border:1px solid #ddd;"><?php echo ucfirst($o['status']); ?></td>
                    <td style="padding:10px; border:1px solid #ddd;">
                        <a href="order_detail.php?id=<?php echo $o['order_id']; ?>" style="color:blue;">View Items</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>You haven't placed any orders yet.</p>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>