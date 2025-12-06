<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

// 强制登录检查
if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 获取购物车所有商品
$sql = "SELECT c.quantity as cart_qty, p.* FROM cart c 
        JOIN products p ON c.product_id = p.product_id 
        WHERE c.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$cart_items = $stmt->fetchAll();

$page_title = "My Shopping Cart";
$path = "../../";
$extra_css = "shop.css";

require $path . 'includes/header.php';
?>

<h2>Your Shopping Cart</h2>

<?php if (empty($cart_items)): ?>
    <p>Your cart is empty. <a href="home.php">Go shop now!</a></p>
<?php else: ?>

    <table class="table-list" id="cart-table">
        <thead>
            <tr>
                <th style="width: 50px; text-align: center;">
                    <input type="checkbox" id="select-all">
                </th>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item):
                $subtotal = $item['price'] * $item['cart_qty'];
            ?>
                <tr class="cart-item-row">
                    <td style="text-align: center;">
                        <input type="checkbox" class="item-checkbox" value="<?php echo $item['product_id']; ?>" data-subtotal="<?php echo $subtotal; ?>">
                    </td>
                    <td>
                        <img src="../../images/products/<?php echo $item['image_path']; ?>" class="img-small" style="width:50px;">
                        <?php echo $item['name']; ?>
                    </td>
                    <td>$<?php echo $item['price']; ?></td>
                    <td>
                        <form action="../../controllers/cart_controller.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['cart_qty']; ?>" min="1" style="width: 50px; padding:5px;">
                            <button type="submit" class="btn-blue" style="padding:5px 10px; font-size:0.8em;">Update</button>
                        </form>
                    </td>
                    <td class="row-subtotal">$<?php echo number_format($subtotal, 2); ?></td>
                    <td>
                        <form action="../../controllers/cart_controller.php" method="POST">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <button type="submit" class="btn-red">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="cart-total">
        Total Selected: $<span id="display-total">0.00</span>
    </div>

    <div style="text-align: right; margin-top: 20px;">
        <form action="../../controllers/order_controller.php" method="POST" id="checkout-form">
            <input type="hidden" name="action" value="checkout">
            <button type="submit" class="btn-green" id="btn-checkout" disabled style="opacity: 0.5; cursor: not-allowed;">
                Checkout Selected Items
            </button>
        </form>
    </div>

    <script>
        $(document).ready(function() {

            // 1. 计算总价函数
            function calculateTotal() {
                let total = 0;
                let count = 0;

                $('.item-checkbox:checked').each(function() {
                    // 读取 checkbox 上的 data-subtotal 属性
                    total += parseFloat($(this).data('subtotal'));
                    count++;
                });

                // 更新页面显示
                $('#display-total').text(total.toFixed(2));

                // 控制结账按钮状态 (没选东西时不能点)
                if (count > 0) {
                    $('#btn-checkout').prop('disabled', false).css('opacity', '1').css('cursor', 'pointer');
                } else {
                    $('#btn-checkout').prop('disabled', true).css('opacity', '0.5').css('cursor', 'not-allowed');
                }
            }

            // 2. 监听复选框变化
            $('.item-checkbox').change(function() {
                calculateTotal();

                // 如果有个没选，全选框就取消勾选
                if (!$(this).prop('checked')) {
                    $('#select-all').prop('checked', false);
                }
            });

            // 3. 全选/全不选功能
            $('#select-all').change(function() {
                let isChecked = $(this).prop('checked');
                $('.item-checkbox').prop('checked', isChecked);
                calculateTotal();
            });

            // 4. 提交表单时的拦截处理
            $('#checkout-form').submit(function(e) {
                // 获取所有选中的 ID
                let selectedIds = [];
                $('.item-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });

                if (selectedIds.length === 0) {
                    alert("Please select at least one item to checkout.");
                    e.preventDefault(); // 阻止提交
                    return;
                }

                // 动态创建 hidden input 插入到表单里
                // 格式: <input type="hidden" name="selected_items[]" value="1">
                selectedIds.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'selected_items[]',
                        value: id
                    }).appendTo('#checkout-form');
                });

                // 现在让表单正常提交，selected_items[] 数组会被带过去
            });
        });
    </script>

<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>