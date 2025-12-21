<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$page_title = "Shop - Scent Seasons";
$path = "../../";
$extra_css = "shop.css"; 
require $path . 'includes/header.php';
?>

<h2>My Order History</h2>

<?php if (isset($_GET['msg'])): ?>
    <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;
        <?php echo ($_GET['msg'] == 'cancelled' || $_GET['msg'] == 'returned') ? 'background:#fff3cd; color:#856404;' : 'background:#d4edda; color:#155724;'; ?>">
        <?php 
            if ($_GET['msg'] == 'success') echo "✓ Order Placed Successfully!";
            elseif ($_GET['msg'] == 'cancelled') echo "Order has been cancelled.";
            elseif ($_GET['msg'] == 'returned') echo "Return request has been submitted.";
            elseif ($_GET['msg'] == 'cannot_cancel') echo "This order cannot be cancelled.";
        ?>
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
                    <td><?php echo date('M d, Y', strtotime($o['order_date'])); ?></td>
                    <td>$<?php echo number_format($o['total_amount'], 2); ?></td>
                    <td>
                        <?php
                        $status = strtolower($o['status']);
                        if ($status == 'completed') {
                            echo "<span style='color:#30d158; font-weight:bold;'>✓ Completed</span>";
                        } elseif ($status == 'cancelled') {
                            echo "<span style='color:#ff3b30; font-weight:bold;'>✗ Cancelled</span>";
                        } elseif ($status == 'returned') {
                            echo "<span style='color:#ff9500; font-weight:bold;'>↩ Returned</span>";
                        } elseif ($status == 'pending') {
                            echo "<span style='color:#ff9500; font-weight:bold;'>⏳ Pending</span>";
                        } else {
                            echo "<span style='color:#1d1d1f; font-weight:bold;'>" . ucfirst($status) . "</span>";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="order_detail.php?id=<?php echo $o['order_id']; ?>" 
                           class="btn-blue" 
                           style="padding:8px 16px; font-size:14px; text-decoration:none;">
                            View Details
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <div style="text-align: center; padding: 80px 20px; background: white; border-radius: 18px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);">
        <div style="font-size: 64px; color: #d2d2d7; margin-bottom: 16px;">📦</div>
        <h3 style="color: #1d1d1f; margin-bottom: 8px;">No Orders Yet</h3>
        <p style="color: #6e6e73; margin-bottom: 24px;">You haven't placed any orders yet.</p>
        <a href="shop.php" class="btn-blue" style="text-decoration: none;">Start Shopping</a>
    </div>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>