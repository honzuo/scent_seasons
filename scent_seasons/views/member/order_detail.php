<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();
if (!$order) {
    die("Order not found.");
}

$sql = "SELECT oi.*, p.name, p.image_path, (SELECT COUNT(*) FROM reviews r WHERE r.user_id = ? AND r.product_id = p.product_id AND r.order_id = ?) as is_reviewed FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id, $order_id, $order_id]);
$items = $stmt->fetchAll();
$status = trim(strtolower($order['status']));

$page_title = "Order Details #$order_id";
$path = "../../";
$extra_css = "shop.css"; // shop.css will be loaded first
require $path . 'includes/header.php';
?>

<!-- Load Order Modals CSS after header -->
<link rel="stylesheet" href="<?php echo $path; ?>css/order_modals.css">

<!-- Success/Cancel Messages -->
<?php if (isset($_GET['msg'])): ?>
    <?php 
    $msg_type = ($_GET['msg'] == 'cancelled' || $_GET['msg'] == 'returned') ? 'warning' : 'success';
    $msg_icon = ($_GET['msg'] == 'cancelled') ? '✓' : (($_GET['msg'] == 'returned') ? '↩' : '✓');
    ?>
    <div class="order-message-box <?php echo $msg_type; ?>">
        <div class="order-message-icon"><?php echo $msg_icon; ?></div>
        <h2 class="order-message-title <?php echo $msg_type; ?>">
            <?php 
                if ($_GET['msg'] == 'cancelled') echo 'Order Cancelled Successfully';
                elseif ($_GET['msg'] == 'returned') echo 'Return Request Submitted';
                else echo 'Action Completed';
            ?>
        </h2>
        <p class="order-message-text">
            <?php 
                if ($_GET['msg'] == 'cancelled') echo 'Your order has been cancelled. A confirmation email has been sent to you.';
                elseif ($_GET['msg'] == 'returned') echo 'Your return request has been submitted and is being processed.';
            ?>
        </p>
        <p class="order-message-note">
            <?php 
                if ($_GET['msg'] == 'cancelled') echo 'If payment was made, refund will be processed within 5-7 business days.';
                elseif ($_GET['msg'] == 'returned') echo 'You will receive updates via email. Refund will be processed after we receive the returned items.';
            ?>
        </p>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error" style="margin-bottom: 20px;">
        <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<div class="order-detail-box">
    <div class="order-header">
        <div class="flex-between">
            <div>
                <h2 class="mt-0">Order #<?php echo $order_id; ?></h2>
                <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
            </div>
            
            <!-- Action Buttons Based on Status -->
            <div class="action-buttons">
                <?php if ($status == 'pending'): ?>
                    <button onclick="openCancelModal(<?php echo $order_id; ?>)" class="btn-red">
                        ✗ Cancel Order
                    </button>
                <?php elseif ($status == 'completed'): ?>
                    <button onclick="openReturnModal(<?php echo $order_id; ?>)" class="btn-orange">
                        ↩ Return Order
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <p><strong>Shipping Address:</strong> <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
        <p>
            <strong>Status:</strong>
            <?php
            if ($status == 'completed') echo "<span class='text-green-bold'>✓ Completed</span>";
            elseif ($status == 'pending') echo "<span class='text-orange-bold'>⏳ Pending</span>";
            elseif ($status == 'cancelled') echo "<span class='text-red-bold'>✗ Cancelled</span>";
            elseif ($status == 'returned') echo "<span class='text-orange-bold'>↩ Returned</span>";
            else echo "<span class='text-dark-bold'>" . ucfirst($status) . "</span>";
            ?>
        </p>
    </div>

    <table class="table-list">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Subtotal</th>
                <?php if ($status == 'completed'): ?><th>Review</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <div class="flex-center" style="justify-content: flex-start;">
                            <img src="../../images/products/<?php echo $item['image_path']; ?>" class="thumbnail" style="margin-right:15px;">
                            <span style="font-weight:bold;"><?php echo $item['name']; ?></span>
                        </div>
                    </td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>$<?php echo number_format($item['price_each'], 2); ?></td>
                    <td style="font-weight:bold;">$<?php echo number_format($item['quantity'] * $item['price_each'], 2); ?></td>

                    <?php if ($status == 'completed'): ?>
                        <td>
                            <?php if ($item['is_reviewed'] > 0): ?>
                                <span class="tag-reviewed">&#10003; Reviewed</span>
                            <?php else: ?>
                                <a href="write_review.php?product_id=<?php echo $item['product_id']; ?>&order_id=<?php echo $order_id; ?>" class="btn-review">&#9733; Write Review</a>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-total">
        Total: $<?php echo number_format($order['total_amount'], 2); ?>
        <?php if ($status == 'cancelled' || $status == 'returned'): ?>
            <span style="color: #ff3b30; font-size: 16px; display: block; margin-top: 8px;">
                (<?php echo ucfirst($status); ?>)
            </span>
        <?php endif; ?>
    </div>

    <div class="mt-20">
        <a href="orders.php" style="text-decoration:none; color:#666;">&larr; Back to My Orders</a>
    </div>
</div>

<!-- Cancel Order Modal -->
<div id="cancelModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel Order</h3>
            <button class="modal-close-btn" onclick="closeModal('cancelModal')">&times;</button>
        </div>
        
        <p class="modal-description">
            Are you sure you want to cancel this order? This action cannot be undone.
        </p>
        
        <form action="<?php echo $path; ?>controllers/order_controller.php" method="POST">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="order_id" id="modal_order_id">
            
            <div class="form-group">
                <label>Reason for Cancellation: <span>*</span></label>
                <select name="cancel_reason" id="reason_select" onchange="toggleOther(this)" required>
                    <option value="">-- Select a Reason --</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Found a better price">Found a better price</option>
                    <option value="Ordered by mistake">Ordered by mistake</option>
                    <option value="Delivery takes too long">Delivery takes too long</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group" id="other_input" style="display:none;">
                <label>Please specify:</label>
                <textarea name="custom_reason" placeholder="Type your reason here..."></textarea>
            </div>

            <div class="btn-group">
                <button type="button" class="btn-cancel" onclick="closeModal('cancelModal')">Keep Order</button>
                <button type="submit" class="btn-confirm">Confirm Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Return Order Modal -->
<div id="returnModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Return Order</h3>
            <button class="modal-close-btn" onclick="closeModal('returnModal')">&times;</button>
        </div>
        
        <div class="warning-box">
            <p><strong>⚠️ Important:</strong> Please ensure items are in original condition with tags attached. Return shipping costs may apply.</p>
        </div>
        
        <p class="modal-description">
            Please tell us why you'd like to return this order.
        </p>
        
        <form action="../../controllers/order_controller.php" method="POST">
            <input type="hidden" name="action" value="return">
            <input type="hidden" name="order_id" id="return_order_id">
            
            <div class="form-group">
                <label>Reason for Return: <span>*</span></label>
                <select name="return_reason" id="return_reason_select" onchange="toggleReturnOther(this)" required>
                    <option value="">-- Select a Reason --</option>
                    <option value="Defective or damaged">Defective or damaged</option>
                    <option value="Wrong item received">Wrong item received</option>
                    <option value="Not as described">Not as described</option>
                    <option value="Quality not satisfactory">Quality not satisfactory</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group" id="return_other_input" style="display:none;">
                <label>Please specify:</label>
                <textarea name="custom_return_reason" placeholder="Describe the issue..."></textarea>
            </div>

            <div class="form-group">
                <label>Additional Notes (Optional):</label>
                <textarea name="return_notes" placeholder="Any additional information about the return..."></textarea>
            </div>

            <div class="btn-group">
                <button type="button" class="btn-cancel" onclick="closeModal('returnModal')">Keep Order</button>
                <button type="submit" class="btn-confirm btn-confirm-return">Submit Return Request</button>
            </div>
        </form>
    </div>
</div>

<script>
// Open Cancel Modal
function openCancelModal(orderId) {
    document.getElementById('modal_order_id').value = orderId;
    document.getElementById('cancelModal').style.display = 'flex';
}

// Open Return Modal
function openReturnModal(orderId) {
    document.getElementById('return_order_id').value = orderId;
    document.getElementById('returnModal').style.display = 'flex';
}

// Close Modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Toggle "Other" input for Cancel
function toggleOther(select) {
    var otherInput = document.getElementById('other_input');
    if (select.value === 'Other') {
        otherInput.style.display = 'block';
        otherInput.querySelector('textarea').required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.querySelector('textarea').required = false;
    }
}

// Toggle "Other" input for Return
function toggleReturnOther(select) {
    var otherInput = document.getElementById('return_other_input');
    if (select.value === 'Other') {
        otherInput.style.display = 'block';
        otherInput.querySelector('textarea').required = true;
    } else {
        otherInput.style.display = 'none';
        otherInput.querySelector('textarea').required = false;
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = 'none';
    }
}

// ESC key to close modal
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(function(modal) {
            modal.style.display = 'none';
        });
    }
});
</script>

<?php require $path . 'includes/footer.php'; ?>