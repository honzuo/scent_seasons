<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

// 获取 4 个热门产品 (这里演示用随机推荐，你也可以改成按销量排序)
// ORDER BY RAND() LIMIT 4
$stmt = $pdo->query("SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4");
$hot_products = $stmt->fetchAll();

$page_title = "Welcome - Scent Seasons";
$path = "../../";
$extra_css = "shop.css";

require $path . 'includes/header.php';
?>

<div class="hero-banner">
    <div class="hero-content">
        <h1>Discover Your Signature Scent</h1>
        <p>Experience the essence of luxury with our exclusive collection.</p>
        <a href="shop.php" class="btn-hero">Shop Now</a>
    </div>
</div>

<div class="features-section">
    <div class="feature-item">
        <h3>100% Authentic</h3>
        <p>Guaranteed original fragrances.</p>
    </div>
    <div class="feature-item">
        <h3>Fast Shipping</h3>
        <p>Delivery within 3-5 days.</p>
    </div>
    <div class="feature-item">
        <h3>Secure Payment</h3>
        <p>100% secure checkout.</p>
    </div>
</div>

<div class="container" style="margin-top: 50px;">
    <h2 style="text-align:center; margin-bottom: 30px;">Trending Now</h2>

    <div class="product-grid" style="grid-template-columns: repeat(4, 1fr);">
        <?php foreach ($hot_products as $p): ?>
            <div class="product-card">
                <a href="product_detail.php?id=<?php echo $p['product_id']; ?>">
                    <img src="../../images/products/<?php echo $p['image_path']; ?>" alt="<?php echo $p['name']; ?>">
                </a>
                <div class="p-info">
                    <h4><?php echo $p['name']; ?></h4>
                    <p class="p-price">$<?php echo $p['price']; ?></p>
                    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" class="btn-add">View Details</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="text-align:center; margin-top:30px;">
        <a href="shop.php" class="btn-blue" style="padding: 12px 25px; font-size: 1.1em;">View All Products</a>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>