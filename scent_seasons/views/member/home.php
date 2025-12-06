<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

// 检查是否登录 (如果不强制登录才能看产品，可以去掉这行，但根据作业通常会员系统是封闭的)
if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

// 获取分类用于侧边栏筛选 (Bonus)
$cats = $pdo->query("SELECT * FROM categories")->fetchAll();

// 获取产品 (支持搜索)
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$cat_filter = isset($_GET['cat']) ? intval($_GET['cat']) : 0;

$sql = "SELECT * FROM products WHERE name LIKE ?";
$params = ["%$search%"];

if ($cat_filter > 0) {
    $sql .= " AND category_id = ?";
    $params[] = $cat_filter;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$page_title = "Shop - Scent Seasons";
$path = "../../";
$extra_css = "shop.css"; // 引用 shop.css

require $path . 'includes/header.php';
?>

<div style="margin: 20px 0; display: flex; justify-content: space-between; align-items: center;">
    <h2>Our Collection</h2>
    <form method="GET" style="display:flex;">
        <input type="text" name="search" placeholder="Search perfume..." value="<?php echo $search; ?>">
        <button type="submit">Search</button>
    </form>
</div>

<div class="shop-layout">
    <div class="sidebar">
        <h3>Categories</h3>
        <ul style="list-style:none; padding:0; display:block;">
            <li style="margin-bottom:10px;"><a href="home.php" style="color:#333;">All Scents</a></li>
            <?php foreach ($cats as $c): ?>
                <li style="margin-bottom:10px;">
                    <a href="home.php?cat=<?php echo $c['category_id']; ?>" style="color:#333;">
                        <?php echo $c['category_name']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="product-grid">
        <?php if (count($products) > 0): ?>
            <?php foreach ($products as $p): ?>
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
        <?php else: ?>
            <p>No perfumes found.</p>
        <?php endif; ?>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>