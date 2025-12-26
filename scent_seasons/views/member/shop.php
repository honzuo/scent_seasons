<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';


$cats = $pdo->query("SELECT * FROM categories")->fetchAll();


$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$cat_filter = isset($_GET['cat']) ? intval($_GET['cat']) : 0;
-
$limit = 12; 
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


$where_sql = "WHERE is_deleted = 0 AND name LIKE ?";
$params = ["%$search%"];

if ($cat_filter > 0) {
    $where_sql .= " AND category_id = ?";
    $params[] = $cat_filter;
}

$sql_count = "SELECT COUNT(*) FROM products $where_sql";
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);


$sql = "SELECT * FROM products $where_sql LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$page_title = "Shop - Scent Seasons";
$path = "../../";
$extra_css = "shop.css";

require $path . 'includes/header.php';
?>

<div style="margin: 20px 0; display: flex; justify-content: space-between; align-items: center;">
    <h2>Our Collection</h2>
    <form method="GET" style="display:flex;">
        <?php if ($cat_filter > 0): ?>
            <input type="hidden" name="cat" value="<?php echo $cat_filter; ?>">
        <?php endif; ?>

        <input type="text" name="search" placeholder="Search perfume..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<div class="shop-layout">
    <div class="sidebar">
        <h3>Categories</h3>
        <ul style="list-style:none; padding:0; display:block;">
            <li style="margin-bottom:10px;">
                <a href="shop.php" class="<?php echo ($cat_filter == 0) ? 'active-cat' : ''; ?>" style="color:#333;">All Scents</a>
            </li>
            <?php foreach ($cats as $c): ?>
                <li style="margin-bottom:10px;">
                    <a href="shop.php?cat=<?php echo $c['category_id']; ?>"
                        class="<?php echo ($cat_filter == $c['category_id']) ? 'active-cat' : ''; ?>"
                        style="color:#333;">
                        <?php echo $c['category_name']; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div style="flex: 1;">
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

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&cat=<?php echo $cat_filter; ?>" class="page-link">&laquo; Prev</a>
                <?php else: ?>
                    <span class="page-link disabled">&laquo; Prev</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&cat=<?php echo $cat_filter; ?>"
                        class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&cat=<?php echo $cat_filter; ?>" class="page-link">Next &raquo;</a>
                <?php else: ?>
                    <span class="page-link disabled">Next &raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require $path . 'includes/footer.php'; ?>