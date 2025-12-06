<?php
session_start();
require '../../config/database.php';

if (!isset($_GET['id'])) { header("Location: home.php"); exit(); }
$id = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) { die("Product not found"); }
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo $product['name']; ?></title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <nav>
        <a href="home.php" class="logo">Scent Seasons</a>
        <ul>
            <li><a href="home.php">Back to Shop</a></li>
            <li><a href="cart.php">Cart (<?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?>)</a></li>
        </ul>
    </nav>

    <div class="container" style="margin-top: 50px;">
        <div style="display: flex; gap: 40px; background: white; padding: 40px; border-radius: 8px;">
            <div style="flex: 1;">
                <img src="../../images/products/<?php echo $product['image_path']; ?>" style="width: 100%; border-radius: 8px;">
            </div>
            
            <div style="flex: 1;">
                <h1 style="margin-top:0;"><?php echo $product['name']; ?></h1>
                <p style="color: gray;"><?php echo $product['category_name']; ?></p>
                <h2 style="color: #e67e22;">$<?php echo $product['price']; ?></h2>
                <p><?php echo nl2br($product['description']); ?></p>
                
                <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
                
                <form action="../../controllers/cart_controller.php" method="POST">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    
                    <label>Quantity:</label>
                    <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 60px; padding: 5px;">
                    
                    <br><br>
                    <?php if($product['stock'] > 0): ?>
                        <button type="submit" style="background: #27ae60; color: white; padding: 15px 30px; border: none; font-size: 1.1em; cursor: pointer;">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button disabled style="background: gray; color: white; padding: 15px 30px; border: none;">Out of Stock</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>