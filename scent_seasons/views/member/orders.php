<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$page_title = "Shop - Scent Seasons";
$path = "../../";
$extra_css = "shop.css"; 
require $path . 'includes/header.php';
?>

<style>
/* 简单的弹窗样式 */
.modal-overlay {
    display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;
}
.modal-content {
    background: white; padding: 25px; border-radius: 8px; width: 400px; max-width: 90%;
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
.form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.btn-group { text-align: right; margin-top: 20px; }
.btn-cancel { background: #ccc; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; margin-right: 10px; }
.btn-confirm { background: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
</style>

<h2>My Order History</h2>

<?php if (isset($_GET['msg'])): ?>
    <div style="padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center;
        <?php echo ($_GET['msg'] == 'cancelled') ? 'background:#f8d7da; color:#721c24;' : 'background:#d4edda; color:#155724;'; ?>">
        <?php 
            if ($_GET['msg'] == 'success') echo "Order Placed Successfully!";
            elseif ($_GET['msg'] == 'cancelled') echo "Order has been cancelled.";
            elseif ($_GET['msg'] == 'cannot_cancel') echo "This order cannot be cancelled.";
        ?>
    </div>
<?php endif; ?>

<?php if (count($orders) > 0): ?>
    <table class="table-list">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?php echo $o['order_id']; ?></td>
                    <td><?php echo date('M d, Y', strtotime($o['order_date'])); ?></td>
                    <td>$<?php echo number_format($o['total_amount'], 2); ?></td>
                    <td>
                        <?php
                        $status = ucfirst($o['status']);
                        if ($o['status'] == 'completed') echo "<span style='color:green; font-weight:bold;'>$status</span>";
                        elseif ($o['status'] == 'cancelled') echo "<span style='color:red;'>$status</span>";
                        else echo "<span style='color:orange;'>$status</span>";
                        ?>
                    </td>
                    <td>
                        <a href="order_detail.php?id=<?php echo $o['order_id']; ?>" class="btn-blue" style="padding:5px 10px; font-size:12px;">View</a>
                        
                        <?php if ($o['status'] == 'pending'): ?>
                            <button onclick="openCancelModal(<?php echo $o['order_id']; ?>)" 
                                    style="background:#e74c3c; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-size:12px; margin-left:5px;">
                                Cancel
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>You haven't placed any orders yet.</p>
<?php endif; ?>

<div id="cancelModal" class="modal-overlay">
    <div class="modal-content">
        <h3>Cancel Order</h3>
        <p>Are you sure you want to cancel this order?</p>
        
        <form action="../../controllers/order_controller.php" method="POST">
            <input type="hidden" name="action" value="cancel">
            <input type="hidden" name="order_id" id="modal_order_id">
            
            <div class="form-group">
                <label>Reason for Cancellation:</label>
                <select name="cancel_reason" id="reason_select" onchange="toggleOther(this)" required>
                    <option value="">-- Select a Reason --</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Found a better price">Found a better price</option>
                    <option value="Ordered by mistake">Ordered by mistake</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group" id="other_input" style="display:none;">
                <label>Please specify:</label>
                <textarea name="custom_reason" rows="3" placeholder="Type your reason here..."></textarea>
            </div>

            <div class="btn-group">
                <button type="button" class="btn-cancel" onclick="closeModal()">Keep Order</button>
                <button type="submit" class="btn-confirm">Confirm Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCancelModal(orderId) {
    document.getElementById('modal_order_id').value = orderId;
    document.getElementById('cancelModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

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
</script>

<?php require $path . 'includes/footer.php'; ?>