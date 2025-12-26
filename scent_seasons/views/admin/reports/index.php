<?php
session_start();
require '../../../config/database.php';
require '../../../includes/functions.php';
require_admin();


$popular_sql = "SELECT p.product_id, p.name, p.price, p.image_path, c.category_name,
                        COALESCE(SUM(oi.quantity), 0) as total_sold,
                        COUNT(DISTINCT oi.order_id) as order_count
                 FROM products p 
                 LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                 LEFT JOIN categories c ON p.category_id = c.category_id
                 WHERE p.is_deleted = 0 
                 GROUP BY p.product_id, p.name, p.price, p.image_path, c.category_name
                 ORDER BY total_sold DESC, order_count DESC
                 LIMIT 10";
$popular_products = $pdo->query($popular_sql)->fetchAll();


$unpopular_sql = "SELECT p.product_id, p.name, p.price, p.image_path, c.category_name,
                         COALESCE(SUM(oi.quantity), 0) as total_sold,
                         COUNT(DISTINCT oi.order_id) as order_count
                  FROM products p 
                  LEFT JOIN order_items oi ON p.product_id = oi.product_id 
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.is_deleted = 0 
                  GROUP BY p.product_id, p.name, p.price, p.image_path, c.category_name
                  HAVING total_sold = 0 OR total_sold <= 2
                  ORDER BY total_sold ASC, p.product_id ASC
                  LIMIT 10";
$unpopular_products = $pdo->query($unpopular_sql)->fetchAll();


$high_rated_sql = "SELECT p.product_id, p.name, p.price, p.image_path, c.category_name,
                           COALESCE(AVG(r.rating), 0) as avg_rating,
                           COUNT(r.review_id) as review_count
                    FROM products p 
                    LEFT JOIN reviews r ON p.product_id = r.product_id 
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.is_deleted = 0 
                    GROUP BY p.product_id, p.name, p.price, p.image_path, c.category_name
                    HAVING avg_rating >= 4.0 AND review_count >= 2
                    ORDER BY avg_rating DESC, review_count DESC
                    LIMIT 10";
$high_rated_products = $pdo->query($high_rated_sql)->fetchAll();

$low_rated_sql = "SELECT p.product_id, p.name, p.price, p.image_path, c.category_name,
                         COALESCE(AVG(r.rating), 0) as avg_rating,
                         COUNT(r.review_id) as review_count
                  FROM products p 
                  LEFT JOIN reviews r ON p.product_id = r.product_id 
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  WHERE p.is_deleted = 0 
                  GROUP BY p.product_id, p.name, p.price, p.image_path, c.category_name
                  HAVING (avg_rating < 3.0 AND review_count >= 1) OR (review_count = 0)
                  ORDER BY avg_rating ASC, review_count ASC
                  LIMIT 10";
$low_rated_products = $pdo->query($low_rated_sql)->fetchAll();

function render_stars($rating) {
    $stars = "";
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full_stars) {
            $stars .= "★";
        } elseif ($i == $full_stars + 1 && $half_star) {
            $stars .= "½";
        } else {
            $stars .= "☆";
        }
    }
    return "<span style='color:#ff9500;'>$stars</span> " . number_format($rating, 1);
}

$page_title = "Product Reports";
$path = "../../../";
$extra_css = "admin.css";

require $path . 'includes/header.php';
?>

<style>
    .chart-container {
        background: #fff;
        padding: 32px;
        border-radius: 18px;
        margin-bottom: 40px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
    }
    .chart-wrapper {
        position: relative;
        height: 600px;
        margin-top: 20px;
        width: 100%;
    }
    .chart-wrapper canvas {
        width: 100% !important;
        height: 600px !important;
    }
    .chart-title {
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #1d1d1f;
        text-align: center;
    }
    .chart-legend {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-top: 25px;
        flex-wrap: wrap;
        padding-top: 20px;
        border-top: 1px solid #e5e5e7;
    }
    .chart-legend-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 15px;
        font-weight: 500;
        color: #1d1d1f;
    }
    .chart-legend-color {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
</style>

<h2>Product Reports</h2>
<p style="color: #86868b; margin-bottom: 30px;">Analyze product performance, sales, and customer ratings.</p>


<div style="margin-bottom: 50px;">
    <h3 style="color: #30d158; margin-bottom: 20px;">⭐ Popular Products (Top 10 by Sales)</h3>
    <?php if (empty($popular_products)): ?>
        <p class="text-gray">No popular products found.</p>
    <?php else: ?>
   
        <div class="chart-container">
            <div class="chart-wrapper">
                <canvas id="popularChart"></canvas>
            </div>
        </div>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Total Sold</th>
                    <th>Orders</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($popular_products as $p): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../../images/products/<?php echo htmlspecialchars($p['image_path']); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"
                                     onerror="this.src='../../../images/products/default_product.jpg'">
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($p['price'], 2); ?></td>
                        <td><strong style="color: #30d158;"><?php echo $p['total_sold']; ?></strong> units</td>
                        <td><?php echo $p['order_count']; ?> orders</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


<div style="margin-bottom: 50px;">
    <h3 style="color: #ff3b30; margin-bottom: 20px;">📉 Unpopular Products (Low or No Sales)</h3>
    <?php if (empty($unpopular_products)): ?>
        <p class="text-gray">No unpopular products found. All products are selling well!</p>
    <?php else: ?>

        <div class="chart-container">
            <div class="chart-wrapper">
                <canvas id="unpopularChart"></canvas>
            </div>
        </div>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Total Sold</th>
                    <th>Orders</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($unpopular_products as $p): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../../images/products/<?php echo htmlspecialchars($p['image_path']); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"
                                     onerror="this.src='../../../images/products/default_product.jpg'">
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($p['price'], 2); ?></td>
                        <td><strong style="color: #ff3b30;"><?php echo $p['total_sold']; ?></strong> units</td>
                        <td><?php echo $p['order_count']; ?> orders</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


<div style="margin-bottom: 50px;">
    <h3 style="color: #30d158; margin-bottom: 20px;">⭐ High-Rated Products (Rating ≥ 4.0)</h3>
    <?php if (empty($high_rated_products)): ?>
        <p class="text-gray">No high-rated products found.</p>
    <?php else: ?>
   
        <div class="chart-container">
            <div class="chart-wrapper">
                <canvas id="highRatedChart"></canvas>
            </div>
        </div>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Average Rating</th>
                    <th>Reviews</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($high_rated_products as $p): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../../images/products/<?php echo htmlspecialchars($p['image_path']); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"
                                     onerror="this.src='../../../images/products/default_product.jpg'">
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($p['price'], 2); ?></td>
                        <td><?php echo render_stars($p['avg_rating']); ?></td>
                        <td><strong style="color: #30d158;"><?php echo $p['review_count']; ?></strong> reviews</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


<div style="margin-bottom: 50px;">
    <h3 style="color: #ff3b30; margin-bottom: 20px;">📉 Low-Rated Products (Rating < 3.0 or No Reviews)</h3>
    <?php if (empty($low_rated_products)): ?>
        <p class="text-gray">No low-rated products found. All products have good ratings!</p>
    <?php else: ?>
    
        <div class="chart-container">
            <div class="chart-wrapper">
                <canvas id="lowRatedChart"></canvas>
            </div>
        </div>
        <table class="table-list">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Average Rating</th>
                    <th>Reviews</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_rated_products as $p): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="../../../images/products/<?php echo htmlspecialchars($p['image_path']); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;"
                                     onerror="this.src='../../../images/products/default_product.jpg'">
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($p['category_name'] ?? 'N/A'); ?></td>
                        <td>$<?php echo number_format($p['price'], 2); ?></td>
                        <td>
                            <?php if ($p['review_count'] > 0): ?>
                                <?php echo render_stars($p['avg_rating']); ?>
                            <?php else: ?>
                                <span style="color: #86868b;">No reviews</span>
                            <?php endif; ?>
                        </td>
                        <td><strong style="color: #ff3b30;"><?php echo $p['review_count']; ?></strong> reviews</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>

(function() {
    'use strict';
    
    
    const ChartHelper = {

        colors: {
            green: { fill: 'rgba(48, 209, 88, 0.7)', stroke: 'rgba(48, 209, 88, 1)' },
            blue: { fill: 'rgba(0, 113, 227, 0.7)', stroke: 'rgba(0, 113, 227, 1)' },
            red: { fill: 'rgba(255, 59, 48, 0.7)', stroke: 'rgba(255, 59, 48, 1)' },
            orange: { fill: 'rgba(255, 149, 0, 0.7)', stroke: 'rgba(255, 149, 0, 1)' },
            purple: { fill: 'rgba(142, 68, 173, 0.7)', stroke: 'rgba(142, 68, 173, 1)' }
        },
        
    
        drawBarChart: function(canvasId, data, options) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
 
            const scale = window.devicePixelRatio || 1;
            const width = canvas.width / scale;
            const height = canvas.height / scale;
            
    
            ctx.clearRect(0, 0, width, height);
            
            const padding = { top: 70, right: 50, bottom: 100, left: 80 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            
            const labels = data.labels || [];
            const datasets = data.datasets || [];
            let maxValue = options.maxValue;
            if (!maxValue && datasets.length > 0) {
                const allValues = [];
                datasets.forEach(function(dataset) {
                    allValues.push.apply(allValues, dataset.data);
                });
                maxValue = Math.max.apply(Math, allValues);
            }
            maxValue = maxValue || 10;
     
            maxValue = Math.ceil(maxValue / 5) * 5;
            
         
            const gradient = ctx.createLinearGradient(0, padding.top, 0, padding.top + chartHeight);
            gradient.addColorStop(0, '#fbfbfd');
            gradient.addColorStop(1, '#ffffff');
            ctx.fillStyle = gradient;
            ctx.fillRect(padding.left, padding.top, chartWidth, chartHeight);
            
           
            ctx.strokeStyle = '#d2d2d7';
            ctx.lineWidth = 2;
            ctx.strokeRect(padding.left, padding.top, chartWidth, chartHeight);
            
        
            ctx.strokeStyle = '#e5e5e7';
            ctx.lineWidth = 1;
            const gridLines = 6;
            for (let i = 0; i <= gridLines; i++) {
                const y = padding.top + (chartHeight / gridLines) * i;
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(padding.left + chartWidth, y);
                ctx.stroke();
                
       
                ctx.fillStyle = '#6e6e73';
                ctx.font = 'bold 14px -apple-system, BlinkMacSystemFont, sans-serif';
                ctx.textAlign = 'right';
                const value = maxValue - (maxValue / gridLines) * i;
                ctx.fillText(Math.round(value).toString(), padding.left - 25, y + 5);
            }
            
           
            if (options.yAxisLabel) {
                ctx.save();
                ctx.fillStyle = '#86868b';
                ctx.font = '13px -apple-system, BlinkMacSystemFont, sans-serif';
                ctx.textAlign = 'center';
                ctx.translate(35, padding.top + chartHeight / 2);
                ctx.rotate(-Math.PI / 2);
                ctx.fillText(options.yAxisLabel, 0, 0);
                ctx.restore();
            }
            
           
            const barGroupWidth = chartWidth / labels.length * 0.75;
            const barSpacing = chartWidth / labels.length;
            const datasetCount = datasets.length;
            const barWidth = (barGroupWidth / datasetCount) * 0.85;
            const barGap = (barGroupWidth / datasetCount) * 0.15;
            
            datasets.forEach(function(dataset, datasetIndex) {
               
                const barGradient = ctx.createLinearGradient(0, padding.top + chartHeight, 0, padding.top);
                barGradient.addColorStop(0, dataset.color.fill);
                barGradient.addColorStop(1, dataset.color.stroke);
                
                ctx.fillStyle = barGradient;
                ctx.strokeStyle = dataset.color.stroke;
                ctx.lineWidth = 2;
                
                labels.forEach(function(label, index) {
                    const value = dataset.data[index] || 0;
                    const barHeight = (value / maxValue) * chartHeight;
                    const x = padding.left + (barSpacing * index) + (barSpacing - barGroupWidth) / 2 + 
                              (barGroupWidth / datasetCount) * datasetIndex + barGap / 2;
                    const y = padding.top + chartHeight - barHeight;
                    
                   
                    const cornerRadius = 6;
                    ctx.beginPath();
                    ctx.moveTo(x + cornerRadius, y);
                    ctx.lineTo(x + barWidth - cornerRadius, y);
                    ctx.quadraticCurveTo(x + barWidth, y, x + barWidth, y + cornerRadius);
                    ctx.lineTo(x + barWidth, y + barHeight);
                    ctx.lineTo(x, y + barHeight);
                    ctx.lineTo(x, y + cornerRadius);
                    ctx.quadraticCurveTo(x, y, x + cornerRadius, y);
                    ctx.closePath();
                    ctx.fill();
                    ctx.stroke();
                    
                    
                    if (value > 0) {
                        ctx.fillStyle = '#1d1d1f';
                        ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, sans-serif';
                        ctx.textAlign = 'center';
                    
                        const labelY = y - 15;
                        ctx.fillText(value.toString(), x + barWidth / 2, labelY);
                        ctx.fillStyle = barGradient;
                    }
                });
            });
            
          
            ctx.fillStyle = '#1d1d1f';
            ctx.font = '13px -apple-system, BlinkMacSystemFont, sans-serif';
            ctx.textAlign = 'center';
            labels.forEach(function(label, index) {
                const x = padding.left + (barSpacing * index) + barSpacing / 2;
                const y = height - padding.bottom + 50;
                
             
                ctx.save();
                ctx.translate(x, y);
                ctx.rotate(-Math.PI / 4);
                const displayLabel = label.length > 20 ? label.substring(0, 20) + '...' : label;
              
                ctx.fillText(displayLabel, 0, 10);
                ctx.restore();
            });
            
           
            if (options.title) {
                ctx.fillStyle = '#1d1d1f';
                ctx.font = 'bold 18px -apple-system, BlinkMacSystemFont, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(options.title, width / 2, 50);
            }
        },
        
        
        drawLineChart: function(canvasId, data, options) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
          
            const scale = window.devicePixelRatio || 1;
            const width = canvas.width / scale;
            const height = canvas.height / scale;
            
            const padding = { top: 90, right: 50, bottom: 120, left: 100 };
            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            
            const labels = data.labels || [];
            const lineData = data.data || [];
            const maxValue = options.maxValue || Math.max(...lineData);
            const minValue = options.minValue || 0;
            const valueRange = maxValue - minValue || 1;
            
            const pointSpacing = chartWidth / labels.length;
            
            ctx.strokeStyle = options.color.stroke;
            ctx.fillStyle = options.color.fill;
            ctx.lineWidth = 4;
            
            
            ctx.beginPath();
            lineData.forEach(function(value, index) {
                const x = padding.left + (pointSpacing * index) + pointSpacing / 2;
                const y = padding.top + chartHeight - ((value - minValue) / valueRange) * chartHeight;
                
                if (index === 0) {
                    ctx.moveTo(x, y);
                } else {
              
                    const prevX = padding.left + (pointSpacing * (index - 1)) + pointSpacing / 2;
                    const prevY = padding.top + chartHeight - ((lineData[index - 1] - minValue) / valueRange) * chartHeight;
                    const cpx = (prevX + x) / 2;
                    ctx.quadraticCurveTo(cpx, prevY, x, y);
                }
            });
            ctx.stroke();
            
           
            lineData.forEach(function(value, index) {
                const x = padding.left + (pointSpacing * index) + pointSpacing / 2;
                const y = padding.top + chartHeight - ((value - minValue) / valueRange) * chartHeight;
                
             
                ctx.beginPath();
                ctx.arc(x, y, 6, 0, Math.PI * 2);
                ctx.fillStyle = '#ffffff';
                ctx.fill();
                ctx.strokeStyle = options.color.stroke;
                ctx.lineWidth = 3;
                ctx.stroke();
                
               
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fillStyle = options.color.fill;
                ctx.fill();
                
               
                ctx.fillStyle = '#1d1d1f';
                ctx.font = 'bold 12px -apple-system, BlinkMacSystemFont, sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(value.toString(), x, y - 20);
            });
        }
    };
    
    
    $(document).ready(function() {
      
        const popularData = <?php echo json_encode($popular_products); ?>;
        const unpopularData = <?php echo json_encode($unpopular_products); ?>;
        const highRatedData = <?php echo json_encode($high_rated_products); ?>;
        const lowRatedData = <?php echo json_encode($low_rated_products); ?>;
        
       
        if (popularData.length > 0) {
            const canvas = document.getElementById('popularChart');
            if (canvas) {
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * window.devicePixelRatio;
                canvas.height = 600 * window.devicePixelRatio;
                canvas.style.width = rect.width + 'px';
                canvas.style.height = '600px';
                const ctx = canvas.getContext('2d');
                ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
                
                const labels = popularData.map(function(p) {
                    return p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name;
                });
                const soldData = popularData.map(function(p) { return parseInt(p.total_sold); });
                const orderData = popularData.map(function(p) { return parseInt(p.order_count); });
                const maxValue = Math.max(Math.max(...soldData), Math.max(...orderData));
                
                ChartHelper.drawBarChart('popularChart', {
                    labels: labels,
                    datasets: [
                        { data: soldData, color: ChartHelper.colors.green, label: 'Units Sold' },
                        { data: orderData, color: ChartHelper.colors.blue, label: 'Number of Orders' }
                    ]
                }, {
                    title: 'Top 10 Products by Sales Volume',
                    maxValue: maxValue,
                    yAxisLabel: 'Sales Volume'
                });
                
                
                const legendHtml = '<div class="chart-legend">' +
                    '<div class="chart-legend-item">' +
                    '<div class="chart-legend-color" style="background: ' + ChartHelper.colors.green.fill + ';"></div>' +
                    '<span>Units Sold</span>' +
                    '</div>' +
                    '<div class="chart-legend-item">' +
                    '<div class="chart-legend-color" style="background: ' + ChartHelper.colors.blue.fill + ';"></div>' +
                    '<span>Number of Orders</span>' +
                    '</div>' +
                    '</div>';
                $(canvas).parent().append(legendHtml);
            }
        }
        
        
        if (unpopularData.length > 0) {
            const canvas = document.getElementById('unpopularChart');
            if (canvas) {
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * window.devicePixelRatio;
                canvas.height = 600 * window.devicePixelRatio;
                canvas.style.width = rect.width + 'px';
                canvas.style.height = '600px';
                const ctx = canvas.getContext('2d');
                ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
                
                const labels = unpopularData.map(function(p) {
                    return p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name;
                });
                const soldData = unpopularData.map(function(p) { return parseInt(p.total_sold); });
                const maxValue = Math.max(...soldData, 5);
                
                ChartHelper.drawBarChart('unpopularChart', {
                    labels: labels,
                    datasets: [
                        { data: soldData, color: ChartHelper.colors.red, label: 'Units Sold' }
                    ]
                }, {
                    title: 'Products with Low or No Sales',
                    maxValue: maxValue,
                    yAxisLabel: 'Units Sold'
                });
                
             
                const legendHtml = '<div class="chart-legend">' +
                    '<div class="chart-legend-item">' +
                    '<div class="chart-legend-color" style="background: ' + ChartHelper.colors.red.fill + ';"></div>' +
                    '<span>Units Sold</span>' +
                    '</div>' +
                    '</div>';
                $(canvas).parent().append(legendHtml);
            }
        }
        
        
        if (highRatedData.length > 0) {
            const canvas = document.getElementById('highRatedChart');
            if (canvas) {
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * window.devicePixelRatio;
                canvas.height = 600 * window.devicePixelRatio;
                canvas.style.width = rect.width + 'px';
                canvas.style.height = '600px';
                const ctx = canvas.getContext('2d');
                ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
                
                const labels = highRatedData.map(function(p) {
                    return p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name;
                });
                const ratingData = highRatedData.map(function(p) { return parseFloat(p.avg_rating); });
                const reviewData = highRatedData.map(function(p) { return parseInt(p.review_count); });
                
               
                ChartHelper.drawBarChart('highRatedChart', {
                    labels: labels,
                    datasets: [
                        { data: ratingData, color: ChartHelper.colors.green, label: 'Average Rating' }
                    ]
                }, {
                    title: 'High-Rated Products (Rating ≥ 4.0)',
                    maxValue: 5,
                    yAxisLabel: 'Rating (out of 5)'
                });
                
               
                ChartHelper.drawLineChart('highRatedChart', {
                    labels: labels,
                    data: reviewData
                }, {
                    color: ChartHelper.colors.orange,
                    maxValue: Math.max(...reviewData, 10),
                    minValue: 0
                });
                
             
                const legendHtml = '<div class="chart-legend">' +
                    '<div class="chart-legend-item">' +
                    '<div class="chart-legend-color" style="background: ' + ChartHelper.colors.green.fill + ';"></div>' +
                    '<span>Average Rating</span>' +
                    '</div>' +
                    '<div class="chart-legend-item">' +
                    '<div class="chart-legend-color" style="background: ' + ChartHelper.colors.orange.fill + ';"></div>' +
                    '<span>Number of Reviews</span>' +
                    '</div>' +
                    '</div>';
                $(canvas).parent().append(legendHtml);
            }
        }
        
      
        if (lowRatedData.length > 0) {
            const canvas = document.getElementById('lowRatedChart');
            if (canvas) {
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * window.devicePixelRatio;
                canvas.height = 600 * window.devicePixelRatio;
                canvas.style.width = rect.width + 'px';
                canvas.style.height = '600px';
                const ctx = canvas.getContext('2d');
                ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
                
                const labels = lowRatedData.map(function(p) {
                    return p.name.length > 20 ? p.name.substring(0, 20) + '...' : p.name;
                });
                const ratingData = lowRatedData.map(function(p) {
                    return p.review_count > 0 ? parseFloat(p.avg_rating) : 0;
                });
                const reviewData = lowRatedData.map(function(p) { return parseInt(p.review_count); });
                
              
                ChartHelper.drawBarChart('lowRatedChart', {
                    labels: labels,
                    datasets: [
                        { data: ratingData, color: ChartHelper.colors.red, label: 'Average Rating' }
                    ]
                }, {
                    title: 'Low-Rated Products (Rating < 3.0 or No Reviews)',
                    maxValue: 5,
                    yAxisLabel: 'Rating (out of 5)'
                });
                
              
                ChartHelper.drawLineChart('lowRatedChart', {
                    labels: labels,
                    data: reviewData
                }, {
                    color: ChartHelper.colors.purple,
                    maxValue: Math.max(...reviewData, 10),
                    minValue: 0
                });
                
            
                const legendHtml = '<div class="chart-legend">' +
                    '<div class="chart-legend-item">' +
                    '<div class="chart-legend-color" style="background: ' + ChartHelper.colors.red.fill + ';"></div>' +
                    '<span>Average Rating</span>' +
                    '</div>' +
                    '<div class="chart-legend-item">' +
                    '<div class="chart-legend-color" style="background: ' + ChartHelper.colors.purple.fill + ';"></div>' +
                    '<span>Number of Reviews</span>' +
                    '</div>' +
                    '</div>';
                $(canvas).parent().append(legendHtml);
            }
        }
        
      
        $(window).on('resize', function() {

            $('canvas[id$="Chart"]').each(function() {
                const canvas = this;
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * window.devicePixelRatio;
                canvas.height = 600 * window.devicePixelRatio;
                canvas.style.width = rect.width + 'px';
                canvas.style.height = '600px';
                const ctx = canvas.getContext('2d');
                ctx.scale(window.devicePixelRatio, window.devicePixelRatio);
            });
        });
    });
})();
</script>

<?php require $path . 'includes/footer.php'; ?>

