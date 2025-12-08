<?php
// index.php - Homepage for all users
session_start();
require 'config/database.php';
require 'includes/functions.php';

// Row 1: Get popular items based on total quantity sold
// First check if there are any order_items, if not, just show random products
$check_orders_sql = "SELECT COUNT(*) as count FROM order_items";
$stmt = $pdo->query($check_orders_sql);
$has_orders = $stmt->fetch()['count'] > 0;

if ($has_orders) {
    // If there are orders, get popular items based on sales
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
        $popular_products = $stmt->fetchAll();
    } catch (Exception $e) {
        $popular_products = [];
    }
} else {
    $popular_products = [];
}

// Fallback: If no popular products found, show random products
if (empty($popular_products)) {
    $fallback_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->query($fallback_sql);
    $popular_products = $stmt->fetchAll();
}

// Row 2: Get personalized recommendations based on member's purchase habits
$recommended_products = [];
if (isset($_SESSION['user_id']) && is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    
    // First, check if user has any order history
    $check_orders_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
    $stmt = $pdo->prepare($check_orders_sql);
    $stmt->execute([$user_id]);
    $order_check = $stmt->fetch();
    $has_order_history = $order_check['order_count'] > 0;
    
    if ($has_order_history) {
        // User has order history: suggest products based on categories from their orders
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
        
        // If no recommendations found (e.g., user bought all products in those categories), show random products
        if (empty($recommended_products)) {
            $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
            $stmt = $pdo->query($random_sql);
            $recommended_products = $stmt->fetchAll();
        }
    } else {
        // User has no order history: suggest random products
        $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
        $stmt = $pdo->query($random_sql);
        $recommended_products = $stmt->fetchAll();
    }
} else {
    // For non-logged-in users, show popular items
    $recommended_products = $popular_products;
}

$page_title = "Scent Seasons - Discover Your Signature Scent";
$path = "./";
$extra_css = "shop.css";

require 'includes/header.php';
?>

<div class="hero-banner">
    <div class="hero-content">
        <h1>Discover Your Signature Scent</h1>
        <p>Experience the essence of luxury with our exclusive collection of premium fragrances.</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="views/member/shop.php" class="btn-hero">Shop Now</a>
        <?php else: ?>
            <a href="views/public/register.php" class="btn-hero">Get Started</a>
        <?php endif; ?>
    </div>
</div>

<div class="features-section">
    <div class="feature-item">
        <h3>100% Authentic</h3>
        <p>Guaranteed original fragrances from trusted suppliers.</p>
    </div>
    <div class="feature-item">
        <h3>Fast Shipping</h3>
        <p>Delivery within 3-5 business days worldwide.</p>
    </div>
    <div class="feature-item">
        <h3>Secure Payment</h3>
        <p>100% secure checkout with encrypted transactions.</p>
    </div>
</div>

<!-- Row 1: Popular Items -->
<div class="container" style="margin-top: 50px;">
    <h2 style="text-align:center; margin-bottom: 30px;">Popular Items</h2>

    <?php if (empty($popular_products)): ?>
        <p style="text-align: center; color: #86868b;">No products available at the moment.</p>
    <?php else: ?>
        <div class="product-grid" style="grid-template-columns: repeat(4, 1fr);">
            <?php foreach ($popular_products as $product): ?>
                <div class="product-card">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'views/member/product_detail.php?id=' . $product['product_id'] : 'views/public/login.php'; ?>">
                        <img src="images/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='images/products/default_product.jpg'">
                    </a>
                    <div class="p-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="p-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <a href="<?php echo isset($_SESSION['user_id']) ? 'views/member/product_detail.php?id=' . $product['product_id'] : 'views/public/login.php'; ?>" 
                           class="btn-add">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Row 2: Recommended for You (Based on Purchase Habits) -->
<div class="container" style="margin-top: 50px;">
    <h2 style="text-align:center; margin-bottom: 30px;">
        <?php if (isset($_SESSION['user_id'])): ?>
            Recommended for You
        <?php else: ?>
            Trending Now
        <?php endif; ?>
    </h2>

    <?php if (empty($recommended_products)): ?>
        <p style="text-align: center; color: #86868b;">No recommendations available at the moment.</p>
    <?php else: ?>
        <div class="product-grid" style="grid-template-columns: repeat(4, 1fr);">
            <?php foreach ($recommended_products as $product): ?>
                <div class="product-card">
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'views/member/product_detail.php?id=' . $product['product_id'] : 'views/public/login.php'; ?>">
                        <img src="images/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='images/products/default_product.jpg'">
                    </a>
                    <div class="p-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="p-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <a href="<?php echo isset($_SESSION['user_id']) ? 'views/member/product_detail.php?id=' . $product['product_id'] : 'views/public/login.php'; ?>" 
                           class="btn-add">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="container" style="text-align:center; margin-top:30px; margin-bottom: 50px;">
    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="views/member/shop.php" class="btn-blue" style="padding: 12px 25px; font-size: 1.1em;">View All Products</a>
    <?php else: ?>
        <a href="views/public/login.php" class="btn-blue" style="padding: 12px 25px; font-size: 1.1em;">Login to Shop</a>
    <?php endif; ?>
</div>

<?php require 'includes/footer.php'; ?>