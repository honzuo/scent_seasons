<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 联表查询
$sql = "SELECT r.*, u.full_name, p.name as product_name 
        FROM reviews r 
        JOIN users u ON r.user_id = u.user_id 
        JOIN products p ON r.product_id = p.product_id 
        ORDER BY r.created_at DESC";
$reviews = $pdo->query($sql)->fetchAll();

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

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] == 'deleted'): ?>
        <p style="color:green; font-weight:bold;">Review deleted successfully.</p>
    <?php elseif ($_GET['msg'] == 'replied'): ?>
        <p style="color:blue; font-weight:bold;">Reply saved successfully.</p>
    <?php endif; ?>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Product / User</th>
            <th>Rating & Comment</th>
            <th style="width: 35%;">Admin Reply</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reviews as $r): ?>
            <tr>
                <td style="vertical-align:top;">#<?php echo $r['review_id']; ?></td>

                <td style="vertical-align:top;">
                    <strong>Prod:</strong>
                    <a href="../../member/product_detail.php?id=<?php echo $r['product_id']; ?>" target="_blank">
                        <?php echo $r['product_name']; ?>
                    </a><br>
                    <strong>User:</strong> <?php echo $r['full_name']; ?><br>
                    <small style="color:#777;"><?php echo date('Y-m-d', strtotime($r['created_at'])); ?></small>
                </td>

                <td style="vertical-align:top;">
                    <div><?php echo render_stars_admin($r['rating']); ?></div>
                    <div style="margin-top:5px; font-style:italic;">
                        "<?php echo nl2br(htmlspecialchars($r['comment'])); ?>"
                    </div>
                </td>

                <td style="vertical-align:top;">
                    <form action="../../../controllers/review_controller.php" method="POST">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">

                        <textarea name="admin_reply" rows="3" style="width:100%; padding:5px; border:1px solid #ddd; border-radius:4px; font-size:0.9em;" placeholder="Write your reply here..."><?php echo htmlspecialchars($r['admin_reply']); ?></textarea>

                        <div style="margin-top:5px; text-align:right;">
                            <button type="submit" class="btn-blue" style="padding:4px 8px; font-size:0.8em;">Save Reply</button>
                        </div>
                    </form>
                    <?php if ($r['reply_at']): ?>
                        <div style="font-size:0.8em; color:#999; margin-top:3px;">
                            Last replied: <?php echo date('Y-m-d H:i', strtotime($r['reply_at'])); ?>
                        </div>
                    <?php endif; ?>
                </td>

                <td style="vertical-align:top;">
                    <form action="../../../controllers/review_controller.php" method="POST" onsubmit="return confirm('Delete this review?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">
                        <button type="submit" class="btn-red" style="padding:5px 10px; font-size:0.8em;">Delete</button>
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