<?php
session_start();
require '../../config/database.php';

$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$products_in_cart = [];
$total_price = 0;

if (!empty($cart_items)) {
    // 根据 ID 获取所有购物车里的商品详情
    // 这里用了 implode 将数组变成 string (e.g., "1,3,5") 用于 SQL IN 查询
    $ids = implode(',', array_keys($cart_items));
    $stmt = $pdo->query("SELECT * FROM products WHERE product_id IN ($ids)");
    $products_in_cart = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>My Shopping Cart</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .cart-table th,
        .cart-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .cart-total {
            text-align: right;
            font-size: 1.5em;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <nav>
        <a href="home.php" class="logo">Scent Seasons</a>
        <ul>
            <li><a href="home.php">Continue Shopping</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Your Shopping Cart</h2>

        <?php if (empty($products_in_cart)): ?>
            <p>Your cart is empty. <a href="home.php">Go shop now!</a></p>
        <?php else: ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products_in_cart as $p):
                        $qty = $cart_items[$p['product_id']];
                        $subtotal = $p['price'] * $qty;
                        $total_price += $subtotal;
                    ?>
                        <tr>
                            <td>
                                <img src="../../images/products/<?php echo $p['image_path']; ?>" style="width: 50px; vertical-align: middle; margin-right: 10px;">
                                <?php echo $p['name']; ?>
                            </td>
                            <td>$<?php echo $p['price']; ?></td>
                            <td>
                                <form action="../../controllers/cart_controller.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $qty; ?>" min="1" style="width: 50px;">
                                    <button type="submit" style="font-size:0.8em;">Update</button>
                                </form>
                            </td>
                            <td>$<?php echo number_format($subtotal, 2); ?></td>
                            <td>
                                <form action="../../controllers/cart_controller.php" method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?php echo $p['product_id']; ?>">
                                    <button type="submit" style="background:red; color:white; border:none; padding:5px 10px; cursor:pointer;">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-total">
                Total: $<?php echo number_format($total_price, 2); ?>
            </div>

            <div style="text-align: right; margin-top: 20px;">
                <form action="../../controllers/order_controller.php" method="POST">
                    <input type="hidden" name="action" value="checkout">
                    <input type="hidden" name="total" value="<?php echo $total_price; ?>">
                    <button type="submit" style="background: #27ae60; color: white; padding: 15px 30px; border: none; font-size: 1.2em; cursor: pointer;">
                        Checkout & Place Order
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>