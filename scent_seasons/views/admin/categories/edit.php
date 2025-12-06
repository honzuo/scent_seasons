<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    die("Category not found");
}

$page_title = "Edit Category";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="container" style="max-width: 600px;">
    <h2>Edit Category</h2>

    <form action="../../../controllers/category_controller.php" method="POST">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">

        <div class="form-group">
            <label>Category Name:</label>
            <input type="text" name="category_name" value="<?php echo $category['category_name']; ?>" required>
        </div>

        <br>
        <button type="submit" class="btn-blue">Update Category</button>
        <a href="index.php" class="btn-blue" style="background:gray;">Cancel</a>
    </form>
</div>

<?php require $path . 'includes/footer.php'; ?>