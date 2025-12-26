<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();


$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$sql = "SELECT * FROM users WHERE role = 'member' AND (full_name LIKE ? OR email LIKE ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$members = $stmt->fetchAll();


$stmt_orders = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
$all_orders_raw = $stmt_orders->fetchAll();
$orders_by_user = [];
foreach ($all_orders_raw as $o) {
    $orders_by_user[$o['user_id']][] = $o;
}


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

<div class="flex-between mb-20">
    <div></div>
    <form method="GET" action="" class="search-form">
        <input type="text" name="search" placeholder="Search name or email..." value="<?php echo $search; ?>">
        <button type="submit" class="btn-blue">Search</button>
    </form>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert <?php echo ($_GET['msg'] == 'blocked') ? 'alert-error' : 'alert-success'; ?>">
        <?php
        if ($_GET['msg'] == 'blocked') echo "User has been blocked.";
        elseif ($_GET['msg'] == 'unblocked') echo "User has been unblocked.";
        elseif ($_GET['msg'] == 'deleted') echo "User deleted successfully.";
        ?>
    </div>
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
            <tr class="<?php echo ($m['is_blocked'] == 1) ? 'bg-red-light' : ''; ?>">
                <td>#<?php echo $m['user_id']; ?></td>
                <td>
                    <img src="../../../images/uploads/<?php echo $m['profile_photo']; ?>" class="thumbnail thumbnail-circle">
                </td>
                <td>
                    <strong><?php echo $m['full_name']; ?></strong><br>
                    <a href="mailto:<?php echo $m['email']; ?>" class="text-link-gray">
                        <?php echo $m['email']; ?>
                    </a><br>
                    <small class="text-muted">Joined: <?php echo isset($m['created_at']) ? date('Y-m-d', strtotime($m['created_at'])) : '-'; ?></small>
                </td>
                <td>
                    <?php if ($m['is_blocked'] == 1): ?>
                        <span class="badge badge-red">BLOCKED</span>
                    <?php else: ?>
                        <span class="badge badge-green">ACTIVE</span>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button"
                        onclick="openOrdersModal(<?php echo $m['user_id']; ?>, '<?php echo htmlspecialchars($m['full_name']); ?>')"
                        class="btn-blue" style="margin-right: 5px;">
                        Orders
                    </button>

                    <?php if ($m['is_blocked'] == 1): ?>
                        <button type="button"
                            onclick="openBlockModal(<?php echo $m['user_id']; ?>, 1, '<?php echo htmlspecialchars($m['full_name']); ?>')"
                            class="btn-green">
                            Unblock
                        </button>
                    <?php else: ?>
                        <button type="button"
                            onclick="openBlockModal(<?php echo $m['user_id']; ?>, 0, '<?php echo htmlspecialchars($m['full_name']); ?>')"
                            class="btn-red">
                            Block
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (count($members) == 0): ?>
    <p class="text-center text-gray mt-20">No members found.</p>
<?php endif; ?>


<div id="userOrdersModal" class="modal-overlay">
    <div class="modal-box large">
        <div class="modal-header">
            <h3 id="modalTitle">Order History</h3>
            <button onclick="closeOrdersModal()" class="modal-close-btn">&times;</button>
        </div>
        <div id="ordersContent"></div>
        <div class="modal-actions">
            <button onclick="closeOrdersModal()" class="btn-disabled">Close</button>
        </div>
    </div>
</div>

<div id="blockUserModal" class="modal-overlay">
    <div class="modal-box small text-center">
        <h3 id="blockModalTitle" class="mt-0">Block User?</h3>
        <p id="blockModalMessage" class="mb-20 text-gray">Are you sure?</p>

        <form action="../../../controllers/member_controller.php" method="POST">
            <input type="hidden" name="action" value="toggle_block">
            <input type="hidden" name="user_id" id="block_user_id" value="">
            <input type="hidden" name="is_blocked" id="block_status_val" value="">

            <div class="modal-actions center">
                <button type="button" onclick="closeBlockModal()" class="btn-disabled">Cancel</button>
                <button type="submit" id="blockModalBtn" class="btn-red">Confirm</button>
            </div>
        </form>
    </div>
</div>

<script>
    const ordersData = <?php echo json_encode($orders_by_user); ?>;
    const itemsData = <?php echo json_encode($items_by_order); ?>;

    function openOrdersModal(userId, userName) {
        document.getElementById('modalTitle').innerText = "Orders for: " + userName;
        const contentDiv = document.getElementById('ordersContent');
        const userOrders = ordersData[userId];

        if (!userOrders || userOrders.length === 0) {
            contentDiv.innerHTML = '<p class="text-center text-gray" style="padding:20px;">No orders found for this user.</p>';
        } else {
            let html = `
                <table class="table-list">
                    <thead>
                        <tr>
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
                let statusClass = 'text-dark-bold';
                if (order.status === 'completed') statusClass = 'text-green-bold';
                else if (order.status === 'cancelled') statusClass = 'text-red-bold';
                else statusClass = 'text-orange-bold';

                let statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                let items = itemsData[order.order_id] || [];

                html += `
                    <tr>
                        <td class="text-center">
                            <button onclick="toggleDetails(${order.order_id})" id="btn-${order.order_id}" class="btn-expand">+</button>
                        </td>
                        <td>#${order.order_id}</td>
                        <td>${order.order_date}</td>
                        <td>$${order.total_amount}</td>
                        <td><span class="${statusClass}">${statusText}</span></td>
                    </tr>
                    <tr id="detail-${order.order_id}" class="detail-row">
                        <td colspan="5" class="detail-cell">
                            <div class="detail-title">Order Items:</div>
                            <table class="nested-table">
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Subtotal</th>
                                </tr>
                `;
                if (items.length > 0) {
                    items.forEach(item => {
                        let subtotal = (item.quantity * item.price_each).toFixed(2);
                        html += `
                            <tr>
                                <td class="product-flex">
                                    <img src="../../../images/products/${item.image_path}" class="product-thumb-small">
                                    ${item.name}
                                </td>
                                <td>$${item.price_each}</td>
                                <td>${item.quantity}</td>
                                <td>$${subtotal}</td>
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
        if (row.style.display === 'none' || row.style.display === '') {
            row.style.display = 'table-row';
            btn.innerText = '-';
            btn.classList.add('expanded');
        } else {
            row.style.display = 'none';
            btn.innerText = '+';
            btn.classList.remove('expanded');
        }
    }

    function closeOrdersModal() {
        document.getElementById('userOrdersModal').style.display = 'none';
    }

  
    function openBlockModal(id, currentStatus, name) {
        let newStatus = (currentStatus == 1) ? 0 : 1;
        let title = document.getElementById('blockModalTitle');
        let msg = document.getElementById('blockModalMessage');
        let btn = document.getElementById('blockModalBtn');

        document.getElementById('block_user_id').value = id;
        document.getElementById('block_status_val').value = newStatus;

        if (newStatus == 1) {
            title.innerText = "Block User?";
            title.style.color = "#c0392b";
            msg.innerHTML = `Are you sure you want to block <strong>${name}</strong>?<br>They will not be able to login.`;
            btn.innerText = "Confirm Block";
            btn.className = "btn-red";
        } else {
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

    window.onclick = function(event) {
        let orderModal = document.getElementById('userOrdersModal');
        let blockModal = document.getElementById('blockUserModal');
        if (event.target == orderModal) closeOrdersModal();
        if (event.target == blockModal) closeBlockModal();
    }
</script>

<?php require $path . 'includes/footer.php'; ?>