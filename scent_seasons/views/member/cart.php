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

    <!-- Promotion Code Section -->
    <div style="margin-top: 30px; padding: 20px; background: #f5f5f7; border-radius: 12px; max-width: 500px;">
        <h3 style="margin-bottom: 15px; font-size: 18px;">Promotion Code</h3>
        <div style="display: flex; gap: 10px; align-items: flex-start;">
            <div style="flex: 1;">
                <input type="text" id="promoCode" placeholder="Enter promotion code" 
                       style="width: 100%; padding: 10px; border: 1px solid #d2d2d7; border-radius: 8px; font-size: 14px;">
                <div id="promoMessage" style="margin-top: 8px; font-size: 13px;"></div>
            </div>
            <button id="applyPromo" class="btn-blue" style="padding: 10px 20px; white-space: nowrap;">Apply</button>
        </div>
    </div>

    <div class="cart-total" style="margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span>Subtotal:</span>
            <span>$<span id="display-subtotal">0.00</span></span>
        </div>
        <div id="discount-row" style="display: none; justify-content: space-between; margin-bottom: 10px; color: #30d158;">
            <span>Discount (<span id="discount-code-name"></span>):</span>
            <span>-$<span id="display-discount">0.00</span> <button id="removePromo" style="margin-left: 10px; padding: 2px 8px; font-size: 11px; background: #ff3b30; color: white; border: none; border-radius: 4px; cursor: pointer;">Remove</button></span>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 600; padding-top: 10px; border-top: 2px solid #e5e5e7;">
            <span>Total:</span>
            <span>$<span id="display-total">0.00</span></span>
        </div>
    </div>

    <div style="text-align: right; margin-top: 30px; display: flex; justify-content: flex-end;">
        <div id="paypal-button-container" style="width: 300px;"></div>
    </div>

    <script src="https://www.paypal.com/sdk/js?client-id=Ab91QiHAZkGW1YVrL_60iEZvAraUdaF-BCUFbrxdRw6zmaI3wZP0XlwZAoUQHe0FIE5cuYUZe4X4I0M6&currency=USD"></script>

    <script>
        $(document).ready(function() {
            let currentPromoCode = null;
            let discountAmount = 0;
            let discountInfo = null;

            // 1. 计算总价函数
            function calculateTotal() {
                let subtotal = 0;
                let count = 0;
                $('.item-checkbox:checked').each(function() {
                    subtotal += parseFloat($(this).data('subtotal'));
                    count++;
                });
                
                $('#display-subtotal').text(subtotal.toFixed(2));
                
                // Apply discount if promotion code is active
                let finalTotal = subtotal;
                if (currentPromoCode && discountInfo) {
                    discountAmount = parseFloat(discountInfo.discount);
                    finalTotal = subtotal - discountAmount;
                    if (finalTotal < 0) finalTotal = 0;
                    
                    $('#discount-row').css('display', 'flex');
                    $('#display-discount').text(discountAmount.toFixed(2));
                    $('#discount-code-name').text(currentPromoCode);
                } else {
                    $('#discount-row').hide();
                    discountAmount = 0;
                }
                
                $('#display-total').text(finalTotal.toFixed(2));
                return finalTotal;
            }

            // Apply promotion code
            $('#applyPromo').on('click', function() {
                const code = $('#promoCode').val().trim().toUpperCase();
                if (!code) {
                    $('#promoMessage').html('<span style="color: #ff3b30;">Please enter a promotion code</span>');
                    return;
                }
                
                const subtotal = parseFloat($('#display-subtotal').text()) || 0;
                if (subtotal <= 0) {
                    $('#promoMessage').html('<span style="color: #ff3b30;">Please select items first</span>');
                    return;
                }
                
                $.getJSON('../../controllers/promotion_controller.php', {
                    action: 'validate',
                    code: code,
                    total: subtotal
                }, function(res) {
                    if (res.status === 'success') {
                        currentPromoCode = code;
                        discountInfo = res;
                        $('#promoMessage').html('<span style="color: #30d158;">✓ Promotion code applied! Discount: $' + res.discount.toFixed(2) + '</span>');
                        $('#promoCode').prop('disabled', true);
                        $('#applyPromo').text('Applied').prop('disabled', true);
                        calculateTotal();
                    } else {
                        currentPromoCode = null;
                        discountInfo = null;
                        $('#promoMessage').html('<span style="color: #ff3b30;">' + res.message + '</span>');
                        calculateTotal();
                    }
                }).fail(function() {
                    $('#promoMessage').html('<span style="color: #ff3b30;">Error validating promotion code</span>');
                });
            });

            // Remove promotion code
            $(document).on('click', '#removePromo', function() {
                currentPromoCode = null;
                discountInfo = null;
                $('#promoCode').val('').prop('disabled', false);
                $('#applyPromo').text('Apply').prop('disabled', false);
                $('#promoMessage').html('');
                calculateTotal();
            });

            // 监听复选框变化
            $('.item-checkbox, #select-all').change(function() {
                // 如果是全选
                if (this.id === 'select-all') {
                    $('.item-checkbox').prop('checked', $(this).prop('checked'));
                } else if (!$(this).prop('checked')) {
                    $('#select-all').prop('checked', false);
                }
                calculateTotal();
            });

            // 2. 初始化 PayPal 按钮
            paypal.Buttons({
                // 只有当有商品被选中且金额 > 0 时，才允许点击
                onInit: function(data, actions) {
                    // 初始禁用，除非有选中
                    actions.disable();

                    // 监听 checkbox 变化来启用/禁用按钮
                    $('.item-checkbox, #select-all').change(function() {
                        if (calculateTotal() > 0) {
                            actions.enable();
                        } else {
                            actions.disable();
                        }
                    });
                },

                // 点击按钮时触发：告诉 PayPal 收多少钱
                createOrder: function(data, actions) {
                    let amount = calculateTotal().toFixed(2);
                    if (amount <= 0) {
                        alert("Please select items to checkout.");
                        return false;
                    }

                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: amount
                            }
                        }]
                    });
                },

                // 用户付款成功后触发
                onApprove: function(data, actions) {
                    // 1. 捕获资金（完成交易）
                    return actions.order.capture().then(function(details) {

                        console.log('Transaction completed by ' + details.payer.name.given_name);

                        // 2. 收集选中的商品 ID
                        let selectedIds = [];
                        $('.item-checkbox:checked').each(function() {
                            selectedIds.push($(this).val());
                        });

                        // 3. 用 AJAX 发送数据给 PHP 后端保存订单
                        fetch('../../controllers/order_controller.php?action=checkout', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    selected_items: selectedIds,
                                    transaction_id: details.id, // PayPal 的交易号
                                    promotion_code: currentPromoCode || null,
                                    discount_amount: discountAmount || 0
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // 成功！跳转到订单页
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

                // 用户取消或出错
                onError: function(err) {
                    console.log(err);
                    alert("Something went wrong with PayPal.");
                }

            }).render('#paypal-button-container');
        });
    </script>

<?php endif; ?>

<?php require $path . 'includes/footer.php'; ?>