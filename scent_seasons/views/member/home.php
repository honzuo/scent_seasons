<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';

// Get popular products based on total quantity sold
// First check if there are any order_items, if not, just show random products
$check_orders_sql = "SELECT COUNT(*) as count FROM order_items";
$stmt = $pdo->query($check_orders_sql);
$has_orders = $stmt->fetch()['count'] > 0;

if ($has_orders) {
    // If there are orders, get popular items based on sales
    $popular_sql = "SELECT p.product_id, p.name, p.price, p.image_path, p.description, p.category_id, p.stock, 
                            COALESCE(SUM(oi.quantity), 0) as total_sold 
                     FROM products p 
                     LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                     WHERE p.is_deleted = 0 
                     GROUP BY p.product_id, p.name, p.price, p.image_path, p.description, p.category_id, p.stock
                     ORDER BY total_sold DESC, p.product_id ASC 
                     LIMIT 4";
    try {
        $stmt = $pdo->query($popular_sql);
        $hot_products = $stmt->fetchAll();
    } catch (Exception $e) {
        $hot_products = [];
    }
} else {
    $hot_products = [];
}

// Fallback: If no popular products found, show random products
if (empty($hot_products)) {
    $fallback_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->query($fallback_sql);
    $hot_products = $stmt->fetchAll();
}

// Row 2: Get personalized recommendations based on member's purchase habits
$recommended_products = [];
$user_id = $_SESSION['user_id'];

// First, check if user has any order history
$check_orders_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
$stmt = $pdo->prepare($check_orders_sql);
$stmt->execute([$user_id]);
$order_check = $stmt->fetch();
$has_order_history = $order_check['order_count'] > 0;

if ($has_order_history) {
    // User has order history: suggest products based on categories from their orders
    $recommended_sql = "SELECT DISTINCT p.* 
                        FROM products p 
                        WHERE p.is_deleted = 0 
                        AND p.category_id IN (
                            SELECT DISTINCT p2.category_id 
                            FROM order_items oi 
                            JOIN orders o ON oi.order_id = o.order_id 
                            JOIN products p2 ON oi.product_id = p2.product_id 
                            WHERE o.user_id = ? 
                            AND p2.category_id IS NOT NULL
                        )
                        AND p.product_id NOT IN (
                            SELECT DISTINCT oi.product_id 
                            FROM order_items oi 
                            JOIN orders o ON oi.order_id = o.order_id 
                            WHERE o.user_id = ?
                        )
                        ORDER BY RAND() 
                        LIMIT 4";
    $stmt = $pdo->prepare($recommended_sql);
    $stmt->execute([$user_id, $user_id]);
    $recommended_products = $stmt->fetchAll();
    
    // If no recommendations found (e.g., user bought all products in those categories), show random products
    if (empty($recommended_products)) {
        $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
        $stmt = $pdo->query($random_sql);
        $recommended_products = $stmt->fetchAll();
    }
} else {
    // User has no order history: suggest random products
    $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->query($random_sql);
    $recommended_products = $stmt->fetchAll();
}

$page_title = "Welcome - Scent Seasons";
$path = "../../";
$extra_css = "shop.css";

require $path . 'includes/header.php';
?>
<link rel="stylesheet" href="<?php echo $path; ?>css/memberchat.css">

<div class="hero-banner">
    <div class="hero-content">
        <h1>Discover Your Signature Scent</h1>
        <p>Experience the essence of luxury with our exclusive collection.</p>
        <a href="shop.php" class="btn-hero">Shop Now</a>
    </div>
</div>

<div class="features-section">
    <div class="feature-item">
        <h3>100% Authentic</h3>
        <p>Guaranteed original fragrances.</p>
    </div>
    <div class="feature-item">
        <h3>Fast Shipping</h3>
        <p>Delivery within 3-5 days.</p>
    </div>
    <div class="feature-item">
        <h3>Secure Payment</h3>
        <p>100% secure checkout.</p>
    </div>
</div>

<div class="container" style="margin-top: 50px;">
    <h2 style="text-align:center; margin-bottom: 30px;">Popular Items</h2>

    <?php if (empty($hot_products)): ?>
        <p style="text-align: center; color: #86868b;">No products available at the moment.</p>
    <?php else: ?>
        <div class="product-grid" style="grid-template-columns: repeat(4, 1fr);">
            <?php foreach ($hot_products as $p): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $p['product_id']; ?>">
                        <img src="../../images/products/<?php echo htmlspecialchars($p['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($p['name']); ?>"
                             onerror="this.src='../../images/products/default_product.jpg'">
                    </a>
                    <div class="p-info">
                        <h4><?php echo htmlspecialchars($p['name']); ?></h4>
                        <p class="p-price">$<?php echo number_format($p['price'], 2); ?></p>
                        <a href="product_detail.php?id=<?php echo $p['product_id']; ?>" class="btn-add">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<!-- Row 2: Recommended for You (Based on Purchase Habits) -->
<div class="container" style="margin-top: 50px;">
    <h2 style="text-align:center; margin-bottom: 30px;">Recommended for You</h2>

    <?php if (empty($recommended_products)): ?>
        <p style="text-align: center; color: #86868b;">No recommendations available at the moment.</p>
    <?php else: ?>
        <div class="product-grid" style="grid-template-columns: repeat(4, 1fr);">
            <?php foreach ($recommended_products as $product): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?php echo $product['product_id']; ?>">
                        <img src="../../images/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='../../images/products/default_product.jpg'">
                    </a>
                    <div class="p-info">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="p-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <a href="product_detail.php?id=<?php echo $product['product_id']; ?>" 
                           class="btn-add">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="container" style="text-align:center; margin-top:30px; margin-bottom: 50px;">
    <a href="shop.php" class="btn-blue" style="padding: 12px 25px; font-size: 1.1em;">View All Products</a>
</div>

<?php require $path . 'includes/footer.php'; ?>

<!-- Floating Chat -->
<div class="chat-fab" id="chatFab" title="Chat with admin">💬</div>
<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        <span>Support Chat</span>
        <button id="chatClose" style="background:transparent;border:none;color:#fff;font-size:18px;cursor:pointer;">×</button>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div style="text-align:center;color:#6e6e73;font-size:13px;">Say hello! The admin will reply soon.</div>
    </div>
    <div class="chat-input">
        <textarea id="chatInput" placeholder="Type a message..."></textarea>
        <button id="chatSend">Send</button>
    </div>
</div>

<script>
(function() {
    'use strict';

    const fab = document.getElementById('chatFab');
    const win = document.getElementById('chatWindow');
    const closeBtn = document.getElementById('chatClose');
    const msgBox = document.getElementById('chatMessages');
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSend');

    let poller = null;

    function toggleChat(open) {
        win.style.display = open ? 'flex' : 'none';
        if (open) {
            fetchMessages();
            if (poller) clearInterval(poller);
            poller = setInterval(fetchMessages, 5000);
        } else {
            if (poller) clearInterval(poller);
        }
    }

    function renderMessages(messages) {
        msgBox.innerHTML = '';
        if (!messages || messages.length === 0) {
            msgBox.innerHTML = '<div style="text-align:center;color:#6e6e73;font-size:13px;">No messages yet.</div>';
            return;
        }
        messages.forEach(function(m) {
            const div = document.createElement('div');
            div.className = 'bubble ' + (m.is_admin == 1 ? 'them' : 'me');
            div.innerHTML = escapeHtml(m.message) + '<span class="time">' + m.created_at + '</span>';
            msgBox.appendChild(div);
        });
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.innerText = str;
        return div.innerHTML;
    }

    function fetchMessages() {
        fetch('../../controllers/chat_controller.php?action=fetch_member')
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    renderMessages(res.messages);
                }
            }).catch(() => {});
    }

    function sendMessage() {
        const text = input.value.trim();
        if (!text) return;
        sendBtn.disabled = true;
        fetch('../../controllers/chat_controller.php?action=send_member', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'message=' + encodeURIComponent(text)
        }).then(r => r.json())
          .then(res => {
            sendBtn.disabled = false;
            if (res.status === 'success') {
                input.value = '';
                fetchMessages();
            }
        }).catch(() => { sendBtn.disabled = false; });
    }

    fab.addEventListener('click', function() { toggleChat(true); });
    closeBtn.addEventListener('click', function() { toggleChat(false); });
    sendBtn.addEventListener('click', sendMessage);
    input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
})();
</script>