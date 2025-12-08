<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

// Get popular products based on total quantity sold
$check_orders_sql = "SELECT COUNT(*) as count FROM order_items";
$stmt = $pdo->query($check_orders_sql);
$has_orders = $stmt->fetch()['count'] > 0;

if ($has_orders) {
    $popular_sql = "SELECT p.product_id, p.name, p.price, p.image_path, p.description, p.category_id, p.stock, 
                            COALESCE(SUM(oi.quantity), 0) as total_sold 
                     FROM products p 
                     LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                     WHERE p.is_deleted = 0 
                     GROUP BY p.product_id, p.name, p.price, p.image_path, p.description, p.category_id, p.stock
                     ORDER BY total_sold DESC, p.product_id ASC 
                     LIMIT 4";
    try {
        $stmt = $pdo->query($popular_sql);
        $hot_products = $stmt->fetchAll();
    } catch (Exception $e) {
        $hot_products = [];
    }
} else {
    $hot_products = [];
}

if (empty($hot_products)) {
    $fallback_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->query($fallback_sql);
    $hot_products = $stmt->fetchAll();
}

// Get personalized recommendations
$recommended_products = [];
$user_id = $_SESSION['user_id'];

$check_orders_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
$stmt = $pdo->prepare($check_orders_sql);
$stmt->execute([$user_id]);
$order_check = $stmt->fetch();
$has_order_history = $order_check['order_count'] > 0;

if ($has_order_history) {
    $recommended_sql = "SELECT DISTINCT p.* 
                        FROM products p 
                        WHERE p.is_deleted = 0 
                        AND p.category_id IN (
                            SELECT DISTINCT p2.category_id 
                            FROM order_items oi 
                            JOIN orders o ON oi.order_id = o.order_id 
                            JOIN products p2 ON oi.product_id = p2.product_id 
                            WHERE o.user_id = ? 
                            AND p2.category_id IS NOT NULL
                        )
                        AND p.product_id NOT IN (
                            SELECT DISTINCT oi.product_id 
                            FROM order_items oi 
                            JOIN orders o ON oi.order_id = o.order_id 
                            WHERE o.user_id = ?
                        )
                        ORDER BY RAND() 
                        LIMIT 4";
    $stmt = $pdo->prepare($recommended_sql);
    $stmt->execute([$user_id, $user_id]);
    $recommended_products = $stmt->fetchAll();
    
    if (empty($recommended_products)) {
        $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
        $stmt = $pdo->query($random_sql);
        $recommended_products = $stmt->fetchAll();
    }
} else {
    $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->query($random_sql);
    $recommended_products = $stmt->fetchAll();
}

$page_title = "Welcome - Scent Seasons";
$path = "../../";
$extra_css = "home.css"; // 改成 home.css

require $path . 'includes/header.php';
?>

<!-- Hero Section 轮播图 - 纯图片展示 -->
<div class="hero-section">
    <div class="hero-slideshow">
        <!-- Slide 1 -->
        <div class="hero-slide active">
            <img src="../../images/products/jennie.png" alt="Banner 1" class="hero-image">
        </div>
        
        <!-- Slide 2 -->
        <div class="hero-slide">
            <img src="../../images/products/jennie1.png" alt="Banner 2" class="hero-image">
        </div>
        
        <!-- Slide 3 -->
        <div class="hero-slide">
            <img src="../../images/banner/banner3.jpg" alt="Banner 3" class="hero-image">
        </div>
        
        <!-- Slide 4 -->
        <div class="hero-slide">
            <img src="../../images/banner/banner4.jpg" alt="Banner 4" class="hero-image">
        </div>
        
        <!-- Slide 5 -->
        <div class="hero-slide">
            <img src="../../images/banner/banner5.jpg" alt="Banner 5" class="hero-image">
        </div>
    </div>
    
    <!-- Navigation Dots -->
    <div class="hero-dots">
        <span class="dot active" data-slide="0"></span>
        <span class="dot" data-slide="1"></span>
        <span class="dot" data-slide="2"></span>
        <span class="dot" data-slide="3"></span>
        <span class="dot" data-slide="4"></span>
    </div>
    
    <!-- Navigation Arrows -->
    <button class="hero-arrow hero-arrow-left" aria-label="Previous slide">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
        </svg>
    </button>
    <button class="hero-arrow hero-arrow-right" aria-label="Next slide">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
        </svg>
    </button>
</div>

<!-- Shop Now Button - 放在轮播图下面 -->
<div class="shop-now-section">
    <a href="shop.php" class="shop-now-btn">Shop Now</a>
</div>

<!-- Popular Items Section -->
<div class="container" style="margin-top: 60px;">
    <h2 class="section-title">Popular Items</h2>

    <?php if (empty($hot_products)): ?>
        <p style="text-align: center; color: #86868b;">No products available at the moment.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($hot_products as $p): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" class="product-image-link">
                        <div class="product-image-wrapper">
                            <img src="../../images/products/<?php echo htmlspecialchars($p['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($p['name']); ?>"
                                 onerror="this.src='../../images/products/default_product.jpg'">
                            <div class="product-overlay">
                                <span class="quick-view">Quick View</span>
                            </div>
                        </div>
                    </a>
                    <div class="p-info">
                        <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                        <p class="p-price">RM <?php echo number_format($p['price'], 2); ?></p>
                        <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" class="btn-add">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Recommended for You Section -->
<div class="container" style="margin-top: 60px;">
    <h2 class="section-title">Recommended for You</h2>

    <?php if (empty($recommended_products)): ?>
        <p style="text-align: center; color: #86868b;">No recommendations available at the moment.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($recommended_products as $product): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" class="product-image-link">
                        <div class="product-image-wrapper">
                            <img src="../../images/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='../../images/products/default_product.jpg'">
                            <div class="product-overlay">
                                <span class="quick-view">Quick View</span>
                            </div>
                        </div>
                    </a>
                    <div class="p-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="p-price">RM <?php echo number_format($product['price'], 2); ?></p>
                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                           class="btn-add">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- View All Button -->
<div class="container" style="text-align:center; margin-top:40px; margin-bottom: 60px;">
    <a href="shop.php" class="btn-blue" style="padding: 14px 40px; font-size: 1.1em;">View All Products</a>
</div>

<!-- Hero Slider JavaScript -->
<script src="../../js/hero-slider.js"></script>

<?php require $path . 'includes/footer.php'; ?>