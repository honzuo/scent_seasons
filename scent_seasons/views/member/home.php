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
?>

<!DOCTYPE html>
<html>

<head>
    <title>Scent Seasons - Shop</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        /* 简单的网格布局 CSS */
        .shop-layout {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .sidebar {
            width: 200px;
            padding: 10px;
            background: #fff;
            border-radius: 8px;
        }

        .product-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .p-info {
            padding: 15px;
            text-align: center;
        }

        .p-price {
            color: #e67e22;
            font-weight: bold;
            font-size: 1.1em;
        }

        .btn-add {
            display: block;
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 10px;
            text-decoration: none;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: #34495e;
        }
    </style>
</head>

<body>
    <nav>
        <a href="home.php" class="logo">Scent Seasons</a>
        <ul>
            <li><a href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>)</a></li>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="orders.php">My Orders</a></li>
            <li><a href="../../logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
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
    </div>
</body>

</html>