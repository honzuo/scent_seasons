<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();


$filter_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$filter_status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;


$sql = "SELECT o.*, u.full_name, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE 1=1";

$params = [];


if ($filter_user_id > 0) {
    $sql .= " AND o.user_id = ?";
    $params[] = $filter_user_id;
}


if (!empty($filter_status)) {
    $sql .= " AND LOWER(o.status) = ?";
    $params[] = strtolower($filter_status);
}


if (!empty($search_query)) {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR o.order_id LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}


$count_sql = str_replace("o.*, u.full_name, u.email", "COUNT(*) as total", $sql);
if (!empty($params)) {
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
} else {
    $count_stmt = $pdo->query($count_sql);
}
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $per_page);


switch ($sort_by) {
    case 'date_asc':
        $sql .= " ORDER BY o.order_date ASC";
        break;
    case 'amount_desc':
        $sql .= " ORDER BY o.total_amount DESC";
        break;
    case 'amount_asc':
        $sql .= " ORDER BY o.total_amount ASC";
        break;
    case 'customer_asc':
        $sql .= " ORDER BY u.full_name ASC";
        break;
    case 'customer_desc':
        $sql .= " ORDER BY u.full_name DESC";
        break;
    default:
        $sql .= " ORDER BY o.order_date DESC";
}


$offset = ($current_page - 1) * $per_page;
$sql .= " LIMIT $per_page OFFSET $offset";


$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();


$status_counts = [
    'all' => 0,
    'pending' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'returned' => 0,
    'refunded' => 0
];

$count_sql = "SELECT status, COUNT(*) as count FROM orders WHERE 1=1";
$count_params = [];

if ($filter_user_id > 0) {
    $count_sql .= " AND user_id = ?";
    $count_params[] = $filter_user_id;
}

if (!empty($search_query)) {
    $count_sql .= " AND order_id IN (
        SELECT o.order_id FROM orders o 
        JOIN users u ON o.user_id = u.user_id 
        WHERE u.full_name LIKE ? OR u.email LIKE ? OR o.order_id LIKE ?
    )";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
}

$count_sql .= " GROUP BY status";

if (!empty($count_params)) {
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
} else {
    $count_stmt = $pdo->query($count_sql);
}

$total_count_sql = "SELECT COUNT(*) as total FROM orders WHERE 1=1";
if ($filter_user_id > 0) {
    $total_count_sql .= " AND user_id = ?";
    $total_stmt = $pdo->prepare($total_count_sql);
    $total_stmt->execute([$filter_user_id]);
} else {
    $total_stmt = $pdo->query($total_count_sql);
}
$status_counts['all'] = $total_stmt->fetch()['total'];

while ($row = $count_stmt->fetch()) {
    $status_counts[strtolower($row['status'])] = (int)$row['count'];
}


$stmt_items = $pdo->query("SELECT oi.*, p.name, p.image_path 
                           FROM order_items oi 
                           JOIN products p ON oi.product_id = p.product_id");
$all_items_raw = $stmt_items->fetchAll();

$items_by_order = [];
foreach ($all_items_raw as $item) {
    $items_by_order[$item['order_id']][] = $item;
}


function build_filter_url($params_to_update = []) {
    global $filter_user_id, $filter_status, $search_query, $sort_by, $current_page;
    
    $params = [];
    
    if (isset($params_to_update['user_id'])) {
        if ($params_to_update['user_id'] > 0) $params['user_id'] = $params_to_update['user_id'];
    } else if ($filter_user_id > 0) {
        $params['user_id'] = $filter_user_id;
    }
    
    if (isset($params_to_update['status'])) {
        if (!empty($params_to_update['status'])) $params['status'] = $params_to_update['status'];
    } else if (!empty($filter_status)) {
        $params['status'] = $filter_status;
    }
    
    if (isset($params_to_update['search'])) {
        if (!empty($params_to_update['search'])) $params['search'] = $params_to_update['search'];
    } else if (!empty($search_query)) {
        $params['search'] = $search_query;
    }
    
    if (isset($params_to_update['sort'])) {
        $params['sort'] = $params_to_update['sort'];
    } else {
        $params['sort'] = $sort_by;
    }
    
    if (isset($params_to_update['page'])) {
        if ($params_to_update['page'] > 1) $params['page'] = $params_to_update['page'];
    } else if ($current_page > 1) {
        $params['page'] = $current_page;
    }
    
    return 'index.php' . (!empty($params) ? '?' . http_build_query($params) : '');
}

$page_title = "Order Management";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>


<link rel="stylesheet" href="<?php echo $path; ?>css/order_filter.css">
<link rel="stylesheet" href="<?php echo $path; ?>css/order_modals.css">

<div class="flex-between mb-20">
    <h2>📦 Order Management</h2>
    <?php if ($filter_user_id > 0): ?>
        <a href="<?php echo build_filter_url(['user_id' => 0]); ?>" class="btn-blue" style="font-size:0.9em;">
            🔙 Show All Orders
        </a>
    <?php endif; ?>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
    <div class="alert alert-success">✅ Order status updated successfully.</div>
<?php endif; ?>


<div class="filter-container">
 
    <div class="filter-tabs">
        <?php
        $statuses = [
            '' => ['label' => 'All Orders', 'icon' => '📋', 'key' => 'all'],
            'pending' => ['label' => 'Pending', 'icon' => '⏳', 'key' => 'pending'],
            'completed' => ['label' => 'Completed', 'icon' => '✅', 'key' => 'completed'],
            'returned' => ['label' => 'Returns', 'icon' => '🔄', 'key' => 'returned'],
            'refunded' => ['label' => 'Refunded', 'icon' => '💰', 'key' => 'refunded'],
            'cancelled' => ['label' => 'Cancelled', 'icon' => '❌', 'key' => 'cancelled'],
        ];
        
        foreach ($statuses as $status_val => $status_info):
            $is_active = ($filter_status === $status_val);
            $url = build_filter_url(['status' => $status_val, 'page' => 1]);
            $count = $status_counts[$status_info['key']];
        ?>
            <a href="<?php echo $url; ?>" class="filter-tab <?php echo $is_active ? 'active' : ''; ?>">
                <span><?php echo $status_info['icon']; ?> <?php echo $status_info['label']; ?></span>
                <span class="count"><?php echo $count; ?></span>
            </a>
        <?php endforeach; ?>
    </div>

   
    <form method="GET" action="index.php" class="search-bar">
        <?php if ($filter_user_id > 0): ?>
            <input type="hidden" name="user_id" value="<?php echo $filter_user_id; ?>">
        <?php endif; ?>
        <?php if (!empty($filter_status)): ?>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
        <?php endif; ?>
        
        <input 
            type="text" 
            name="search" 
            class="search-input" 
            placeholder="🔍 Search by name, email, or order ID..."
            value="<?php echo htmlspecialchars($search_query); ?>"
        >
        
        <select name="sort" class="sort-select">
            <option value="date_desc" <?php echo $sort_by == 'date_desc' ? 'selected' : ''; ?>>Latest First</option>
            <option value="date_asc" <?php echo $sort_by == 'date_asc' ? 'selected' : ''; ?>>Oldest First</option>
            <option value="amount_desc" <?php echo $sort_by == 'amount_desc' ? 'selected' : ''; ?>>Highest Amount</option>
            <option value="amount_asc" <?php echo $sort_by == 'amount_asc' ? 'selected' : ''; ?>>Lowest Amount</option>
            <option value="customer_asc" <?php echo $sort_by == 'customer_asc' ? 'selected' : ''; ?>>Customer A-Z</option>
            <option value="customer_desc" <?php echo $sort_by == 'customer_desc' ? 'selected' : ''; ?>>Customer Z-A</option>
        </select>
        
        <div class="filter-actions">
            <button type="submit" class="btn-search">🔍 Search</button>
            <a href="<?php echo build_filter_url(['status' => '', 'search' => '', 'page' => 1]); ?>" class="btn-clear">
                🔄 Clear
            </a>
        </div>
    </form>

 
    <?php if (!empty($filter_status) || !empty($search_query) || $filter_user_id > 0): ?>
        <div class="active-filters">
            <span style="color: #6e6e73; font-size: 13px; font-weight: 600;">Active Filters:</span>
            
            <?php if ($filter_user_id > 0): 
                $stmt_user = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
                $stmt_user->execute([$filter_user_id]);
                $user_name = $stmt_user->fetchColumn();
            ?>
                <span class="filter-badge">
                    👤 Customer: <?php echo htmlspecialchars($user_name); ?>
                    <a href="<?php echo build_filter_url(['user_id' => 0]); ?>" class="remove">×</a>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($filter_status)): ?>
                <span class="filter-badge">
                    📊 Status: <?php echo ucfirst($filter_status); ?>
                    <a href="<?php echo build_filter_url(['status' => '', 'page' => 1]); ?>" class="remove">×</a>
                </span>
            <?php endif; ?>
            
            <?php if (!empty($search_query)): ?>
                <span class="filter-badge">
                    🔍 Search: "<?php echo htmlspecialchars($search_query); ?>"
                    <a href="<?php echo build_filter_url(['search' => '', 'page' => 1]); ?>" class="remove">×</a>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="results-count">
        Showing <strong><?php echo count($orders); ?></strong> of <strong><?php echo $total_records; ?></strong> order<?php echo $total_records != 1 ? 's' : ''; ?>
        <?php if ($current_page > 1): ?>
            (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
        <?php endif; ?>
    </div>
</div>


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
                    <td><strong>#<?php echo $o['order_id']; ?></strong></td>
                    <td>
                        <strong><?php echo htmlspecialchars($o['full_name']); ?></strong><br>
                        <small class="text-gray"><?php echo htmlspecialchars($o['email']); ?></small>
                    </td>
                    <td><?php echo date('M d, Y H:i', strtotime($o['order_date'])); ?></td>
                    <td><strong>$<?php echo number_format($o['total_amount'], 2); ?></strong></td>
                    <td>
                        <?php
                        $s = strtolower($o['status']);
                        if ($s == 'completed') echo '<span class="badge badge-green">✅ Completed</span>';
                        elseif ($s == 'cancelled') echo '<span class="badge badge-red">❌ Cancelled</span>';
                        elseif ($s == 'returned') echo '<span class="badge badge-orange">🔄 Returned</span>';
                        elseif ($s == 'refunded') echo '<span class="badge badge-green">💰 Refunded</span>';
                        else echo '<span class="badge badge-orange">⏳ Pending</span>';
                        ?>
                    </td>
                    <td>
                        <button type="button"
                            data-address="<?php echo htmlspecialchars($o['address'] ?? ''); ?>"
                            onclick="openOrderModal(<?php echo $o['order_id']; ?>, '<?php echo $o['status']; ?>', this.getAttribute('data-address'))"
                            class="btn-blue"
                            style="padding:8px 16px; font-size:13px;">
                            ⚙️ Manage
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

  
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="<?php echo build_filter_url(['page' => 1]); ?>">« First</a>
                <a href="<?php echo build_filter_url(['page' => $current_page - 1]); ?>">‹ Prev</a>
            <?php else: ?>
                <span class="disabled">« First</span>
                <span class="disabled">‹ Prev</span>
            <?php endif; ?>

            <?php
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a href="<?php echo build_filter_url(['page' => $i]); ?>" 
                   class="<?php echo $i == $current_page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="<?php echo build_filter_url(['page' => $current_page + 1]); ?>">Next ›</a>
                <a href="<?php echo build_filter_url(['page' => $total_pages]); ?>">Last »</a>
            <?php else: ?>
                <span class="disabled">Next ›</span>
                <span class="disabled">Last »</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">🔭</div>
        <h3>No Orders Found</h3>
        <p>Try adjusting your filters or search query</p>
        <a href="<?php echo build_filter_url(['status' => '', 'search' => '', 'page' => 1]); ?>" class="btn-blue">
            Clear All Filters
        </a>
    </div>
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
                <input type="hidden" name="filter_status" value="<?php echo htmlspecialchars($filter_status); ?>">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="page" value="<?php echo $current_page; ?>">

                <div class="status-form">
                    <select name="status" id="form_status" class="status-select">
                        <option value="pending">⏳ Pending</option>
                        <option value="completed">✅ Completed</option>
                        <option value="cancelled">❌ Cancelled</option>
                        <option value="returned">🔄 Returned</option>
                        <option value="refunded">💰 Refunded</option>
                    </select>
                    <button type="submit" class="btn-green">Update</button>
                </div>
                
                <div id="status-help-text" style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 8px; font-size: 13px; display: none; color: #555;"></div>
            </form>
        </div>
    </div>
</div>

<script>
    const itemsData = <?php echo json_encode($items_by_order); ?>;

    function openOrderModal(orderId, currentStatus, address) {
        document.getElementById('modalTitle').innerText = "Manage Order #" + orderId;
        document.getElementById('form_order_id').value = orderId;
        document.getElementById('form_status').value = currentStatus.toLowerCase();

        updateStatusHelpText(currentStatus.toLowerCase());

        const contentDiv = document.getElementById('orderItemsContent');

        let oldAddr = document.getElementById('temp-admin-address');
        if (oldAddr) oldAddr.remove();

        if (address && address.trim() !== "") {
            let formattedAddress = address.replace(/\n/g, '<br>');
            let addrDiv = document.createElement('div');
            addrDiv.id = 'temp-admin-address';
            addrDiv.style.cssText = 'background:#f9f9f9; padding:15px; margin-bottom:20px; border-radius:8px; font-size:14px; line-height:1.5;';
            addrDiv.innerHTML = '<strong style="color:#333;">📍 Shipping Address:</strong><br><span style="color:#555;">' + formattedAddress + '</span>';
            contentDiv.parentNode.insertBefore(addrDiv, contentDiv);
        }

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