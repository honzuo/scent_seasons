<?php
session_start();
require '../../includes/functions.php';
require '../../config/database.php';
require_admin(); 
$stmt_returns = $pdo->query("SELECT COUNT(*) as return_count FROM orders WHERE status = 'returned'");
$return_data = $stmt_returns->fetch();
$pending_returns = $return_data['return_count'];


$sql_unread = "
    SELECT COUNT(*) as total_unread 
    FROM messages 
    WHERE is_admin = 0 
    AND admin_read = 0
";
$stmt_messages = $pdo->query($sql_unread);
$message_data = $stmt_messages->fetch();
$unread_messages = $message_data['total_unread'] ? (int)$message_data['total_unread'] : 0;

$page_title = "Admin Dashboard";
$path = "../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo $path; ?>css/order_modals.css">

<h1>Welcome, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin'; ?></h1>
<p>Manage your perfume shop from here.</p>

<div class="dashboard-grid">
    <div class="card">
        <h3>Products</h3>
        <p>Manage perfumes, stock & prices.</p>
        <a href="products/index.php">Go to Products &rarr;</a>
    </div>

    <div class="card">
        <h3>Members</h3>
        <p>View registered members.</p>
        <a href="members/index.php">Go to Members &rarr;</a>
    </div>

    <div class="card <?php echo ($pending_returns > 0) ? 'has-notification' : ''; ?>">
        <?php if ($pending_returns > 0): ?>
            <span class="notification-badge <?php echo ($pending_returns > 99) ? 'large' : ''; ?>">
                <?php echo ($pending_returns > 99) ? '99+' : $pending_returns; ?>
            </span>
        <?php endif; ?>
        
        <h3>Orders</h3>
        <p>View customer orders<?php echo ($pending_returns > 0) ? ' & handle returns' : ''; ?>.</p>
        <a href="orders/index.php">Go to Orders &rarr;</a>
        
        <?php if ($pending_returns > 0): ?>
            <div class="notification-alert">
                <small>
                    🔄 <?php echo $pending_returns; ?> return request<?php echo ($pending_returns > 1) ? 's' : ''; ?> pending
                </small>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Categories</h3>
        <p>Manage product categories.</p>
        <a href="categories/index.php">Go to Categories &rarr;</a>
    </div>

    <div class="card">
        <h3>Reviews</h3>
        <p>Moderating user reviews.</p>
        <a href="reviews/index.php">Go to Reviews &rarr;</a>
    </div>

    <div class="card">
        <h3>Activity Logs</h3>
        <p>Track system activities.</p>
        <a href="logs/index.php">View Logs &rarr;</a>
    </div>


    <div class="card <?php echo ($unread_messages > 0) ? 'has-notification' : ''; ?>" style="border-top: 4px solid #0c9ef5;">
        <?php if ($unread_messages > 0): ?>
            <span class="notification-badge <?php echo ($unread_messages > 99) ? 'large' : ''; ?>">
                <?php echo ($unread_messages > 99) ? '99+' : $unread_messages; ?>
            </span>
        <?php endif; ?>
        
        <h3 style="color:#0c9ef5;">Chat</h3>
        <p>Chat with members in real time.</p>
        <a href="chat/index.php" style="color:#0c9ef5;">Open Chat &rarr;</a>
        
        <?php if ($unread_messages > 0): ?>
            <div class="notification-alert" style="background: #f0f9ff; border-left-color: #0c9ef5;">
                <small style="color: #0c9ef5;">
                    💬 <?php echo $unread_messages; ?> unread message<?php echo ($unread_messages > 1) ? 's' : ''; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="border-top: 4px solid #0071e3;">
        <h3 style="color:#0071e3;">Reports</h3>
        <p>Product performance & analytics.</p>
        <a href="reports/index.php" style="color:#0071e3;">View Reports &rarr;</a>
    </div>

    <div class="card" style="border-top: 4px solid #ff9500;">
        <h3 style="color:#ff9500;">Promotions</h3>
        <p>Manage discount codes & offers.</p>
        <a href="promotion.php" style="color:#ff9500;">Manage Promotions &rarr;</a>
    </div>

    <?php if (is_superadmin()): ?>
        <div class="card" style="border-top: 4px solid #8e44ad;">
            <h3 style="color:#8e44ad;">Admin Maintenance</h3>
            <p>Manage system administrators.</p>
            <a href="users/index.php" style="color:#8e44ad;">Manage Admins &rarr;</a>
        </div>
    <?php endif; ?>

</div>

<?php require $path . 'includes/footer.php'; ?>