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

// 3. [新增] 预加载所有订单详情 (Items) 用于弹窗
// 联表查询产品信息
$stmt_items = $pdo->query("SELECT oi.*, p.name, p.image_path 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.product_id");
$all_items_raw = $stmt_items->fetchAll();

// 按 order_id 分组整理数据
$items_by_order = [];
foreach ($all_items_raw as $item) {
    $items_by_order[$item['order_id']][] = $item;
}

$page_title = ($filter_user_id > 0) ? "Orders for User #$filter_user_id" : "Order Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div style="display:flex; justify-content:space-between; align-items:center;">
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
    <p style="color:green; font-weight:bold; background:#e8f5e9; padding:10px;">Order status updated.</p>
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
                        <small style="color:gray;"><?php echo $o['email']; ?></small>
                    </td>
                    <td><?php echo date('Y-m-d H:i', strtotime($o['order_date'])); ?></td>
                    <td>$<?php echo $o['total_amount']; ?></td>
                    <td>
                        <?php
                        $s = strtolower($o['status']);
                        if ($s == 'completed') echo '<span style="color:green;font-weight:bold;">Completed</span>';
                        elseif ($s == 'cancelled') echo '<span style="color:red;font-weight:bold;">Cancelled</span>';
                        else echo '<span style="color:orange;font-weight:bold;">Pending</span>';
                        ?>
                    </td>
                    <td>
                        <button type="button"
                            onclick="openOrderModal(<?php echo $o['order_id']; ?>, '<?php echo $o['status']; ?>')"
                            class="btn-blue"
                            style="padding:5px 10px; font-size:0.8em; cursor:pointer;">
                            Manage
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p style="color: gray; margin-top: 20px;">No orders found.</p>
<?php endif; ?>


<div id="manageOrderModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1500;">
    <div style="background:white; padding:30px; border-radius:8px; width:600px; max-height:90vh; overflow-y:auto; position:relative;">

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
            <h3 style="margin:0;" id="modalTitle">Manage Order</h3>
            <button onclick="closeOrderModal()" style="background:none; border:none; font-size:1.5em; cursor:pointer;">&times;</button>
        </div>

        <div style="margin-bottom:20px;">
            <h4 style="margin-top:0; color:#555;">Order Items:</h4>
            <div id="orderItemsContent" style="background:#f9f9f9; padding:10px; border-radius:4px;">
            </div>
        </div>

        <div style="background:#f0f8ff; padding:15px; border-radius:4px; border:1px solid #b6d4fe;">
            <h4 style="margin-top:0; color:#0d6efd;">Update Status</h4>
            <form action="../../../controllers/order_controller.php" method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="order_id" id="form_order_id">
                <input type="hidden" name="filter_user_id" value="<?php echo $filter_user_id; ?>">

                <div style="display:flex; gap:10px; align-items:center;">
                    <select name="status" id="form_status" style="flex:1; padding:8px; border-radius:4px; border:1px solid #ccc;">
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button type="submit" class="btn-green">Update</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    // 传递商品数据给 JS
    const itemsData = <?php echo json_encode($items_by_order); ?>;

    function openOrderModal(orderId, currentStatus) {
        // 1. 设置标题和表单
        document.getElementById('modalTitle').innerText = "Manage Order #" + orderId;
        document.getElementById('form_order_id').value = orderId;
        document.getElementById('form_status').value = currentStatus.toLowerCase(); // 选中当前状态

        // 2. 渲染商品列表
        const contentDiv = document.getElementById('orderItemsContent');
        const items = itemsData[orderId] || [];

        if (items.length === 0) {
            contentDiv.innerHTML = '<p>No items found.</p>';
        } else {
            let html = '<table style="width:100%; border-collapse:collapse;">';
            html += '<tr style="border-bottom:1px solid #ddd; text-align:left;"><th style="padding:5px;">Product</th><th style="padding:5px;">Qty</th><th style="padding:5px;">Subtotal</th></tr>';

            items.forEach(item => {
                let subtotal = (item.quantity * item.price_each).toFixed(2);
                html += `
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:8px; display:flex; align-items:center;">
                            <img src="../../../images/products/${item.image_path}" style="width:30px; height:30px; object-fit:cover; margin-right:10px;">
                            ${item.name}
                        </td>
                        <td style="padding:8px;">${item.quantity}</td>
                        <td style="padding:8px;">$${subtotal}</td>
                    </tr>
                `;
            });
            html += '</table>';
            contentDiv.innerHTML = html;
        }

        document.getElementById('manageOrderModal').style.display = 'flex';
    }

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