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

// 1. 获取订单主信息
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found or access denied.");
}

// 2. 获取订单内的商品 (同时查一下是否已评价)
// 使用 LEFT JOIN reviews 来检查该用户对该产品是否已有记录
$sql = "SELECT oi.*, p.name, p.image_path,
        (SELECT COUNT(*) FROM reviews r WHERE r.user_id = ? AND r.product_id = p.product_id) as is_reviewed
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $order_id]);
$items = $stmt->fetchAll();

$page_title = "Order Details #$order_id";
$path = "../../";
$extra_css = "shop.css";

require $path . 'includes/header.php';
?>

<h2>Order Details #<?php echo $order_id; ?></h2>
<p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
<p>
    <strong>Status:</strong>
    <?php
    if ($order['status'] == 'completed') echo "<span style='color:green; font-weight:bold;'>Completed</span>";
    elseif ($order['status'] == 'pending') echo "<span style='color:orange; font-weight:bold;'>Pending</span>";
    else echo "<span style='color:red; font-weight:bold;'>Cancelled</span>";
    ?>
</p>

<table class="table-list">
    <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price Each</th>
            <th>Subtotal</th>
            <?php if ($order['status'] == 'completed'): ?>
                <th>Action</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <img src="../../images/products/<?php echo $item['image_path']; ?>" class="img-small">
                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" style="color:#333; text-decoration:none;">
                        <?php echo $item['name']; ?>
                    </a>
                </td>
                <td><?php echo $item['quantity']; ?></td>
                <td>$<?php echo $item['price_each']; ?></td>
                <td>$<?php echo number_format($item['quantity'] * $item['price_each'], 2); ?></td>

                <?php if ($order['status'] == 'completed'): ?>
                    <td>
                        <?php if ($item['is_reviewed'] > 0): ?>
                            <span style="color:gray; font-size:0.9em;">Reference Submitted</span>
                        <?php else: ?>
                            <a href="write_review.php?product_id=<?php echo $item['product_id']; ?>" class="btn-blue" style="padding: 5px 10px; font-size: 0.8em; text-decoration:none;">
                                Write Review
                            </a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 style="text-align:right;">Total Paid: $<?php echo $order['total_amount']; ?></h3>
<a href="orders.php">Back to My Orders</a>

<?php require $path . 'includes/footer.php'; ?>