<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 联表查询：获取评价 + 用户名 + 商品名
$sql = "SELECT r.*, u.full_name, p.name as product_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.user_id 
        JOIN products p ON r.product_id = p.product_id 
        ORDER BY r.created_at DESC";
$reviews = $pdo->query($sql)->fetchAll();

// 辅助函数：显示星星
function render_stars_admin($rating)
{
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        $stars .= ($i <= $rating) ? "★" : "☆";
    }
    return "<span style='color:#f1c40f;'>$stars</span>";
}

$page_title = "Review Maintenance";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Review Maintenance</h2>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <p style="color:green; font-weight:bold;">Review deleted successfully.</p>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Product</th>
            <th>User</th>
            <th>Rating</th>
            <th style="width: 40%;">Comment</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reviews as $r): ?>
            <tr>
                <td>#<?php echo $r['review_id']; ?></td>
                <td>
                    <a href="../../member/product_detail.php?id=<?php echo $r['product_id']; ?>" target="_blank" style="color:blue;">
                        <?php echo $r['product_name']; ?>
                    </a>
                </td>
                <td><?php echo $r['full_name']; ?></td>
                <td><?php echo render_stars_admin($r['rating']); ?></td>
                <td><?php echo nl2br(htmlspecialchars($r['comment'])); ?></td>
                <td style="font-size:0.9em; color:#777;">
                    <?php echo date('Y-m-d', strtotime($r['created_at'])); ?>
                </td>
                <td>
                    <form action="../../../controllers/review_controller.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this review?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">
                        <button type="submit" class="btn-red" style="font-size:0.8em; padding:5px 10px;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($reviews) == 0): ?>
    <p style="text-align: center; color: gray; margin-top: 20px;">No reviews found.</p>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>