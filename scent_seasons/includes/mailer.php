<?php
// includes/mailer.php - 调试增强版

// 引入 PHPMailer
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * 发送订单收据邮件 - 增强调试版
 */
function send_order_receipt($to_email, $to_name, $order_data) {
    error_log("========================================");
    error_log("📧 STARTING EMAIL SEND PROCESS");
    error_log("To: $to_email");
    error_log("Name: $to_name");
    error_log("Order ID: " . $order_data['order_id']);
    error_log("========================================");
    
    $mail = new PHPMailer(true);

    try {
        // 服务器设置
        $mail->SMTPDebug = 2; // 开启详细调试 (0=关闭, 1=客户端, 2=客户端+服务器)
        $mail->Debugoutput = function($str, $level) {
            error_log("PHPMailer DEBUG [$level]: $str");
        };
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gansq-wm23@student.tarc.edu.my';
        $mail->Password   = 'kmziylissuqjtcwr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        error_log("✓ SMTP configuration set");

        // 发件人
        $mail->setFrom('gansq-wm23@student.tarc.edu.my', 'Scent Seasons');
        error_log("✓ From address set");
        
        // 收件人
        $mail->addAddress($to_email, $to_name ?: 'Valued Customer');
        error_log("✓ Recipient added: $to_email");

        // 邮件内容
        $mail->isHTML(true);
        $mail->Subject = 'Payment Receipt - Order #' . $order_data['order_id'] . ' - Scent Seasons';
        error_log("✓ Subject set: " . $mail->Subject);
        
        $mail->Body    = get_receipt_email_template($to_name, $order_data);
        $mail->AltBody = get_receipt_plain_text($to_name, $order_data);
        error_log("✓ Email body generated (HTML: " . strlen($mail->Body) . " chars)");

        // 尝试发送
        error_log("⏳ Attempting to send email...");
        $send_result = $mail->send();
        
        if ($send_result) {
            error_log("✅ SUCCESS: Email sent to $to_email for Order #{$order_data['order_id']}");
            error_log("========================================");
            return true;
        } else {
            error_log("❌ SEND FAILED: Result was false");
            error_log("========================================");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("❌ EXCEPTION in send_order_receipt()");
        error_log("Error Message: " . $e->getMessage());
        error_log("PHPMailer Error Info: " . $mail->ErrorInfo);
        error_log("Exception Type: " . get_class($e));
        error_log("Stack Trace: " . $e->getTraceAsString());
        error_log("========================================");
        return false;
    }
}

/**
 * 发送 OTP 邮件
 */
function send_otp_email($to_email, $to_name, $otp_code) {
    $mail = new PHPMailer(true);

    try {
        // 服务器设置
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gansq-wm23@student.tarc.edu.my';
        $mail->Password   = 'kmziylissuqjtcwr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // 发件人
        $mail->setFrom('gansq-wm23@student.tarc.edu.my', 'Scent Seasons');
        
        // 收件人
        $mail->addAddress($to_email, $to_name ?: 'User');

        // 邮件内容
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Verification Code - Scent Seasons';
        $mail->Body    = get_otp_email_template($to_name, $otp_code);
        $mail->AltBody = "Hello " . ($to_name ?: 'User') . ",\n\nYour password reset verification code is: $otp_code\n\nThis code will expire in 15 minutes.\n\nIf you didn't request this, please ignore this email.\n\nBest regards,\nScent Seasons Team";

        $mail->send();
        error_log("OTP Email sent successfully to: $to_email");
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * OTP 邮件模板
 */
function get_otp_email_template($name, $otp) {
    if (empty($name)) {
        $name = 'User';
    }
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                line-height: 1.6; 
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f5f5f7;
            }
            .container { 
                max-width: 600px; 
                margin: 0 auto; 
                padding: 20px;
                background: #f9f9f9;
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; 
                padding: 30px; 
                text-align: center; 
                border-radius: 10px 10px 0 0;
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px; 
            }
            .content { 
                background: white; 
                padding: 30px; 
                border-radius: 0 0 10px 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .otp-box { 
                background: #f0f4ff; 
                border: 2px dashed #667eea; 
                padding: 20px; 
                text-align: center; 
                margin: 20px 0;
                border-radius: 10px;
            }
            .otp-code { 
                font-size: 36px; 
                font-weight: bold; 
                color: #667eea; 
                letter-spacing: 8px;
                margin: 10px 0;
                font-family: 'Courier New', monospace;
            }
            .info { 
                background: #fff3cd; 
                border-left: 4px solid #ffc107; 
                padding: 15px; 
                margin: 20px 0;
            }
            .footer { 
                text-align: center; 
                margin-top: 20px; 
                color: #666; 
                font-size: 12px; 
                padding-top: 20px;
                border-top: 1px solid #e0e0e0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌸 Scent Seasons</h1>
                <p style='margin: 5px 0 0 0;'>Password Reset Request</p>
            </div>
            <div class='content'>
                <p style='font-size: 16px;'>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>We received a request to reset your password for your Scent Seasons account. Use the OTP code below to proceed:</p>
                
                <div class='otp-box'>
                    <p style='margin: 0; color: #666; font-size: 14px;'>Your OTP Code</p>
                    <p class='otp-code'>" . htmlspecialchars($otp) . "</p>
                    <p style='margin: 0; color: #e74c3c; font-size: 14px; font-weight: bold;'>⏰ Valid for 15 minutes</p>
                </div>
                
                <div class='info'>
                    <p style='margin: 0; font-weight: bold;'>🔒 Important Security Information:</p>
                    <ul style='margin: 10px 0 0 20px; padding: 0;'>
                        <li>This code will expire in 15 minutes</li>
                        <li>Don't share this code with anyone</li>
                        <li>Scent Seasons will never ask for your OTP via phone</li>
                        <li>If you didn't request this, please ignore this email and secure your account</li>
                    </ul>
                </div>
                
                <p style='margin-top: 30px;'>Best regards,<br><strong>The Scent Seasons Team</strong></p>
            </div>
            <div class='footer'>
                <p>This is an automated email. Please do not reply.</p>
                <p>&copy; " . date('Y') . " Scent Seasons. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * 生成收据邮件 HTML 模板
 */
function get_receipt_email_template($name, $order) {
    if (empty($name)) {
        $name = 'Valued Customer';
    }
    
    // 格式化日期
    $order_date = date('F d, Y', strtotime($order['order_date']));
    
    // 生成商品列表 HTML
    $items_html = '';
    foreach ($order['items'] as $item) {
        $subtotal = $item['price_each'] * $item['quantity'];
        $items_html .= "
        <tr>
            <td style='padding: 15px; border-bottom: 1px solid #eee;'>
                <strong>" . htmlspecialchars($item['product_name']) . "</strong>
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: center;'>
                " . $item['quantity'] . "
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: right;'>
                RM " . number_format($item['price_each'], 2) . "
            </td>
            <td style='padding: 15px; border-bottom: 1px solid #eee; text-align: right;'>
                <strong>RM " . number_format($subtotal, 2) . "</strong>
            </td>
        </tr>";
    }
    
    // 支付方式显示
    $payment_method = !empty($order['transaction_id']) ? 
        "PayPal (Transaction: " . htmlspecialchars($order['transaction_id']) . ")" : 
        "Pending Payment";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
                line-height: 1.6; 
                color: #333;
                margin: 0;
                padding: 0;
                background-color: #f5f5f7;
            }
            .container { 
                max-width: 650px; 
                margin: 0 auto; 
                padding: 20px;
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
                border-radius: 10px 10px 0 0;
            }
            .header h1 { 
                margin: 0 0 10px 0; 
                font-size: 32px; 
            }
            .success-badge {
                background: rgba(255, 255, 255, 0.2);
                border: 2px solid white;
                display: inline-block;
                padding: 8px 20px;
                border-radius: 20px;
                font-size: 14px;
                font-weight: bold;
                margin-top: 10px;
            }
            .content { 
                background: white; 
                padding: 40px 30px; 
                border-radius: 0 0 10px 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .order-info {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
            }
            .order-info-row {
                padding: 8px 0;
                border-bottom: 1px solid #e0e0e0;
            }
            .order-info-row:last-child {
                border-bottom: none;
            }
            .label {
                color: #666;
                font-weight: 500;
                display: inline-block;
                width: 150px;
            }
            .value {
                color: #333;
                font-weight: 600;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0;
                background: white;
            }
            th { 
                background: #667eea; 
                color: white; 
                padding: 15px; 
                text-align: left;
                font-weight: 600;
            }
            th:nth-child(2), th:nth-child(3), th:nth-child(4) {
                text-align: right;
            }
            td { 
                padding: 15px; 
                border-bottom: 1px solid #eee; 
            }
            .total-section {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-top: 20px;
            }
            .total-row {
                padding: 10px 0;
                font-size: 16px;
            }
            .total-row.final {
                border-top: 2px solid #667eea;
                margin-top: 10px;
                padding-top: 15px;
                font-size: 24px;
                font-weight: bold;
                color: #667eea;
            }
            .info-box { 
                background: #e8f4f8; 
                border-left: 4px solid #0071e3; 
                padding: 15px; 
                margin: 20px 0;
                border-radius: 4px;
            }
            .footer { 
                text-align: center; 
                margin-top: 30px; 
                color: #666; 
                font-size: 13px; 
                padding-top: 20px;
                border-top: 1px solid #e0e0e0;
            }
            .btn {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 12px 30px;
                text-decoration: none;
                border-radius: 25px;
                font-weight: 600;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🌸 Scent Seasons</h1>
                <div class='success-badge'>✓ PAYMENT SUCCESSFUL</div>
            </div>
            
            <div class='content'>
                <h2 style='color: #667eea; margin-top: 0;'>Thank You for Your Purchase!</h2>
                <p style='font-size: 16px;'>Dear <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Your payment has been successfully processed. Here's your order receipt:</p>
                
                <div class='order-info'>
                    <div class='order-info-row'>
                        <span class='label'>Order Number:</span>
                        <span class='value'>#" . $order['order_id'] . "</span>
                    </div>
                    <div class='order-info-row'>
                        <span class='label'>Order Date:</span>
                        <span class='value'>" . $order_date . "</span>
                    </div>
                    <div class='order-info-row'>
                        <span class='label'>Payment Method:</span>
                        <span class='value'>" . $payment_method . "</span>
                    </div>
                    <div class='order-info-row'>
                        <span class='label'>Status:</span>
                        <span class='value' style='color: #28a745;'>✓ " . strtoupper($order['status']) . "</span>
                    </div>
                </div>
                
                <h3 style='color: #333; margin-top: 30px;'>Order Details</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style='text-align: center;'>Qty</th>
                            <th style='text-align: right;'>Unit Price</th>
                            <th style='text-align: right;'>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $items_html . "
                    </tbody>
                </table>
                
                <div class='total-section'>
                    <div class='total-row final'>
                        <span>Total Amount Paid: RM " . number_format($order['total_amount'], 2) . "</span>
                    </div>
                </div>
                
                <div class='info-box'>
                    <p style='margin: 0; font-weight: bold;'>📦 What's Next?</p>
                    <ul style='margin: 10px 0 0 20px; padding: 0;'>
                        <li>Your order is being processed</li>
                        <li>You'll receive shipping updates via email</li>
                        <li>Track your order in your account dashboard</li>
                        <li>Expected delivery: 3-5 business days</li>
                    </ul>
                </div>
                
                <p style='margin-top: 30px; color: #666;'>
                    If you have any questions about your order, please contact our customer support at 
                    <a href='mailto:gansq-wm23@student.tarc.edu.my' style='color: #667eea;'>gansq-wm23@student.tarc.edu.my</a>
                </p>
                
                <p style='margin-top: 30px;'>
                    Best regards,<br>
                    <strong>The Scent Seasons Team</strong>
                </p>
            </div>
            
            <div class='footer'>
                <p>This is an automated receipt. Please save this email for your records.</p>
                <p>&copy; " . date('Y') . " Scent Seasons. All rights reserved.</p>
                <p style='font-size: 11px; color: #999; margin-top: 10px;'>
                    You received this email because you made a purchase at Scent Seasons.
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * 生成纯文本版本收据
 */
function get_receipt_plain_text($name, $order) {
    $text = "SCENT SEASONS - PAYMENT RECEIPT\n";
    $text .= "================================\n\n";
    $text .= "Dear " . ($name ?: 'Valued Customer') . ",\n\n";
    $text .= "Thank you for your purchase! Your payment has been successfully processed.\n\n";
    
    $text .= "ORDER INFORMATION:\n";
    $text .= "------------------\n";
    $text .= "Order Number: #" . $order['order_id'] . "\n";
    $text .= "Order Date: " . date('F d, Y', strtotime($order['order_date'])) . "\n";
    $text .= "Status: " . strtoupper($order['status']) . "\n";
    
    if (!empty($order['transaction_id'])) {
        $text .= "Transaction ID: " . $order['transaction_id'] . "\n";
    }
    
    $text .= "\nORDER DETAILS:\n";
    $text .= "--------------\n";
    
    foreach ($order['items'] as $item) {
        $subtotal = $item['price_each'] * $item['quantity'];
        $text .= $item['product_name'] . " x " . $item['quantity'] . " - RM " . number_format($subtotal, 2) . "\n";
    }
    
    $text .= "\nTOTAL AMOUNT PAID: RM " . number_format($order['total_amount'], 2) . "\n\n";
    
    $text .= "Your order is being processed and you'll receive shipping updates via email.\n\n";
    $text .= "Best regards,\n";
    $text .= "The Scent Seasons Team\n";
    
    return $text;
}
?>