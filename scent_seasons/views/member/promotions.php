<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

if (!is_logged_in()) {
    header("Location: ../public/login.php");
    exit();
}


try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS promotion_codes (
        code_id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) UNIQUE NOT NULL,
        discount_type ENUM('percentage', 'fixed') NOT NULL,
        discount_value DECIMAL(10,2) NOT NULL,
        min_purchase DECIMAL(10,2) DEFAULT 0,
        max_discount DECIMAL(10,2) DEFAULT NULL,
        usage_limit INT DEFAULT NULL,
        used_count INT DEFAULT 0,
        start_date DATE DEFAULT NULL,
        end_date DATE DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {

}


$stmt = $pdo->query("SELECT * FROM promotion_codes ORDER BY created_at DESC");
$promotions = $stmt->fetchAll();


$current_date = date('Y-m-d');
$current_timestamp = strtotime($current_date);
foreach ($promotions as &$promo) {
    $promo['is_expired'] = false;
    $promo['not_started'] = false;
    
    
    if ($promo['end_date']) {
        $end_timestamp = strtotime($promo['end_date']);
    
        if ($end_timestamp < $current_timestamp) {
            $promo['is_expired'] = true;
        }
    }
    
  
    if ($promo['start_date']) {
        $start_timestamp = strtotime($promo['start_date']);
       
        if ($start_timestamp > $current_timestamp) {
            $promo['not_started'] = true;
        }
    }
}
unset($promo); 

$page_title = "Promotion Codes";
$path = "../../";
$extra_css = "promotion.css";

require $path . 'includes/header.php';
?>

<div class="promotions-header">
    <h2>Promotion Codes</h2>
    <button id="btn-open-create" class="btn-blue">+ Add New Promotion Code</button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
        <?php
        if ($_GET['msg'] == 'created') echo "Promotion code created successfully.";
        elseif ($_GET['msg'] == 'updated') echo "Promotion code updated successfully.";
        elseif ($_GET['msg'] == 'deleted') echo "Promotion code deleted successfully.";
        ?>
    </div>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>Code</th>
            <th>Discount</th>
            <th>Min Purchase</th>
            <th>Usage</th>
            <th>Valid Period</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($promotions)): ?>
            <tr>
                <td colspan="7" class="text-center">
                    <div class="promotions-empty">
                        <p>No promotion codes found.</p>
                        <button id="btn-open-create-empty" class="btn-blue">Create Your First Promotion Code</button>
                    </div>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($promotions as $p): ?>
                <tr>
                    <td>
                        <span class="promo-code-badge"><?php echo htmlspecialchars($p['code']); ?></span>
                    </td>
                    <td>
                        <div class="discount-display">
                            <?php if ($p['discount_type'] == 'percentage'): ?>
                                <span class="discount-value"><?php echo $p['discount_value']; ?>% OFF</span>
                                <?php if ($p['max_discount']): ?>
                                    <small>Max: $<?php echo number_format($p['max_discount'], 2); ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="discount-value">$<?php echo number_format($p['discount_value'], 2); ?> OFF</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <strong>$<?php echo number_format($p['min_purchase'], 2); ?></strong>
                        <small class="text-gray" style="display: block; font-size: 12px; margin-top: 4px;">min purchase</small>
                    </td>
                    <td>
                        <strong><?php echo $p['used_count']; ?></strong>
                        <?php if ($p['usage_limit']): ?>
                            / <?php echo $p['usage_limit']; ?>
                        <?php else: ?>
                            <small class="text-gray" style="display: block; font-size: 12px; margin-top: 4px;">Unlimited</small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($p['start_date'] || $p['end_date']): ?>
                            <div style="font-size: 14px;">
                                <?php echo $p['start_date'] ? date('M d, Y', strtotime($p['start_date'])) : '<span class="text-gray">No start</span>'; ?>
                                <br>
                                <span style="color: #86868b; font-size: 12px;">to</span>
                                <br>
                                <?php echo $p['end_date'] ? date('M d, Y', strtotime($p['end_date'])) : '<span class="text-gray">No end</span>'; ?>
                            </div>
                        <?php else: ?>
                            <span class="text-gray">Always valid</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $status_class = 'inactive';
                        $status_text = 'Inactive';
                        
                        if ($p['is_expired']) {
                            $status_class = 'expired';
                            $status_text = 'Expired';
                        } elseif ($p['not_started']) {
                            $status_class = 'pending';
                            $status_text = 'Not Started';
                        } elseif ($p['is_active']) {
                            $status_class = 'active';
                            $status_text = 'Active';
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-blue js-open-edit" data-id="<?php echo $p['code_id']; ?>"
                                    data-code="<?php echo htmlspecialchars($p['code']); ?>"
                                    data-type="<?php echo $p['discount_type']; ?>"
                                    data-value="<?php echo $p['discount_value']; ?>"
                                    data-min="<?php echo $p['min_purchase']; ?>"
                                    data-max="<?php echo $p['max_discount']; ?>"
                                    data-limit="<?php echo $p['usage_limit']; ?>"
                                    data-start="<?php echo $p['start_date']; ?>"
                                    data-end="<?php echo $p['end_date']; ?>"
                                    data-active="<?php echo $p['is_active']; ?>">Edit</button>
                            <form action="../../controllers/promotion_controller.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="code_id" value="<?php echo $p['code_id']; ?>">
                                <button type="submit" class="btn-red" 
                                        onclick="return confirm('Delete this promotion code?');">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>


<div id="createModal" class="modal-overlay" style="display: none;">
    <div class="modal-box medium">
        <h3>Create Promotion Code</h3>
        <form action="../../controllers/promotion_controller.php" method="POST" class="promotion-form">
            <input type="hidden" name="action" value="create">
            <div class="form-group">
                <label>Code:</label>
                <input type="text" name="code" required maxlength="50" placeholder="e.g., SAVE20">
            </div>
            <div class="form-group">
                <label>Discount Type:</label>
                <select name="discount_type" required>
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount ($)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Discount Value:</label>
                <input type="number" name="discount_value" step="0.01" min="0" required placeholder="e.g., 20 or 10.50">
            </div>
            <div class="form-group">
                <label>Minimum Purchase ($):</label>
                <input type="number" name="min_purchase" step="0.01" min="0" value="0">
            </div>
            <div class="form-group">
                <label>Max Discount ($)<br><small style="font-weight: 400; color: #86868b;">For percentage discounts only</small></label>
                <input type="number" name="max_discount" step="0.01" min="0" placeholder="Optional">
            </div>
            <div class="form-group">
                <label>Usage Limit:</label>
                <input type="number" name="usage_limit" min="1" placeholder="Leave empty for unlimited">
            </div>
            <div class="form-group">
                <label>Start Date:</label>
                <input type="date" name="start_date">
            </div>
            <div class="form-group">
                <label>End Date:</label>
                <input type="date" name="end_date">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" checked> Active
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-blue">Create</button>
                <button type="button" class="btn-red js-close-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>


<div id="editModal" class="modal-overlay" style="display: none;">
    <div class="modal-box medium">
        <h3>Edit Promotion Code</h3>
        <form action="../../controllers/promotion_controller.php" method="POST" class="promotion-form">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="code_id" id="edit_code_id">
            <div class="form-group">
                <label>Code:</label>
                <input type="text" name="code" id="edit_code" required maxlength="50">
            </div>
            <div class="form-group">
                <label>Discount Type:</label>
                <select name="discount_type" id="edit_discount_type" required>
                    <option value="percentage">Percentage (%)</option>
                    <option value="fixed">Fixed Amount ($)</option>
                </select>
            </div>
            <div class="form-group">
                <label>Discount Value:</label>
                <input type="number" name="discount_value" id="edit_discount_value" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Minimum Purchase ($):</label>
                <input type="number" name="min_purchase" id="edit_min_purchase" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Max Discount ($)<br><small style="font-weight: 400; color: #86868b;">For percentage discounts only</small></label>
                <input type="number" name="max_discount" id="edit_max_discount" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Usage Limit:</label>
                <input type="number" name="usage_limit" id="edit_usage_limit" min="1">
            </div>
            <div class="form-group">
                <label>Start Date:</label>
                <input type="date" name="start_date" id="edit_start_date">
            </div>
            <div class="form-group">
                <label>End Date:</label>
                <input type="date" name="end_date" id="edit_end_date">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" value="1" id="edit_is_active"> Active
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-blue">Update</button>
                <button type="button" class="btn-red js-close-modal">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    function openModal(modalId) {
        $(modalId).css('display', 'flex');
    }
    
    function closeModal(modalId) {
        $(modalId).css('display', 'none');
    }
    
    $('#btn-open-create, #btn-open-create-empty').on('click', function() {
        openModal('#createModal');
    });

    $('.js-open-edit').on('click', function() {
        const btn = $(this);
        $('#edit_code_id').val(btn.data('id'));
        $('#edit_code').val(btn.data('code'));
        $('#edit_discount_type').val(btn.data('type'));
        $('#edit_discount_value').val(btn.data('value'));
        $('#edit_min_purchase').val(btn.data('min'));
        $('#edit_max_discount').val(btn.data('max') || '');
        $('#edit_usage_limit').val(btn.data('limit') || '');
        $('#edit_start_date').val(btn.data('start') || '');
        $('#edit_end_date').val(btn.data('end') || '');
        $('#edit_is_active').prop('checked', btn.data('active') == 1);
        openModal('#editModal');
    });

    $('.js-close-modal').on('click', function() {
        closeModal($(this).closest('.modal-overlay').attr('id') ? '#' + $(this).closest('.modal-overlay').attr('id') : '.modal-overlay');
    });

    $(document).on('click', function(event) {
        if ($(event.target).hasClass('modal-overlay')) {
            closeModal('#' + $(event.target).attr('id'));
        }
    });
    
  
    $(document).on('keydown', function(event) {
        if (event.key === 'Escape') {
            $('.modal-overlay').css('display', 'none');
        }
    });
});
</script>

<?php require $path . 'includes/footer.php'; ?>

