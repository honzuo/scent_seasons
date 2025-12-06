<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();

// 1. 搜索逻辑
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$sql = "SELECT * FROM users WHERE role = 'member' AND (full_name LIKE ? OR email LIKE ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$members = $stmt->fetchAll();

// 2. 预加载订单数据 (为了 Orders 弹窗)
$stmt_orders = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
$all_orders_raw = $stmt_orders->fetchAll();
$orders_by_user = [];
foreach ($all_orders_raw as $o) {
    $orders_by_user[$o['user_id']][] = $o;
}

// 3. 预加载订单详情 (Items)
$stmt_items = $pdo->query("SELECT oi.*, p.name, p.image_path 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.product_id");
$all_items_raw = $stmt_items->fetchAll();
$items_by_order = [];
foreach ($all_items_raw as $item) {
    $items_by_order[$item['order_id']][] = $item;
}

$page_title = "Member Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Member Management</h2>

<div style="display:flex; justify-content:space-between; margin-bottom: 20px;">
    <div></div>
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search name or email..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] == 'blocked'): ?>
        <p style="color:red; font-weight:bold; background:#ffebee; padding:10px; border-radius:4px;">User has been blocked.</p>
    <?php elseif ($_GET['msg'] == 'unblocked'): ?>
        <p style="color:green; font-weight:bold; background:#e8f5e9; padding:10px; border-radius:4px;">User has been unblocked.</p>
    <?php elseif ($_GET['msg'] == 'deleted'): ?>
        <p style="color:green; font-weight:bold; background:#e8f5e9; padding:10px; border-radius:4px;">User deleted successfully.</p>
    <?php endif; ?>
<?php endif; ?>

<table class="table-list">
    <thead>
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Info</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($members as $m): ?>
            <tr style="<?php echo ($m['is_blocked'] == 1) ? 'background-color:#fff5f5;' : ''; ?>">
                <td>#<?php echo $m['user_id']; ?></td>
                <td>
                    <img src="../../../images/uploads/<?php echo $m['profile_photo']; ?>" class="thumbnail" style="border-radius: 50%;">
                </td>
                <td>
                    <strong><?php echo $m['full_name']; ?></strong><br>
                    <a href="mailto:<?php echo $m['email']; ?>" style="color: #666; text-decoration: none;">
                        <?php echo $m['email']; ?>
                    </a><br>
                    <small style="color:#999;">Joined: <?php echo isset($m['created_at']) ? date('Y-m-d', strtotime($m['created_at'])) : '-'; ?></small>
                </td>
                <td>
                    <?php if ($m['is_blocked'] == 1): ?>
                        <span style="color:white; background:red; padding:3px 8px; border-radius:4px; font-size:0.8em;">BLOCKED</span>
                    <?php else: ?>
                        <span style="color:white; background:green; padding:3px 8px; border-radius:4px; font-size:0.8em;">ACTIVE</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button"
                        onclick="openOrdersModal(<?php echo $m['user_id']; ?>, '<?php echo htmlspecialchars($m['full_name']); ?>')"
                        class="btn-blue"
                        style="padding: 5px 10px; font-size: 0.8em; margin-right: 5px; cursor: pointer;">
                        Orders
                    </button>

                    <?php if ($m['is_blocked'] == 1): ?>
                        <button type="button"
                            onclick="openBlockModal(<?php echo $m['user_id']; ?>, 1, '<?php echo htmlspecialchars($m['full_name']); ?>')"
                            class="btn-green"
                            style="padding: 5px 10px; font-size: 0.8em; cursor: pointer;">
                            Unblock
                        </button>
                    <?php else: ?>
                        <button type="button"
                            onclick="openBlockModal(<?php echo $m['user_id']; ?>, 0, '<?php echo htmlspecialchars($m['full_name']); ?>')"
                            class="btn-red"
                            style="padding: 5px 10px; font-size: 0.8em; cursor: pointer;">
                            Block
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($members) == 0): ?>
    <p style="text-align: center; color: gray; margin-top: 20px;">No members found.</p>
<?php endif; ?>


<div id="userOrdersModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:1500;">
    <div style="background:white; padding:30px; border-radius:8px; width:800px; max-height:85vh; overflow-y:auto; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:10px;">
            <h3 style="margin:0;" id="modalTitle">Order History</h3>
            <button onclick="closeOrdersModal()" style="background:none; border:none; font-size:1.5em; cursor:pointer;">&times;</button>
        </div>
        <div id="ordersContent"></div>
        <div style="text-align:right; margin-top:20px;">
            <button onclick="closeOrdersModal()" class="btn-blue" style="background:gray;">Close</button>
        </div>
    </div>
</div>

<div id="blockUserModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:2000;">
    <div style="background:white; padding:30px; border-radius:8px; width:400px; text-align:center;">
        <h3 style="margin-top:0;" id="blockModalTitle">Block User?</h3>
        <p style="color:gray; margin-bottom:20px;" id="blockModalMessage">
            Are you sure?
        </p>

        <form action="../../../controllers/member_controller.php" method="POST">
            <input type="hidden" name="action" value="toggle_block">
            <input type="hidden" name="user_id" id="block_user_id" value="">
            <input type="hidden" name="is_blocked" id="block_status_val" value="">

            <div style="display:flex; justify-content:center; gap:10px;">
                <button type="button" onclick="closeBlockModal()" class="btn-blue" style="background:gray;">Cancel</button>
                <button type="submit" id="blockModalBtn" class="btn-red">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    // --- Orders Modal Logic (保持不变) ---
    const ordersData = <?php echo json_encode($orders_by_user); ?>;
    const itemsData = <?php echo json_encode($items_by_order); ?>;

    function openOrdersModal(userId, userName) {
        document.getElementById('modalTitle').innerText = "Orders for: " + userName;
        const contentDiv = document.getElementById('ordersContent');
        const userOrders = ordersData[userId];

        if (!userOrders || userOrders.length === 0) {
            contentDiv.innerHTML = '<p style="text-align:center; color:gray; padding:20px;">No orders found for this user.</p>';
        } else {
            let html = `
                <table class="table-list" style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f2f2f2;">
                            <th style="width:50px;">Detail</th>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            userOrders.forEach(order => {
                let statusColor = 'black';
                if (order.status === 'completed') statusColor = 'green';
                else if (order.status === 'cancelled') statusColor = 'red';
                else statusColor = 'orange';
                let statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);

                let items = itemsData[order.order_id] || [];

                html += `
                    <tr style="border-bottom:1px solid #ddd;">
                        <td style="text-align:center;">
                            <button onclick="toggleDetails(${order.order_id})" id="btn-${order.order_id}" style="background:none; border:none; cursor:pointer; font-weight:bold; font-size:1.2em;">+</button>
                        </td>
                        <td>#${order.order_id}</td>
                        <td>${order.order_date}</td>
                        <td>$${order.total_amount}</td>
                        <td><span style="color:${statusColor}; font-weight:bold;">${statusText}</span></td>
                    </tr>
                    <tr id="detail-${order.order_id}" style="display:none; background-color:#f9f9f9;">
                        <td colspan="5" style="padding:15px 20px;">
                            <div style="font-weight:bold; margin-bottom:10px; color:#555;">Order Items:</div>
                            <table style="width:100%; border:1px solid #eee; background:white;">
                                <tr style="background:#eee; font-size:0.9em;">
                                    <th style="padding:5px;">Product</th>
                                    <th style="padding:5px;">Price</th>
                                    <th style="padding:5px;">Qty</th>
                                    <th style="padding:5px;">Subtotal</th>
                                </tr>
                `;
                if (items.length > 0) {
                    items.forEach(item => {
                        let subtotal = (item.quantity * item.price_each).toFixed(2);
                        html += `
                            <tr>
                                <td style="padding:5px; border-bottom:1px solid #eee;">
                                    <div style="display:flex; align-items:center;">
                                        <img src="../../../images/products/${item.image_path}" style="width:30px; height:30px; object-fit:cover; margin-right:10px;">
                                        ${item.name}
                                    </div>
                                </td>
                                <td style="padding:5px; border-bottom:1px solid #eee;">$${item.price_each}</td>
                                <td style="padding:5px; border-bottom:1px solid #eee;">${item.quantity}</td>
                                <td style="padding:5px; border-bottom:1px solid #eee;">$${subtotal}</td>
                            </tr>
                        `;
                    });
                } else {
                    html += `<tr><td colspan="4" style="padding:10px;">No items found.</td></tr>`;
                }
                html += `</table></td></tr>`;
            });
            html += `</tbody></table>`;
            contentDiv.innerHTML = html;
        }
        document.getElementById('userOrdersModal').style.display = 'flex';
    }

    function toggleDetails(orderId) {
        const row = document.getElementById('detail-' + orderId);
        const btn = document.getElementById('btn-' + orderId);
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            btn.innerText = '-';
            btn.style.color = 'red';
        } else {
            row.style.display = 'none';
            btn.innerText = '+';
            btn.style.color = 'black';
        }
    }

    function closeOrdersModal() {
        document.getElementById('userOrdersModal').style.display = 'none';
    }


    // --- Block/Unblock Modal Logic (新功能) ---
    function openBlockModal(id, currentStatus, name) {
        // currentStatus: 1=目前已封禁(需要解封), 0=目前正常(需要封禁)

        let newStatus = (currentStatus == 1) ? 0 : 1;
        let title = document.getElementById('blockModalTitle');
        let msg = document.getElementById('blockModalMessage');
        let btn = document.getElementById('blockModalBtn');

        document.getElementById('block_user_id').value = id;
        document.getElementById('block_status_val').value = newStatus;

        if (newStatus == 1) {
            // 准备封禁 (Block)
            title.innerText = "Block User?";
            title.style.color = "#c0392b";
            msg.innerHTML = `Are you sure you want to block <strong>${name}</strong>?<br>They will not be able to login.`;
            btn.innerText = "Confirm Block";
            btn.className = "btn-red";
        } else {
            // 准备解封 (Unblock)
            title.innerText = "Unblock User?";
            title.style.color = "#27ae60";
            msg.innerHTML = `Are you sure you want to unblock <strong>${name}</strong>?`;
            btn.innerText = "Confirm Unblock";
            btn.className = "btn-green";
        }

        document.getElementById('blockUserModal').style.display = 'flex';
    }

    function closeBlockModal() {
        document.getElementById('blockUserModal').style.display = 'none';
    }

    // 点击背景关闭
    window.onclick = function(event) {
        let orderModal = document.getElementById('userOrdersModal');
        let blockModal = document.getElementById('blockUserModal');
        if (event.target == orderModal) closeOrdersModal();
        if (event.target == blockModal) closeBlockModal();
    }
</script>

<?php require $path . 'includes/footer.php'; ?>