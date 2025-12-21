<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 1. 接收筛选参数
$filter_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// 2. 查询订单
$sql = "SELECT o.*, u.full_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id";

if ($filter_user_id > 0) {
    $sql .= " WHERE o.user_id = ? ORDER BY o.order_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$filter_user_id]);
} else {
    $sql .= " ORDER BY o.order_date DESC";
    $stmt = $pdo->query($sql);
}
$orders = $stmt->fetchAll();

// 3. 预加载所有订单详情 (Items) 用于弹窗
$stmt_items = $pdo->query("SELECT oi.*, p.name, p.image_path 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.product_id");
$all_items_raw = $stmt_items->fetchAll();

$items_by_order = [];
foreach ($all_items_raw as $item) {
    $items_by_order[$item['order_id']][] = $item;
}

$page_title = ($filter_user_id > 0) ? "Orders for User #$filter_user_id" : "Order Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="flex-between mb-20">
    <h2>
        <?php if ($filter_user_id > 0): ?>
            Orders for User #<?php echo $filter_user_id; ?>
        <?php else: ?>
            All Customer Orders
        <?php endif; ?>
    </h2>

    <?php if ($filter_user_id > 0): ?>
        <a href="index.php" class="btn-blue" style="font-size:0.8em;">Show All Orders</a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
    <div class="alert alert-success">Order status updated.</div>
<?php endif; ?>

<?php if (count($orders) > 0): ?>
    <table class="table-list">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
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
                    <td>
                        <strong><?php echo $o['full_name']; ?></strong><br>
                        <small class="text-gray"><?php echo $o['email']; ?></small>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($o['order_date'])); ?></td>
                    <td>$<?php echo $o['total_amount']; ?></td>
                    <td>
                        <?php
                        $s = strtolower($o['status']);
                        if ($s == 'completed') echo '<span class="text-green-bold">✅ Completed</span>';
                        elseif ($s == 'cancelled') echo '<span class="text-red-bold">❌ Cancelled</span>';
                        elseif ($s == 'returned') echo '<span class="text-orange-bold">🔄 Return Requested</span>';
                        elseif ($s == 'refunded') echo '<span class="text-green-bold">💰 Refunded</span>';
                        else echo '<span class="text-orange-bold">⏳ Pending</span>';
                        ?>
                    </td>
                    <td>
                        <button type="button"
                            data-address="<?php echo htmlspecialchars($o['address'] ?? ''); ?>"
                            onclick="openOrderModal(<?php echo $o['order_id']; ?>, '<?php echo $o['status']; ?>', this.getAttribute('data-address'))"
                            class="btn-blue"
                            style="padding:5px 10px; font-size:0.8em;">
                            Manage
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="text-gray mt-20">No orders found.</p>
<?php endif; ?>


<div id="manageOrderModal" class="modal-overlay">
    <div class="modal-box medium">

        <div class="modal-header">
            <h3 id="modalTitle">Manage Order</h3>
            <button onclick="closeOrderModal()" class="modal-close-btn">&times;</button>
        </div>

        <div class="mb-20">
            <h4 class="mt-0" style="color:#555;">Order Items:</h4>
            <div id="orderItemsContent" class="order-items-container"></div>
        </div>

        <div class="status-box">
            <h4>Update Status</h4>
            <form action="../../../controllers/order_controller.php" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="form_order_id">
                <input type="hidden" name="filter_user_id" value="<?php echo $filter_user_id; ?>">

                <div class="status-form">
                    <select name="status" id="form_status" class="status-select">
                        <option value="pending">⏳ Pending</option>
                        <option value="completed">✅ Completed</option>
                        <option value="cancelled">❌ Cancelled</option>
                        <option value="returned">🔄 Returned (Refund Pending)</option>
                        <option value="refunded">💰 Refunded (Completed)</option>
                    </select>
                    <button type="submit" class="btn-green">Update</button>
                </div>
                
                <div id="status-help-text" style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 5px; font-size: 13px; display: none; color: #555;">
                    <!-- Dynamic help text will appear here -->
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    // 传递商品数据给 JS
    const itemsData = <?php echo json_encode($items_by_order); ?>;

    function openOrderModal(orderId, currentStatus, address) {
        // 1. 设置标题和表单
        document.getElementById('modalTitle').innerText = "Manage Order #" + orderId;
        document.getElementById('form_order_id').value = orderId;
        document.getElementById('form_status').value = currentStatus.toLowerCase();

        // Trigger help text update
        updateStatusHelpText(currentStatus.toLowerCase());

        // 2. 处理地址显示 (防止 XSS 并支持换行)
        const contentDiv = document.getElementById('orderItemsContent');

        // 移除旧的地址框 (防止重复添加)
        let oldAddr = document.getElementById('temp-admin-address');
        if (oldAddr) oldAddr.remove();

        // 创建新的地址框
        if (address && address.trim() !== "") {
            // 将换行符 \n 转换为 <br>
            let formattedAddress = address.replace(/\n/g, '<br>');

            let addrDiv = document.createElement('div');
            addrDiv.id = 'temp-admin-address';
            addrDiv.style.cssText = 'background:#f9f9f9; padding:15px; margin-bottom:20px; border-radius:8px; font-size:14px; line-height:1.5;';
            addrDiv.innerHTML = '<strong style="color:#333;">Shipping Address:</strong><br><span style="color:#555;">' + formattedAddress + '</span>';

            // 插入到商品列表前面
            contentDiv.parentNode.insertBefore(addrDiv, contentDiv);
        }

        // 3. 渲染商品列表 (保持原有逻辑)
        const items = itemsData[orderId] || [];

        if (items.length === 0) {
            contentDiv.innerHTML = '<p>No items found.</p>';
        } else {
            let html = '<table class="nested-table">';
            html += '<tr><th>Product</th><th>Qty</th><th>Subtotal</th></tr>';

            items.forEach(item => {
                let subtotal = (item.quantity * item.price_each).toFixed(2);
                html += `
                    <tr>
                        <td class="product-flex">
                            <img src="../../../images/products/${item.image_path}" class="product-thumb-small">
                            ${item.name}
                        </td>
                        <td>${item.quantity}</td>
                        <td>$${subtotal}</td>
                    </tr>
                `;
            });
            html += '</table>';
            contentDiv.innerHTML = html;
        }

        document.getElementById('manageOrderModal').style.display = 'flex';
    }

    function updateStatusHelpText(status) {
        const helpText = document.getElementById('status-help-text');
        
        const messages = {
            'pending': '⏳ Order is awaiting processing or payment confirmation.',
            'completed': '✅ Order has been fulfilled and delivered to customer.',
            'cancelled': '❌ Order was cancelled (stock has been restored).',
            'returned': '🔄 Customer requested return. Items restocked. Awaiting refund approval.',
            'refunded': '💰 Return approved and refund processed. Case closed.'
        };
        
        if (messages[status]) {
            helpText.textContent = messages[status];
            helpText.style.display = 'block';
        } else {
            helpText.style.display = 'none';
        }
    }

    // Add event listener for status change
    document.getElementById('form_status').addEventListener('change', function() {
        updateStatusHelpText(this.value);
    });

    function closeOrderModal() {
        document.getElementById('manageOrderModal').style.display = 'none';
    }

    window.onclick = function(event) {
        let modal = document.getElementById('manageOrderModal');
        if (event.target == modal) {
            closeOrderModal();
        }
    }
</script>

<?php require $path . 'includes/footer.php'; ?>