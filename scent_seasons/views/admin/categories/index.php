<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 获取所有分类
$stmt = $pdo->query("SELECT * FROM categories ORDER BY category_id ASC");
$categories = $stmt->fetchAll();

$page_title = "Category Management";
$path = "../../../"; // 回退 3 层
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Category Management</h2>

<div style="display:flex; justify-content:space-between; margin-bottom: 20px;">
    <a href="create.php" class="btn-blue">+ Add New Category</a>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] == 'in_use'): ?>
    <p class="error-msg" style="background:#ffebee; padding:10px; border:1px solid red; color:red;">
        Error: Cannot delete this category because it contains products. Please delete the products first.
    </p>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Category Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td>#<?php echo $c['category_id']; ?></td>
                <td><?php echo $c['category_name']; ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $c['category_id']; ?>" class="btn-blue" style="font-size:0.8em;">Edit</a>
                    
                    <form action="../../../controllers/category_controller.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="category_id" value="<?php echo $c['category_id']; ?>">
                        <button type="submit" class="btn-red" style="font-size:0.8em;">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require $path . 'includes/footer.php'; ?>