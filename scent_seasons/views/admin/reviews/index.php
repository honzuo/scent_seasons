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
    return "<span class='stars-gold'>$stars</span>";
}

$page_title = "Review Maintenance";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Review Maintenance</h2>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert <?php echo ($_GET['msg'] == 'deleted') ? 'alert-success' : 'alert-info'; ?>">
        <?php
        if ($_GET['msg'] == 'deleted') echo "Review deleted successfully.";
        elseif ($_GET['msg'] == 'replied') echo "Reply saved successfully.";
        ?>
    </div>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Product / User</th>
            <th>Rating & Comment</th>
            <th class="col-reply">Admin Reply</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reviews as $r): ?>
            <tr>
                <td class="align-top">#<?php echo $r['review_id']; ?></td>

                <td class="align-top">
                    <strong>Prod:</strong>
                    <a href="../../member/product_detail.php?id=<?php echo $r['product_id']; ?>" target="_blank" class="text-link-gray">
                        <?php echo $r['product_name']; ?>
                    </a><br>
                    <strong>User:</strong> <?php echo $r['full_name']; ?><br>
                    <small class="text-gray"><?php echo date('Y-m-d', strtotime($r['created_at'])); ?></small>
                </td>

                <td class="align-top">
                    <div><?php echo render_stars_admin($r['rating']); ?></div>
                    <div class="comment-italic">
                        "<?php echo nl2br(htmlspecialchars($r['comment'])); ?>"
                    </div>
                </td>

                <td class="align-top">
                    <form action="../../../controllers/review_controller.php" method="POST">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">

                        <textarea name="admin_reply" rows="3" class="reply-textarea" placeholder="Write your reply here..."><?php echo htmlspecialchars($r['admin_reply']); ?></textarea>

                        <div class="reply-actions">
                            <button type="submit" class="btn-blue btn-sm">Save Reply</button>
                        </div>
                    </form>
                    <?php if ($r['reply_at']): ?>
                        <div class="last-reply-info">
                            Last replied: <?php echo date('Y-m-d H:i', strtotime($r['reply_at'])); ?>
                        </div>
                    <?php endif; ?>
                </td>

                <td class="align-top">
                    <form action="../../../controllers/review_controller.php" method="POST" onsubmit="return confirm('Delete this review?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="review_id" value="<?php echo $r['review_id']; ?>">
                        <button type="submit" class="btn-red btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($reviews) == 0): ?>
    <p class="text-center text-gray mt-20">No reviews found.</p>
<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>