<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin(); 


$filter_user_id = 'all';
$admins_list = [];


if (is_superadmin()) {
    $stmt_users = $pdo->query("SELECT user_id, full_name, role FROM users WHERE role IN ('admin', 'superadmin') ORDER BY full_name ASC");
    $admins_list = $stmt_users->fetchAll();


    if (isset($_GET['filter_user_id']) && $_GET['filter_user_id'] != '') {
        $filter_user_id = $_GET['filter_user_id'];
    }
} else {
 
    $filter_user_id = $_SESSION['user_id'];
}


$sql = "SELECT l.*, u.full_name, u.role 
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.user_id";

$params = [];


if ($filter_user_id != 'all') {
    $sql .= " WHERE l.user_id = ?";
    $params[] = $filter_user_id;
}

$sql .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$page_title = "Activity Logs";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<div class="flex-between mb-20">
    <h2>Activity Logs</h2>

    <?php if (is_superadmin()): ?>
        <form method="GET" action="" class="logs-filter-form">
            <label class="logs-filter-label">Filter by:</label>
            <select name="filter_user_id" onchange="this.form.submit()" class="logs-filter-select">
                <option value="all" <?php echo ($filter_user_id == 'all') ? 'selected' : ''; ?>>--- View All Activities ---</option>

                <?php foreach ($admins_list as $admin): ?>
                    <option value="<?php echo $admin['user_id']; ?>" <?php echo ($filter_user_id == $admin['user_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($admin['full_name']); ?>
                        (<?php echo ucfirst($admin['role']); ?>)
                        <?php echo ($admin['user_id'] == $_SESSION['user_id']) ? '[ME]' : ''; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    <?php endif; ?>
</div>

<p class="text-gray mb-20">
    <?php if (is_superadmin()): ?>
        <?php if ($filter_user_id == 'all'): ?>
            <span class="text-superadmin">[Superadmin View]</span> Viewing <strong>EVERYONE'S</strong> activities.
        <?php else: ?>
            <span class="text-superadmin">[Superadmin View]</span> Viewing filtered activities.
        <?php endif; ?>
    <?php else: ?>
        Viewing <strong>YOUR</strong> activities only.
    <?php endif; ?>
</p>

<table class="table-list">
    <thead>
        <tr>
            <th>Time</th>
            <?php if (is_superadmin()): ?>
                <th>Admin User</th>
            <?php endif; ?>
            <th>Action</th>
            <th>Details</th>
            <th>IP</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($logs) > 0): ?>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="cell-time">
                        <?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                    </td>

                    <?php if (is_superadmin()): ?>
                        <td>
                            <?php if (isset($log['full_name'])): ?>
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                <span class="text-muted-small">(<?php echo ucfirst($log['role']); ?>)</span>
                            <?php else: ?>
                                <span class="text-danger">(User Deleted)</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <td>
                        <span class="text-action-bold"><?php echo htmlspecialchars($log['action']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                    <td class="text-muted-small"><?php echo $log['ip_address']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo is_superadmin() ? 5 : 4; ?>" class="empty-logs-cell">
                    No logs found for this selection.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require $path . 'includes/footer.php'; ?>