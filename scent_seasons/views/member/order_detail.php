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

// 1. 获取订单主信息 (确保只能看自己的订单)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found or access denied.");
}

// 2. 获取订单内的商品
$sql = "SELECT oi.*, p.name, p.image_path 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

$page_title = "Shop - Scent Seasons";
$path = "../../";
$extra_css = "shop.css"; // 引用 shop.css

require $path . 'includes/header.php';
?>

<h2>Order Details #<?php echo $order_id; ?></h2>
<p><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
<p><strong>Status:</strong> <?php echo ucfirst($order['status']); ?></p>

<table style="width:100%; border-collapse:collapse; margin-top:20px;">
    <thead>
        <tr style="background:#eee;">
            <th style="padding:10px; border:1px solid #ddd;">Product</th>
            <th style="padding:10px; border:1px solid #ddd;">Quantity</th>
            <th style="padding:10px; border:1px solid #ddd;">Price Each</th>
            <th style="padding:10px; border:1px solid #ddd;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td style="padding:10px; border:1px solid #ddd;">
                    <img src="../../images/products/<?php echo $item['image_path']; ?>" style="width:50px; vertical-align:middle;">
                    <?php echo $item['name']; ?>
                </td>
                <td style="padding:10px; border:1px solid #ddd;"><?php echo $item['quantity']; ?></td>
                <td style="padding:10px; border:1px solid #ddd;">$<?php echo $item['price_each']; ?></td>
                <td style="padding:10px; border:1px solid #ddd;">$<?php echo number_format($item['quantity'] * $item['price_each'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 style="text-align:right;">Total Paid: $<?php echo $order['total_amount']; ?></h3>
<a href="orders.php">Back to My Orders</a>

<?php require $path . 'includes/footer.php'; ?>