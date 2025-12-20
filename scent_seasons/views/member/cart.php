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

$stmt_addr = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ?");
$stmt_addr->execute([$user_id]);
$my_addresses = $stmt_addr->fetchAll();

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
                        <input type="checkbox" class="item-checkbox" value="<?php echo $item['product_id']; ?>"
                            data-subtotal="<?php echo $subtotal; ?>">
                    </td>
                    <td>
                        <img src="../../images/products/<?php echo $item['image_path']; ?>" class="img-small"
                            style="width:50px;">
                        <?php echo $item['name']; ?>
                    </td>
                    <td>$<?php echo $item['price']; ?></td>
                    <td>
                        <form action="../../controllers/cart_controller.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="number" name="quantity" value="<?php echo $item['cart_qty']; ?>" min="1"
                                style="width: 50px; padding:5px;">
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

        <?php if (!empty($my_addresses)): ?>
            <div class="saved-addresses" style="margin-bottom: 15px;">
                <p><strong>Select a saved address:</strong></p>
                <?php foreach ($my_addresses as $addr): ?>
                    <label style="display: block; margin-bottom: 8px; cursor:pointer;">
                        <input type="radio" name="address_option" class="addr-radio"
                            value="<?php echo htmlspecialchars($addr['address_text']); ?>">
                        <?php echo htmlspecialchars($addr['address_text']); ?>
                    </label>
                <?php endforeach; ?>
                <label style="display: block; cursor:pointer;">
                    <input type="radio" name="address_option" class="addr-radio" value="new" id="use-new-address">
                    <em>-- Use a new address --</em>
                </label>
            </div>
        <?php endif; ?>

        <div id="new-address-input" style="<?php echo !empty($my_addresses) ? 'display:none;' : ''; ?>">
            <p class="text-muted" style="font-size: 14px; margin-bottom: 10px;">Please enter your full delivery address.</p>
            <textarea id="shipping-address" rows="3" placeholder="Street address, City, State, Zip Code..."
                style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #d2d2d7;"></textarea>
        </div>
    </div>

    <div class="cart-total">
        Total Selected: $<span id="display-total">0.00</span>
    </div>

    <div style="text-align: right; margin-top: 30px; display: flex; justify-content: flex-end;">
        <div id="paypal-button-container" style="width: 300px;"></div>
    </div>

    <script
        src="https://www.paypal.com/sdk/js?client-id=Ab91QiHAZkGW1YVrL_60iEZvAraUdaF-BCUFbrxdRw6zmaI3wZP0XlwZAoUQHe0FIE5cuYUZe4X4I0M6&currency=USD"></script>

    <script>
        $(document).ready(function () {

            // 1. 计算总价函数
            function calculateTotal() {
                let total = 0;
                $('.item-checkbox:checked').each(function () {
                    total += parseFloat($(this).data('subtotal'));
                });
                $('#display-total').text(total.toFixed(2));
                return total;
            }

            // 监听复选框变化
            $('.item-checkbox, #select-all').change(function () {
                if (this.id === 'select-all') {
                    $('.item-checkbox').prop('checked', $(this).prop('checked'));
                } else if (!$(this).prop('checked')) {
                    $('#select-all').prop('checked', false);
                }
                calculateTotal();
            });

            // 监听地址单选框切换 (如果你已经按照之前的建议添加了 radio)
            $(document).on('change', '.addr-radio', function () {
                if ($(this).val() === 'new') {
                    $('#new-address-input').slideDown();
                } else {
                    $('#new-address-input').slideUp();
                }
            });

            // 2. 初始化 PayPal 按钮
            paypal.Buttons({
                onInit: function (data, actions) {
                    // 初始检查：如果购物车已有选中的商品，直接启用
                    if (calculateTotal() > 0) {
                        actions.enable();
                    } else {
                        actions.disable();
                    }

                    // 监听勾选框实时切换按钮状态
                    $('.item-checkbox, #select-all').change(function () {
                        if (calculateTotal() > 0) {
                            actions.enable();
                        } else {
                            actions.disable();
                        }
                    });
                },

                onClick: function (data, actions) {
                    let address = '';

                    // 逻辑：优先判断是否有选中的保存地址
                    const savedAddr = $('input[name="address_option"]:checked');

                    if (savedAddr.length > 0) {
                        if (savedAddr.val() === 'new') {
                            address = $('#shipping-address').val().trim();
                        } else {
                            address = savedAddr.val().trim();
                        }
                    } else {
                        // 如果没有 radio (旧版本)，直接读 textarea
                        address = $('#shipping-address').val().trim();
                    }

                    if (address.length === 0) {
                        alert("Please select or enter a shipping address before proceeding.");
                        return actions.reject();
                    }

                    // 存入全局变量供 onApprove 使用
                    window.finalAddress = address;
                },

                createOrder: function (data, actions) {
                    let amount = calculateTotal().toFixed(2);
                    return actions.order.create({
                        purchase_units: [{
                            amount: { value: amount }
                        }]
                    });
                },

                onApprove: function (data, actions) {
                    return actions.order.capture().then(function (details) {
                        let selectedIds = [];
                        $('.item-checkbox:checked').each(function () {
                            selectedIds.push($(this).val());
                        });

                        fetch('../../controllers/order_controller.php?action=checkout', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                selected_items: selectedIds,
                                transaction_id: details.id,
                                address: window.finalAddress // 使用确定的地址
                            })
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.href = "../member/orders.php?msg=success";
                                } else {
                                    alert("Order failed: " + data.message);
                                }
                            });
                    });
                }
            }).render('#paypal-button-container');
        });
    </script>

<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>