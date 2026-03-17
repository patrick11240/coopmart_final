<?php
// mail_config.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Server settings with YOUR credentials
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = 'brucaltattet@gmail.com'; // Your Gmail
        $this->mail->Password   = 'fircxbxscfbkqzns';       // Your app password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
        $this->mail->CharSet    = 'UTF-8';
        
        // Sender info
        $this->mail->setFrom('brucaltattet@gmail.com', 'Coopmart Store');
        $this->mail->isHTML(true);
    }
    
    public function sendOTP($to, $toName, $otp) {
        try {
            $this->mail->addAddress($to, $toName);
            $this->mail->Subject = 'Coopmart - Email Verification OTP';
            
            $body = $this->getOTPEmailTemplate($toName, $otp);
            
            $this->mail->Body = $body;
            $this->mail->AltBody = "Coopmart Email Verification\n\nHello $toName,\n\nYour OTP is: $otp\n\nThis OTP will expire in 10 minutes.\n\nIf you didn't create an account, please ignore this email.";
            
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            return false;
        }
    }
    
    private function getOTPEmailTemplate($name, $otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 10px; overflow: hidden; }
                .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px; }
                .otp-box { 
                    background-color: #f8f9fa; 
                    border: 2px dashed #28a745; 
                    padding: 25px; 
                    text-align: center; 
                    font-size: 36px; 
                    font-weight: bold; 
                    letter-spacing: 8px; 
                    margin: 25px 0; 
                    border-radius: 8px;
                    font-family: 'Courier New', monospace;
                }
                .note { 
                    background-color: #fff3cd; 
                    border-left: 4px solid #ffc107; 
                    padding: 15px; 
                    margin: 20px 0;
                    border-radius: 4px;
                }
                .footer { 
                    text-align: center; 
                    margin-top: 30px; 
                    color: #6c757d; 
                    font-size: 12px; 
                    padding: 20px;
                    border-top: 1px solid #dee2e6;
                }
                .warning { color: #dc3545; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2 style='margin:0;'>📧 Coopmart Email Verification</h2>
                </div>
                <div class='content'>
                    <h3>Hello $name,</h3>
                    <p>Welcome to Coopmart! To complete your registration and secure your account, please verify your email address using the OTP below:</p>
                    
                    <div class='otp-box'>$otp</div>
                    
                    <div class='note'>
                        <p><strong>⏰ Valid for 10 minutes only!</strong><br>
                        Enter this code on the verification page to activate your account.</p>
                    </div>
                    
                    <p><span class='warning'>⚠️ Security Alert:</span> Never share this OTP with anyone. Coopmart staff will never ask for your verification code.</p>
                    
                    <p>If you didn't request this verification, please ignore this email or contact our support team.</p>
                    
                    <p>Best regards,<br>
                    <strong>The Coopmart Team</strong></p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Coopmart Store. All rights reserved.<br>
                    This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>