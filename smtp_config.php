<?php
/**
 * Cookie Cozy Bakery - SMTP Configuration
 * If you want real emails delivered to your real Gmail/Outlook inbox,
 * set 'enabled' => true and fill in your credentials below.
 * Otherwise, leave 'enabled' => false, and all emails will be saved to the sent_emails/ folder.
 */

return [
    'enabled' => false, // Set to true to send real emails to inbox
    'host' => 'smtp.gmail.com', // e.g. smtp.gmail.com
    'port' => 587,
    'username' => 'your_email@gmail.com',
    'password' => 'your_app_password', // Gmail App Password (16 digits)
    'from_email' => 'your_email@gmail.com'
];
