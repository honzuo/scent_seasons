<?php
session_start();
require '../../config/database.php';
require '../../includes/functions.php';


$check_orders_sql = "SELECT COUNT(*) as count FROM order_items";
$stmt = $pdo->query($check_orders_sql);
$has_orders = $stmt->fetch()['count'] > 0;

if ($has_orders) {
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

if (empty($hot_products)) {
    $fallback_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->query($fallback_sql);
    $hot_products = $stmt->fetchAll();
}


$recommended_products = [];

if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];

    $check_orders_sql = "SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?";
    $stmt = $pdo->prepare($check_orders_sql);
    $stmt->execute([$user_id]);
    $order_check = $stmt->fetch();
    $has_order_history = $order_check['order_count'] > 0;

    if ($has_order_history) {
        $recommended_sql = "SELECT DISTINCT p.* FROM products p 
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

        if (empty($recommended_products)) {
            $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
            $stmt = $pdo->query($random_sql);
            $recommended_products = $stmt->fetchAll();
        }
    } else {
        $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
        $stmt = $pdo->query($random_sql);
        $recommended_products = $stmt->fetchAll();
    }
} else {
    $random_sql = "SELECT * FROM products WHERE is_deleted = 0 ORDER BY RAND() LIMIT 4";
    $stmt = $pdo->query($random_sql);
    $recommended_products = $stmt->fetchAll();
}

$page_title = "Welcome - Scent Seasons";
$path = "../../";
$extra_css = "shop.css"; 

require $path . 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo $path; ?>css/home.css">
<link rel="stylesheet" href="<?php echo $path; ?>css/memberchat.css">

<div class="hero-section">
    <div class="hero-slideshow">
        <div class="hero-slide active">
            <img src="../../images/products/jennie.png" alt="Slide 1" class="hero-image">
        </div>
        <div class="hero-slide">
            <img src="../../images/products/chanel.jpg" alt="Slide 2" class="hero-image">
        </div>
        <div class="hero-slide">
            <img src="../../images/products/chanel2.png" alt="Slide 3" class="hero-image">
        </div>
    </div>

    <div class="hero-overlay-content">
        <a href="shop.php" class="btn-hero-shopnow">Shop Now</a>
    </div>

    <button class="hero-arrow hero-arrow-left" aria-label="Previous slide">‹</button>
    <button class="hero-arrow hero-arrow-right" aria-label="Next slide">›</button>

    <div class="hero-dots">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
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

<?php

if (!isset($homeVideo)) {
    $stmt_home_video = $pdo->query("SELECT video_id FROM youtube_videos ORDER BY id DESC LIMIT 1");
    $homeVideo = $stmt_home_video->fetch();
}
?>

<?php if ($homeVideo): ?>
    <section class="home-video" style="margin: 40px auto; max-width: 800px; padding: 0 20px;">
        <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
            <iframe 
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                src="https://www.youtube.com/embed/<?php echo htmlspecialchars($homeVideo['video_id']); ?>?autoplay=1&mute=1&controls=1" 
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen>
            </iframe>
        </div>
    </section>
<?php endif; ?>

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

<script src="<?php echo $path; ?>js/hero_slider.js"></script>

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
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'message=' + encodeURIComponent(text)
                }).then(r => r.json())
                .then(res => {
                    sendBtn.disabled = false;
                    if (res.status === 'success') {
                        input.value = '';
                        fetchMessages();
                    }
                }).catch(() => {
                    sendBtn.disabled = false;
                });
        }

        fab.addEventListener('click', function() {
            toggleChat(true);
        });
        closeBtn.addEventListener('click', function() {
            toggleChat(false);
        });
        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    })();
</script>