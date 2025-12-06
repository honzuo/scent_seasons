<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) { header("Location: ../public/login.php"); exit(); }
if (!isset($_GET['id'])) { header("Location: orders.php"); exit(); }

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// 1. 获取订单主信息
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) { die("Order not found."); }

// 2. 获取商品信息 (修改点：检查评价时加上 order_id)
// 这里的 is_reviewed 现在只检查“这一单”有没有评价
$sql = "SELECT oi.*, p.name, p.image_path,
        (SELECT COUNT(*) FROM reviews r WHERE r.user_id = ? AND r.product_id = p.product_id AND r.order_id = ?) as is_reviewed
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $order_id, $order_id]); // 注意参数顺序：user, order, order
$items = $stmt->fetchAll();

$raw_status = $order['status']; 
$status = trim(strtolower($raw_status)); 

$page_title = "Order Details #$order_id";
$path = "../../";
$extra_css = "shop.css"; 

require $path . 'includes/header.php';
?>

<div style="background:white; padding:30px; border-radius:8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
    
    <div style="border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
        <h2 style="margin:0 0 10px 0;">Order #<?php echo $order_id; ?></h2>
        <p style="margin:5px 0;"><strong>Date:</strong> <?php echo $order['order_date']; ?></p>
        <p style="margin:5px 0;">
            <strong>Status:</strong> 
            <?php 
                if ($status == 'completed') {
                    echo "<span style='color:green; font-weight:bold; font-size:1.1em;'>Completed</span>";
                } elseif ($status == 'pending') {
                    echo "<span style='color:orange; font-weight:bold;'>Pending</span>";
                } else {
                    echo "<span style='color:red; font-weight:bold;'>" . ucfirst($status) . "</span>";
                }
            ?>
        </p>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-top:20px;">
        <thead>
            <tr style="background:#f8f9fa; border-bottom:2px solid #ddd; text-align:left;">
                <th style="padding:12px;">Product</th>
                <th style="padding:12px;">Quantity</th>
                <th style="padding:12px;">Price</th>
                <th style="padding:12px;">Subtotal</th>
                <?php if ($status == 'completed'): ?>
                    <th style="padding:12px;">Action</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr style="border-bottom:1px solid #eee;">
                <td style="padding:12px; vertical-align:middle;">
                    <div style="display:flex; align-items:center;">
                        <img src="../../images/products/<?php echo $item['image_path']; ?>" style="width:50px; height:50px; object-fit:cover; margin-right:15px; border:1px solid #ddd; border-radius:4px;">
                        <span style="font-weight:bold; color:#333;"><?php echo $item['name']; ?></span>
                    </div>
                </td>
                <td style="padding:12px; vertical-align:middle;"><?php echo $item['quantity']; ?></td>
                <td style="padding:12px; vertical-align:middle;">$<?php echo $item['price_each']; ?></td>
                <td style="padding:12px; vertical-align:middle; font-weight:bold;">$<?php echo number_format($item['quantity'] * $item['price_each'], 2); ?></td>
                
                <?php if ($status == 'completed'): ?>
                    <td style="padding:12px; vertical-align:middle;">
                        <?php if ($item['is_reviewed'] > 0): ?>
                            <span style="color:#999; font-size:0.9em; background:#f5f5f5; padding:5px 10px; border-radius:20px; border:1px solid #ddd;">
                                &#10003; Reviewed
                            </span>
                        <?php else: ?>
                            <a href="write_review.php?product_id=<?php echo $item['product_id']; ?>&order_id=<?php echo $order_id; ?>" 
                               style="display:inline-block; background-color:#3498db; color:white; padding:8px 15px; text-decoration:none; border-radius:4px; font-size:0.9em; font-weight:bold; transition: background 0.3s;">
                                &#9733; Write Review
                            </a>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="text-align:right; margin-top:30px; font-size:1.3em; font-weight:bold; color:#2c3e50;">
        Total Paid: $<?php echo $order['total_amount']; ?>
    </div>

    <div style="margin-top:20px;">
        <a href="orders.php" style="text-decoration:none; color:#666;">&larr; Back to My Orders</a>
    </div>

</div>

<?php require $path . 'includes/footer.php'; ?>