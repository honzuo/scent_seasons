<?php
// views/member/wishlist.php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$sql = "SELECT w.wishlist_id, w.added_at, p.* 
        FROM wishlist w 
        JOIN products p ON w.product_id = p.product_id 
        WHERE w.user_id = ? AND p.is_deleted = 0
        ORDER BY w.added_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$wishlist_items = $stmt->fetchAll();

$page_title = "My Wishlist";
$path = "../../";
$extra_css = "shop.css";

require $path . 'includes/header.php';
?>

<h2>My Wishlist</h2>
<p class="text-muted">Items you've saved for later</p>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'removed'): ?>
    <div class="alert alert-info">Item removed from your wishlist.</div>
<?php endif; ?>

<?php if (count($wishlist_items) > 0): ?>
    <div class="product-grid" style="margin-top: 32px;">
        <?php foreach ($wishlist_items as $item): ?>
            <div class="product-card wishlist-card">
                <form action="../../controllers/wishlist_controller.php" method="POST" class="wishlist-remove-form">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                    <input type="hidden" name="from" value="wishlist">
                    <button type="submit" class="btn-remove-wishlist" title="Remove from wishlist">×</button>
                </form>

                <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                    <img src="../../images/products/<?php echo $item['image_path']; ?>" alt="<?php echo $item['name']; ?>">
                </a>

                <div class="p-info">
                    <h4><?php echo $item['name']; ?></h4>
                    <p class="p-price">$<?php echo $item['price']; ?></p>

                    <?php if ($item['stock'] > 0): ?>
                        <span class="stock-badge stock-available">In Stock</span>
                    <?php else: ?>
                        <span class="stock-badge stock-out">Out of Stock</span>
                    <?php endif; ?>

                    <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" class="btn-add">
                        View Details
                    </a>
                </div>

                <div class="wishlist-date">
                    Added <?php echo date('M d, Y', strtotime($item['added_at'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-wishlist">
        <div class="empty-icon">♡</div>
        <h3>Your wishlist is empty</h3>
        <p>Save items you love for later!</p>
        <a href="shop.php" class="btn-blue" style="margin-top: 20px; padding: 12px 32px;">Browse Products</a>
    </div>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>