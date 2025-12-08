<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();
if (!$order) {
    die("Order not found.");
}

$sql = "SELECT oi.*, p.name, p.image_path, (SELECT COUNT(*) FROM reviews r WHERE r.user_id = ? AND r.product_id = p.product_id AND r.order_id = ?) as is_reviewed FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $order_id, $order_id]);
$items = $stmt->fetchAll();
$status = trim(strtolower($order['status']));

$page_title = "Order Details #$order_id";
$path = "../../";
$extra_css = "shop.css";
require $path . 'includes/header.php';
?>

<div class="order-detail-box">
    <div class="order-header">
        <h2 class="mt-0">Order #<?php echo $order_id; ?></h2>
        <p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
        <p><strong>Shipping Address:</strong><br> <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
        <p>
            <strong>Status:</strong>
            <?php
            if ($status == 'completed') echo "<span class='text-green-bold'>Completed</span>";
            elseif ($status == 'pending') echo "<span class='text-orange-bold'>Pending</span>";
            else echo "<span class='text-red-bold'>" . ucfirst($status) . "</span>";
            ?>
        </p>
    </div>

    <table class="table-list">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
                <?php if ($status == 'completed'): ?><th>Action</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="flex-center" style="justify-content: flex-start;">
                            <img src="../../images/products/<?php echo $item['image_path']; ?>" class="thumbnail" style="margin-right:15px;">
                            <span style="font-weight:bold;"><?php echo $item['name']; ?></span>
                        </div>
                    </td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo $item['price_each']; ?></td>
                    <td style="font-weight:bold;">$<?php echo number_format($item['quantity'] * $item['price_each'], 2); ?></td>

                    <?php if ($status == 'completed'): ?>
                        <td>
                            <?php if ($item['is_reviewed'] > 0): ?>
                                <span class="tag-reviewed">&#10003; Reviewed</span>
                            <?php else: ?>
                                <a href="write_review.php?product_id=<?php echo $item['product_id']; ?>&order_id=<?php echo $order_id; ?>" class="btn-review">&#9733; Write Review</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-total">
        Total Paid: $<?php echo $order['total_amount']; ?>
    </div>

    <div class="mt-20">
        <a href="orders.php" style="text-decoration:none; color:#666;">&larr; Back to My Orders</a>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>