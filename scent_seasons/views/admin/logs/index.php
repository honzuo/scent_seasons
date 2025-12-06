<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin(); // 必须是管理员

// --- 核心逻辑：根据权限决定看谁的日志 ---
if (is_superadmin()) {
    // 1. Superadmin: 看所有人的日志
    // 使用 JOIN 连表查询，获取 users 表里的 full_name 和 role
    $sql = "SELECT l.*, u.full_name, u.role 
            FROM activity_logs l 
            LEFT JOIN users u ON l.user_id = u.user_id 
            ORDER BY l.created_at DESC";
    $stmt = $pdo->query($sql);
} else {
    // 2. 普通 Admin: 只看自己的日志
    $sql = "SELECT l.* FROM activity_logs l 
            WHERE l.user_id = ? 
            ORDER BY l.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
}

$logs = $stmt->fetchAll();

$page_title = "Activity Logs";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<h2>Activity Logs</h2>
<p style="color:gray; margin-bottom: 20px;">
    <?php if (is_superadmin()): ?>
        <span style="color:#8e44ad; font-weight:bold;">[Superadmin View]</span> Viewing activities of <strong>ALL</strong> admins.
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
                <td colspan="<?php echo is_superadmin() ? 5 : 4; ?>" style="text-align:center; padding:20px;">
                    No logs found.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require $path . 'includes/footer.php'; ?>