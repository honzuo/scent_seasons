<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin(); // 必须是管理员

// --- 1. 准备数据 ---
$filter_user_id = 'all'; // 默认看全部
$admins_list = [];

// 如果是 Superadmin，先获取所有管理员名单（用于下拉菜单）
if (is_superadmin()) {
    $stmt_users = $pdo->query("SELECT user_id, full_name, role FROM users WHERE role IN ('admin', 'superadmin') ORDER BY full_name ASC");
    $admins_list = $stmt_users->fetchAll();

    // 检查是否有筛选请求
    if (isset($_GET['filter_user_id']) && $_GET['filter_user_id'] != '') {
        $filter_user_id = $_GET['filter_user_id'];
    }
} else {
    // 普通 Admin 强制只能看自己
    $filter_user_id = $_SESSION['user_id'];
}

// --- 2. 构建查询 SQL ---
$sql = "SELECT l.*, u.full_name, u.role 
        FROM activity_logs l 
        LEFT JOIN users u ON l.user_id = u.user_id";

$params = [];

// 筛选逻辑
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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Activity Logs</h2>

    <?php if (is_superadmin()): ?>
        <form method="GET" action="" style="display: flex; gap: 10px; align-items: center;">
            <label style="font-weight: bold;">Filter by:</label>
            <select name="filter_user_id" onchange="this.form.submit()" style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
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

<p style="color:gray; margin-bottom: 20px;">
    <?php if (is_superadmin()): ?>
        <?php if ($filter_user_id == 'all'): ?>
            <span style="color:#8e44ad; font-weight:bold;">[Superadmin View]</span> Viewing <strong>EVERYONE'S</strong> activities.
        <?php else: ?>
            <span style="color:#8e44ad; font-weight:bold;">[Superadmin View]</span> Viewing filtered activities.
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
                    <td style="font-size:0.9em; color:#666; white-space:nowrap;">
                        <?php echo date('Y-m-d H:i', strtotime($log['created_at'])); ?>
                    </td>

                    <?php if (is_superadmin()): ?>
                        <td>
                            <?php if (isset($log['full_name'])): ?>
                                <strong><?php echo htmlspecialchars($log['full_name']); ?></strong><br>
                                <span style="font-size:0.8em; color:#999;">(<?php echo ucfirst($log['role']); ?>)</span>
                            <?php else: ?>
                                <span style="color:red;">(User Deleted)</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>

                    <td>
                        <span style="font-weight:bold; color:#2c3e50;"><?php echo htmlspecialchars($log['action']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($log['details']); ?></td>
                    <td style="font-size:0.9em; color:#999;"><?php echo $log['ip_address']; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="<?php echo is_superadmin() ? 5 : 4; ?>" style="text-align:center; padding:20px; color:gray;">
                    No logs found for this selection.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require $path . 'includes/footer.php'; ?>