<?php
// includes/mailer.php - 根据你的实际 PHPMailer 位置

// 直接引入 PHPMailer 文件（不在 src 文件夹内）
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_otp_email($to_email, $to_name, $otp_code) {
    $mail = new PHPMailer(true);

    try {
        // 服务器设置
        $mail->SMTPDebug = 0;                           // 0=关闭调试输出
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'gansq-wm23@student.tarc.edu.my';
        $mail->Password   = 'kmziylissuqjtcwr';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // 使用 SSL
        $mail->Port       = 465;                          // SSL 端口
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
        // 临时调试 - 生产环境要删除
        // die("Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

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