<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

$page_title = "Add Category";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="container" style="max-width: 600px;">
    <h2>Add New Category</h2>

    <form action="../../../controllers/category_controller.php" method="POST">
        <input type="hidden" name="action" value="add">

        <div class="form-group">
            <label>Category Name:</label>
            <input type="text" name="category_name" required placeholder="e.g., Floral, Woody">
        </div>

        <br>
        <button type="submit" class="btn-green">Save Category</button>
        <a href="index.php" class="btn-blue" style="background:gray;">Cancel</a>
    </form>
</div>

<?php require $path . 'includes/footer.php'; ?>