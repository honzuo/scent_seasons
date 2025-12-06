<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

if (!isset($_GET['product_id'])) {
    header("Location: orders.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$product_id = intval($_GET['product_id']);

// 1. 验证用户是否买过该商品且订单已完成 (completed)
$sql_check = "SELECT COUNT(*) FROM orders o 
              JOIN order_items oi ON o.order_id = oi.order_id 
              WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'";
$stmt = $pdo->prepare($sql_check);
$stmt->execute([$user_id, $product_id]);
$has_bought = $stmt->fetchColumn();

if (!$has_bought) {
    die("Error: You can only review products you have purchased and received.");
}

// 2. 验证是否已经评价过 (防止重复评价)
$sql_exist = "SELECT COUNT(*) FROM reviews WHERE user_id = ? AND product_id = ?";
$stmt = $pdo->prepare($sql_exist);
$stmt->execute([$user_id, $product_id]);
$has_reviewed = $stmt->fetchColumn();

if ($has_reviewed) {
    die("Error: You have already reviewed this product.");
}

// 3. 获取产品信息 (为了显示名字和图片)
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
        
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
            <img src="../../images/products/<?php echo $product['image_path']; ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
            <div>
                <h3 style="margin: 0;"><?php echo $product['name']; ?></h3>
                <small style="color: gray;">Share your experience with this scent.</small>
            </div>
        </div>

        <form action="../../controllers/review_controller.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            
            <div class="form-group">
                <label>Rating:</label>
                <div style="font-size: 1.5em; color: #f1c40f;">
                    <select name="rating" required style="width: 100%; padding: 10px; font-size: 0.6em; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="5">★★★★★ - Excellent</option>
                        <option value="4">★★★★☆ - Good</option>
                        <option value="3">★★★☆☆ - Average</option>
                        <option value="2">★★☆☆☆ - Poor</option>
                        <option value="1">★☆☆☆☆ - Terrible</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Your Comment:</label>
                <textarea name="comment" rows="5" required placeholder="What did you like or dislike? How is the longevity?" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
            </div>

            <button type="submit" class="btn-green" style="width: 100%;">Submit Review</button>
            <a href="orders.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none;">Cancel</a>
        </form>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>