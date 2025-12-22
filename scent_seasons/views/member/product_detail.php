<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

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

// 2. 检查是否已在收藏夹（仅登录用户）
$is_in_wishlist = false;
if (is_logged_in()) {
    $stmt_wish = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt_wish->execute([$_SESSION['user_id'], $id]);
    $is_in_wishlist = ($stmt_wish->rowCount() > 0);
}

// 3. 获取该产品的所有评价
$stmt_reviews = $pdo->prepare("SELECT r.*, u.full_name, u.profile_photo 
                               FROM reviews r 
                               JOIN users u ON r.user_id = u.user_id 
                               WHERE r.product_id = ? 
                               ORDER BY r.created_at DESC");
$stmt_reviews->execute([$id]);
$reviews = $stmt_reviews->fetchAll();

// 4. 计算平均分
$avg_rating = 0;
$total_reviews = count($reviews);
if ($total_reviews > 0) {
    $sum = 0;
    foreach ($reviews as $r) $sum += $r['rating'];
    $avg_rating = round($sum / $total_reviews, 1);
}

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

<?php if (isset($_GET['wishlist'])): ?>
    <div class="alert <?php echo ($_GET['wishlist'] == 'added') ? 'alert-success' : 'alert-info'; ?>" style="margin-bottom: 20px;">
        <?php
        if ($_GET['wishlist'] == 'added') echo "✓ Added to your wishlist!";
        elseif ($_GET['wishlist'] == 'removed') echo "Removed from your wishlist.";
        elseif ($_GET['wishlist'] == 'exists') echo "This item is already in your wishlist.";
        ?>
    </div>
<?php endif; ?>

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

        <h2 style="color: #0071e3;">$<?php echo $product['price']; ?></h2>
        <p><?php echo nl2br($product['description']); ?></p>

        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

        <?php if ($product['stock'] > 0): ?>
            <form action="../../controllers/cart_controller.php" method="POST" style="display: inline-block;">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">

                <label>Quantity:</label>
                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="qty-input">
                <br><br>
                <button type="submit" class="btn-green">Add to Cart</button>
            </form>

            <?php if (is_logged_in()): ?>
                <form action="../../controllers/wishlist_controller.php" method="POST" style="display: inline-block; margin-left: 12px;">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="from" value="detail">

                    <?php if ($is_in_wishlist): ?>
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn-wishlist btn-wishlist-active">
                            ♥ In Wishlist
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn-wishlist">
                            ♡ Add to Wishlist
                        </button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <?php else: ?>
            <button disabled class="btn-disabled">Out of Stock</button>

            <?php if (is_logged_in()): ?>
                <form action="../../controllers/wishlist_controller.php" method="POST" style="display: inline-block; margin-left: 12px;">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="from" value="detail">

                    <?php if ($is_in_wishlist): ?>
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" class="btn-wishlist btn-wishlist-active">
                            ♥ In Wishlist
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn-wishlist">
                            ♡ Save for Later
                        </button>
                    <?php endif; ?>
                </form>
                <p style="margin-top: 12px; color: #86868b; font-size: 14px;">
                    Add to wishlist to get notified when back in stock.
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php 
// 视频自动播放 + 静音逻辑修改
if (!empty($product['youtube_video_id'])) {
    $stmt_v = $pdo->prepare("SELECT video_id FROM youtube_videos WHERE id = ?");
    $stmt_v->execute([$product['youtube_video_id']]);
    $video = $stmt_v->fetch();

    if ($video) {
?>
    <div class="product-video-section" style="margin-top: 30px;">
        <h3>Product Video</h3>
        <div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000;">
            <iframe 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video['video_id']); ?>?autoplay=1&mute=1&controls=1" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
            </iframe>
        </div>
    </div>
<?php 
    }
} 
?>

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

                        <?php if (!empty($r['admin_reply'])): ?>
                            <div class="admin-reply-box">
                                <div class="reply-header">
                                    <span style="font-weight:bold; color:#2c3e50;">Store Response</span>
                                    <span style="font-size:0.8em; color:#999;">
                                        <?php echo $r['reply_at'] ? date('M d, Y', strtotime($r['reply_at'])) : ''; ?>
                                    </span>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($r['admin_reply'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No reviews yet. Be the first to review this perfume!</p>
        <?php endif; ?>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>