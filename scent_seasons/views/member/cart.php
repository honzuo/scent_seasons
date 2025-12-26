<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

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
$extra_css = "cart.css";

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
            <span>-$<span id="display-discount">0.00</span> <button id="removePromo"
                    style="margin-left: 10px; padding: 2px 8px; font-size: 11px; background: #ff3b30; color: white; border: none; border-radius: 4px; cursor: pointer;">Remove</button></span>
        </div>
        <div
            style="display: flex; justify-content: space-between; font-size: 20px; font-weight: 600; padding-top: 10px; border-top: 2px solid #e5e5e7;">
            <span>Total:</span>
            <span>$<span id="display-total">0.00</span></span>
        </div>

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
                <p class="text-muted" style="font-size: 14px; margin-bottom: 10px;">Please enter your full delivery address.
                </p>
                <textarea id="shipping-address" rows="3" placeholder="Street address, City, State, Zip Code..."
                    style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #d2d2d7;"></textarea>
            </div>
        </div>

        <div style="text-align: right; margin-top: 30px; display: flex; justify-content: flex-end;">
            <div id="paypal-button-container" style="width: 300px;"></div>
        </div>

        <script
            src="https://www.paypal.com/sdk/js?client-id=Ab91QiHAZkGW1YVrL_60iEZvAraUdaF-BCUFbrxdRw6zmaI3wZP0XlwZAoUQHe0FIE5cuYUZe4X4I0M6&currency=USD"></script>

        <script>
            $(document).ready(function () {
                let currentPromoCode = null;
                let discountAmount = 0;
                let discountInfo = null;

                function calculateTotal() {
                    let subtotal = 0;
                    let count = 0;
                    $('.item-checkbox:checked').each(function () {
                        subtotal += parseFloat($(this).data('subtotal'));
                        count++;
                    });

                    $('#display-subtotal').text(subtotal.toFixed(2));

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

                $('#applyPromo').on('click', function () {
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
                    }, function (res) {
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
                    }).fail(function () {
                        $('#promoMessage').html('<span style="color: #ff3b30;">Error validating promotion code</span>');
                    });
                });

                $(document).on('click', '#removePromo', function () {
                    currentPromoCode = null;
                    discountInfo = null;
                    $('#promoCode').val('').prop('disabled', false);
                    $('#applyPromo').text('Apply').prop('disabled', false);
                    $('#promoMessage').html('');
                    calculateTotal();
                });

                $('.item-checkbox, #select-all').change(function () {
                    if (this.id === 'select-all') {
                        $('.item-checkbox').prop('checked', $(this).prop('checked'));
                    } else if (!$(this).prop('checked')) {
                        $('#select-all').prop('checked', false);
                    }
                    calculateTotal();
                });

                $(document).on('change', '.addr-radio', function () {
                    if ($(this).val() === 'new') {
                        $('#new-address-input').slideDown();
                    } else {
                        $('#new-address-input').slideUp();
                    }
                });

                paypal.Buttons({
                    onInit: function (data, actions) {
                        if (calculateTotal() > 0) {
                            actions.enable();
                        } else {
                            actions.disable();
                        }

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
                        const savedAddr = $('input[name="address_option"]:checked');

                        if (savedAddr.length > 0) {
                            if (savedAddr.val() === 'new') {
                                address = $('#shipping-address').val().trim();
                            } else {
                                address = savedAddr.val().trim();
                            }
                        } else {
                            address = $('#shipping-address').val().trim();
                        }

                        if (address.length === 0) {
                            alert("Please select or enter a shipping address before proceeding.");
                            return actions.reject();
                        }

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

                            return fetch('../../controllers/order_controller.php?action=checkout', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    selected_items: selectedIds,
                                    transaction_id: details.id,
                                    promotion_code: currentPromoCode || null,
                                    discount_amount: discountAmount || 0,
                                    address: window.finalAddress
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