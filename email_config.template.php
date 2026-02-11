<?php
/**
 * Email Configuration Template
 * 
 * Copy this file to 'email_config.php' and update with your actual credentials.
 * Add 'email_config.php' to .gitignore to keep credentials secure!
 * 
 * Then include this file in orderprocess.php instead of hardcoding credentials.
 */

// SMTP Server Settings
define('SMTP_HOST', 'smtp.gmail.com'); // Change to your SMTP server
define('SMTP_PORT', 587); // 587 for TLS, 465 for SSL
define('SMTP_ENCRYPTION', 'tls'); // 'tls' or 'ssl'

// Email Account Credentials
define('SMTP_USERNAME', 'your-email@gmail.com'); // Your email address
define('SMTP_PASSWORD', 'your-app-password'); // Your email password or app password

// From Email Settings
define('MAIL_FROM_ADDRESS', 'your-email@gmail.com'); // Sender email address
define('MAIL_FROM_NAME', 'Ticket System'); // Sender name

// Optional: BCC for order notifications
define('ADMIN_EMAIL', 'admin@example.com'); // Admin email for order copies

?>
