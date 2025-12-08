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

    <div style="margin-top: 30px; background: #fafafa; padding: 20px; border-radius: 12px; border: 1px solid #eee;">
        <h3 style="margin-top: 0; font-size: 18px;">Shipping Address</h3>
        <p class="text-muted" style="font-size: 14px; margin-bottom: 10px;">Please enter your full delivery address.</p>
        <div class="form-group">
            <textarea id="shipping-address" rows="3" placeholder="Street address, City, State, Zip Code..." style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #d2d2d7; font-family: inherit; font-size: 14px; resize: vertical;"></textarea>
        </div>
    </div>

    <div class="cart-total">
        Total Selected: $<span id="display-total">0.00</span>
    </div>

    <div style="text-align: right; margin-top: 30px; display: flex; justify-content: flex-end;">
        <div id="paypal-button-container" style="width: 300px;"></div>
    </div>

    <script src="https://www.paypal.com/sdk/js?client-id=Ab91QiHAZkGW1YVrL_60iEZvAraUdaF-BCUFbrxdRw6zmaI3wZP0XlwZAoUQHe0FIE5cuYUZe4X4I0M6&currency=USD"></script>

    <script>
        $(document).ready(function() {

            // 1. 计算总价函数
            function calculateTotal() {
                let total = 0;
                let count = 0;
                $('.item-checkbox:checked').each(function() {
                    total += parseFloat($(this).data('subtotal'));
                    count++;
                });
                $('#display-total').text(total.toFixed(2));
                return total;
            }

            // 监听复选框变化
            $('.item-checkbox, #select-all').change(function() {
                if (this.id === 'select-all') {
                    $('.item-checkbox').prop('checked', $(this).prop('checked'));
                } else if (!$(this).prop('checked')) {
                    $('#select-all').prop('checked', false);
                }
                calculateTotal();
            });

            // 2. 初始化 PayPal 按钮
            paypal.Buttons({
                // 初始化：只有金额 > 0 时才启用按钮
                onInit: function(data, actions) {
                    actions.disable();
                    $('.item-checkbox, #select-all').change(function() {
                        if (calculateTotal() > 0) {
                            actions.enable();
                        } else {
                            actions.disable();
                        }
                    });
                },

                // [关键修改] 点击按钮时触发：检查地址是否已填
                onClick: function(data, actions) {
                    let address = $('#shipping-address').val().trim();
                    if (address.length === 0) {
                        alert("Please enter your shipping address before proceeding.");
                        return actions.reject(); // 阻止 PayPal 弹窗
                    }
                },

                // 创建订单
                createOrder: function(data, actions) {
                    let amount = calculateTotal().toFixed(2);
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: amount
                            }
                        }]
                    });
                },

                // 付款成功
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        console.log('Transaction completed by ' + details.payer.name.given_name);

                        // 收集数据
                        let selectedIds = [];
                        $('.item-checkbox:checked').each(function() {
                            selectedIds.push($(this).val());
                        });

                        // [关键修改] 获取地址
                        let address = $('#shipping-address').val().trim();

                        // 发送给后端
                        fetch('../../controllers/order_controller.php?action=checkout', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    selected_items: selectedIds,
                                    transaction_id: details.id,
                                    address: address // 带上地址
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.href = "../member/orders.php?msg=success";
                                } else {
                                    alert("Payment successful, but failed to save order: " + data.message);
                                }
                            })
                            .catch((error) => {
                                console.error('Error:', error);
                                alert("System error processing order.");
                            });
                    });
                },

                onError: function(err) {
                    console.log(err);
                    alert("Something went wrong with PayPal.");
                }

            }).render('#paypal-button-container');
        });
    </script>

<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>