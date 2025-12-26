<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: shop.php");
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

// 1.5 获取相册图片 (Multiple Images)
$stmt_imgs = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
$stmt_imgs->execute([$id]);
$gallery_rows = $stmt_imgs->fetchAll();

// 合并图片数组 (主图 + 相册图)
$all_images = [];
if (!empty($product['image_path'])) {
    $all_images[] = $product['image_path'];
}
foreach ($gallery_rows as $row) {
    $all_images[] = $row['image_path'];
}
if (empty($all_images)) {
    $all_images[] = 'default_product.jpg';
}

// 2. 检查收藏夹
$is_in_wishlist = false;
if (is_logged_in()) {
    $stmt_wish = $pdo->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt_wish->execute([$_SESSION['user_id'], $id]);
    $is_in_wishlist = ($stmt_wish->rowCount() > 0);
}

// 3. 获取评价
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

function render_stars($rating) {
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rating) ? "★" : "☆";
    }
    return $stars;
}

$page_title = $product['name'];
$path = "../../";
$extra_css = "product_detail.css"; 

require $path . 'includes/header_member.php';
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

<div class="product-container">
    <div class="product-gallery">
        <div class="slider-container" id="sliderContainer">
            <div class="slider-wrapper" id="sliderWrapper">
                <?php foreach ($all_images as $img): ?>
                    <div class="slide">
                        <img src="../../images/products/<?php echo $img; ?>" alt="Product Image">
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($all_images) > 1): ?>
                <button class="prev-btn" id="prevBtn">&#10094;</button>
                <button class="next-btn" id="nextBtn">&#10095;</button>
                
                <div class="slider-dots">
                    <?php foreach ($all_images as $index => $img): ?>
                        <span class="dot <?php echo ($index === 0) ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="product-info">
        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div class="product-meta">
            Category: <strong><?php echo htmlspecialchars($product['category_name']); ?></strong>
        </div>

        <div style="margin-bottom:15px; color:#f1c40f; font-size:1.1em;">
            <?php echo render_stars(round($avg_rating)); ?>
            <span style="color:#555; font-size:0.9em; margin-left:5px;">(<?php echo $total_reviews; ?> reviews)</span>
        </div>

        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>

        <div style="margin-bottom: 25px; line-height: 1.6; color: #666;">
            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
        </div>

        <?php if ($product['stock'] > 0): ?>
            <form action="../../controllers/cart_controller.php" method="POST" id="addToCartForm">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                
                <div style="margin-bottom: 20px;">
                    <label style="margin-right:10px;">Quantity:</label>
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 70px; padding: 8px; border:1px solid #ccc; border-radius:4px;">
                    <span style="color:#999; font-size:0.9em; margin-left:10px;"><?php echo $product['stock']; ?> pieces available</span>
                </div>

                <div style="display:flex; align-items:center;">
                    <button type="submit" class="btn-action btn-cart">Add to Cart</button>
                    <button type="submit" name="buy_now" value="1" class="btn-action btn-buy">Buy Now</button>
                </div>
            </form>
            
            <?php if (is_logged_in()): ?>
                <form action="../../controllers/wishlist_controller.php" method="POST" style="margin-top:10px;">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    <input type="hidden" name="from" value="detail">
                    <?php if ($is_in_wishlist): ?>
                        <input type="hidden" name="action" value="remove">
                        <button type="submit" style="background:none; border:none; color:#ee4d2d; cursor:pointer; text-decoration:underline;">Remove from Wishlist</button>
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                        <button type="submit" style="background:none; border:none; color:#555; cursor:pointer; text-decoration:underline;">Add to Wishlist</button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

        <?php else: ?>
            <div style="padding:15px; background:#f8d7da; color:#721c24; border-radius:4px;">
                Out of Stock
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
if (!empty($product['youtube_video_id'])) {
    $stmt_v = $pdo->prepare("SELECT video_id FROM youtube_videos WHERE id = ?");
    $stmt_v->execute([$product['youtube_video_id']]);
    $video = $stmt_v->fetch();

    if ($video) {
?>
    <div class="video-section" style="max-width:1200px; margin: 0 auto 40px auto; padding: 0 20px;">
        <h3 style="border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Product Video</h3>
        <div class="video-container" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; background: #000; border-radius:8px;">
            <iframe 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video['video_id']); ?>?controls=1" 
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

<div class="reviews-container" style="max-width:1200px; margin:0 auto; padding:0 20px 50px 20px;">
    <h3 style="border-bottom:1px solid #eee; padding-bottom:10px;">Product Reviews</h3>
    <?php if ($total_reviews > 0): ?>
        <?php foreach ($reviews as $r): ?>
            <div class="review-item" style="border-bottom:1px solid #f0f0f0; padding:15px 0; display:flex; gap:15px;">
                <div class="review-avatar">
                    <img src="../../images/uploads/<?php echo $r['profile_photo']; ?>" style="width:50px; height:50px; border-radius:50%; object-fit:cover;">
                </div>
                <div class="review-content" style="flex:1;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                        <strong><?php echo htmlspecialchars($r['full_name']); ?></strong>
                        <span style="color:#999; font-size:0.9em;"><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
                    </div>
                    <div style="color:#f1c40f; margin-bottom:5px;"><?php echo render_stars($r['rating']); ?></div>
                    <p style="margin:0; color:#444;"><?php echo nl2br(htmlspecialchars($r['comment'])); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#777;">No reviews yet.</p>
    <?php endif; ?>
</div>

<script>
    // --- 图片轮播逻辑 (Vanilla JS) ---
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.getElementById('sliderWrapper');
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const sliderContainer = document.getElementById('sliderContainer');
        
        let currentIndex = 0;
        const totalSlides = slides.length;

        // 如果只有一张图，不需要轮播逻辑
        if (totalSlides <= 1) return;

        function updateSlider() {
            // 移动 wrapper
            wrapper.style.transform = `translateX(-${currentIndex * 100}%)`;
            
            // 更新 dots
            dots.forEach(dot => dot.classList.remove('active'));
            if(dots[currentIndex]) {
                dots[currentIndex].classList.add('active');
            }
        }

        // 下一张
        function nextSlide() {
            currentIndex = (currentIndex + 1) % totalSlides;
            updateSlider();
        }

        // 上一张
        function prevSlide() {
            currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
            updateSlider();
        }

        // --- [新增] 自动播放逻辑 (2秒一次) ---
        let autoSlideInterval;
        const autoDelay = 2000; // 2000毫秒 = 2秒

        function startAutoSlide() {
            // 防止重复启动
            stopAutoSlide();
            autoSlideInterval = setInterval(nextSlide, autoDelay);
        }

        function stopAutoSlide() {
            clearInterval(autoSlideInterval);
        }

        // 页面加载后启动
        startAutoSlide();

        // 鼠标悬停时暂停，移开后继续 (提升体验)
        sliderContainer.addEventListener('mouseenter', stopAutoSlide);
        sliderContainer.addEventListener('mouseleave', startAutoSlide);

        // 按钮事件 (点击后重置计时)
        if(nextBtn) nextBtn.addEventListener('click', () => {
            nextSlide();
            stopAutoSlide();
            startAutoSlide();
        });
        
        if(prevBtn) prevBtn.addEventListener('click', () => {
            prevSlide();
            stopAutoSlide();
            startAutoSlide();
        });

        // Dot 点击事件
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                currentIndex = parseInt(this.getAttribute('data-index'));
                updateSlider();
                stopAutoSlide();
                startAutoSlide();
            });
        });

        // 触摸滑动支持
        let touchStartX = 0;
        let touchEndX = 0;

        sliderContainer.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoSlide(); // 触摸时暂停
        });

        sliderContainer.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
            startAutoSlide(); // 触摸后恢复
        });

        function handleSwipe() {
            if (touchEndX < touchStartX - 50) {
                nextSlide(); 
            }
            if (touchEndX > touchStartX + 50) {
                prevSlide(); 
            }
        }
    });
</script>

<?php require $path . 'includes/footer.php'; ?>