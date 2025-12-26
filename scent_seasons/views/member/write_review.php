<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) { header("Location: ../public/login.php"); exit(); }

// 必须同时有 product_id 和 order_id
if (!isset($_GET['product_id']) || !isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_GET['product_id']);
$order_id = intval($_GET['order_id']); // 新增

// 1. 验证：必须是这一单里的这个商品，且已完成
$sql_check = "SELECT COUNT(*) FROM orders o 
              JOIN order_items oi ON o.order_id = oi.order_id 
              WHERE o.user_id = ? AND o.order_id = ? AND oi.product_id = ? AND o.status = 'completed'";
$stmt = $pdo->prepare($sql_check);
$stmt->execute([$user_id, $order_id, $product_id]);

if ($stmt->fetchColumn() == 0) {
    die("Error: Invalid order or product.");
}

// 2. 验证：这一单的这个商品是否已评价 (按 order_id 查)
$sql_exist = "SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?";
$stmt = $pdo->prepare($sql_exist);
$stmt->execute([$user_id, $product_id, $order_id]);

if ($stmt->fetchColumn() > 0) {
    die("Error: You have already reviewed this item from this order.");
}

$stmt_prod = $pdo->prepare("SELECT name, image_path FROM products WHERE product_id = ?");
$stmt_prod->execute([$product_id]);
$product = $stmt_prod->fetch();

$page_title = "Write Review";
$path = "../../";
$extra_css = "shop.css"; 

require $path . 'includes/header.php';
?>

<div class="container" style="max-width: 600px; margin-top: 30px;">
    <div class="review-form-box" style="background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0;">Write a Review</h2>
        <p style="color:gray; font-size:0.9em;">Order #<?php echo $order_id; ?></p>
        
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <img src="../../images/products/<?php echo $product['image_path']; ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
            <div>
                <h3 style="margin: 0;"><?php echo $product['name']; ?></h3>
            </div>
        </div>

        <form action="../../controllers/review_controller.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>"> <div class="form-group">
                <label>Rating:</label>
                <select name="rating" required style="width: 100%; padding: 10px;">
                    <option value="5">★★★★★ - Excellent</option>
                    <option value="4">★★★★☆ - Good</option>
                    <option value="3">★★★☆☆ - Average</option>
                    <option value="2">★★☆☆☆ - Poor</option>
                    <option value="1">★☆☆☆☆ - Terrible</option>
                </select>
            </div>

            <div class="form-group">
                <label>Your Comment:</label>
                <textarea name="comment" rows="5" required style="width: 100%; padding: 10px;"></textarea>
            </div>

            <button type="submit" class="btn-green" style="width: 100%;">Submit Review</button>
            <a href="order_detail.php?id=<?php echo $order_id; ?>" style="display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #666;">Cancel</a>
        </form>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>