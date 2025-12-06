<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php'; // 确保这里引用了 functions

if (!isset($_GET['id'])) {
    header("Location: home.php");
    exit();
}
$id = intval($_GET['id']);

// 1. 获取产品详情
$stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found");
}

// 2. 获取该产品的所有评价 (联表查询用户头像和名字)
$stmt_reviews = $pdo->prepare("SELECT r.*, u.full_name, u.profile_photo 
                               FROM reviews r 
                               JOIN users u ON r.user_id = u.user_id 
                               WHERE r.product_id = ? 
                               ORDER BY r.created_at DESC");
$stmt_reviews->execute([$id]);
$reviews = $stmt_reviews->fetchAll();

// 3. 计算平均分
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $sum = 0;
    foreach ($reviews as $r) $sum += $r['rating'];
    $avg_rating = round($sum / $total_reviews, 1);
}

// 生成星星的辅助函数 (显示用)
function render_stars($rating)
{
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) $stars .= "★";
        else $stars .= "☆";
    }
    return $stars;
}

$page_title = $product['name'];
$path = "../../";
$extra_css = "shop.css";

require $path . 'includes/header.php';
?>

<div class="detail-container">
    <div class="detail-img">
        <img src="../../images/products/<?php echo $product['image_path']; ?>" style="width: 100%; border-radius: 8px;">
    </div>

    <div class="detail-info">
        <h1 style="margin-top:0;"><?php echo $product['name']; ?></h1>
        <p style="color: gray;"><?php echo $product['category_name']; ?></p>

        <div style="margin-bottom:10px; color:#f1c40f; font-size:1.2em;">
            <?php echo render_stars(round($avg_rating)); ?>
            <span style="color:#555; font-size:0.8em;">(<?php echo $total_reviews; ?> reviews)</span>
        </div>

        <h2 style="color: #e67e22;">$<?php echo $product['price']; ?></h2>
        <p><?php echo nl2br($product['description']); ?></p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <form action="../../controllers/cart_controller.php" method="POST">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">

            <label>Quantity:</label>
            <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="qty-input">

            <br><br>
            <?php if ($product['stock'] > 0): ?>
                <button type="submit" class="btn-green">Add to Cart</button>
            <?php else: ?>
                <button disabled class="btn-disabled">Out of Stock</button>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="reviews-container">
    <div class="review-summary">
        <div class="big-rating"><?php echo $avg_rating; ?></div>
        <div>
            <div class="star-yellow" style="font-size:1.5em;">
                <?php echo render_stars(round($avg_rating)); ?>
            </div>
            <div style="color:#777;">Based on <?php echo $total_reviews; ?> reviews</div>
        </div>
    </div>

    <div class="review-list">
        <?php if ($total_reviews > 0): ?>
            <?php foreach ($reviews as $r): ?>
                <div class="review-item">
                    <div class="review-avatar">
                        <img src="../../images/uploads/<?php echo $r['profile_photo']; ?>" alt="User">
                    </div>
                    <div class="review-content">
                        <div class="review-header">
                            <span class="reviewer-name"><?php echo htmlspecialchars($r['full_name']); ?></span>
                            <span class="review-date"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                        </div>
                        <div class="star-yellow"><?php echo render_stars($r['rating']); ?></div>
                        <p class="review-text"><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No reviews yet. Be the first to review this perfume!</p>
        <?php endif; ?>
    </div>

    <?php if (is_logged_in()): ?>
        <div class="review-form-box">
            <h3>Write a Review</h3>
            <form action="../../controllers/review_controller.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $id; ?>">

                <div class="form-group">
                    <label>Rating:</label>
                    <select name="rating" required style="width:150px;">
                        <option value="5">★★★★★ (5)</option>
                        <option value="4">★★★★☆ (4)</option>
                        <option value="3">★★★☆☆ (3)</option>
                        <option value="2">★★☆☆☆ (2)</option>
                        <option value="1">★☆☆☆☆ (1)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Your Review:</label>
                    <textarea name="comment" rows="3" required placeholder="Tell us what you think about this scent..."></textarea>
                </div>

                <button type="submit" class="btn-blue">Submit Review</button>
            </form>
        </div>
    <?php else: ?>
        <p style="margin-top:20px;">Please <a href="../public/login.php">login</a> to write a review.</p>
    <?php endif; ?>
</div>

<?php require $path . 'includes/footer.php'; ?>