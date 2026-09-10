<?php
/**
 * Cookie Cozy Bakery - New Member Registration & Welcome Email API
 * Endpoint: register_welcome.php
 * Handles new membership registration and sends a VIP welcome email with bonus points.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed. Please use POST.'
    ]);
    exit;
}

// 1. Parse Input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$name = isset($data['name']) && !empty($data['name']) ? trim($data['name']) : 'สมาชิกใหม่';
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '-';
$memberTier = isset($data['memberTier']) ? trim($data['memberTier']) : 'Silver Member';

// Validate Email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณาระบุอีเมลที่ถูกต้อง (Invalid email address)'
    ]);
    exit;
}

// 2. Generate Member ID & Bonus Points
$memberId = 'CCZ-' . date('Y') . '-' . strtoupper(substr(md5($email . time()), 0, 5));
$bonusPoints = 50;

// 3. Save to mock members database
$membersFile = __DIR__ . '/members.json';
$members = [];
if (file_exists($membersFile)) {
    $members = json_decode(file_get_contents($membersFile), true) ?: [];
}

$members[$email] = [
    'member_id' => $memberId,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'tier' => $memberTier,
    'points' => $bonusPoints,
    'registered_at' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];
@file_put_contents($membersFile, json_encode($members, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 4. Build VIP Welcome HTML Email Template
$emailBody = '<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ยินดีต้อนรับสู่ Cookie Club</title>
</head>
<body style="margin: 0; padding: 20px; background-color: #fdfaf6; font-family: \'Prompt\', \'Kanit\', Helvetica, Arial, sans-serif; color: #3a1a0c;">
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; border: 1px solid #ebd9c8; overflow: hidden; box-shadow: 0 10px 25px rgba(90,45,24,0.06);">
    <!-- Header -->
    <tr>
      <td align="center" style="padding: 28px 20px 20px; background: linear-gradient(135deg, #3a1a0c 0%, #5a2d18 100%);">
        <h1 style="margin: 0; color: #fdfaf6; font-size: 24px; letter-spacing: 1px;">COOKIE CLUB</h1>
        <p style="margin: 4px 0 0; color: #f1c982; font-size: 13px;">Official Membership Privilege • สิทธิพิเศษสำหรับสมาชิก</p>
      </td>
    </tr>

    <!-- Body Content -->
    <tr>
      <td style="padding: 30px 26px 20px;">
        <div style="text-align: center; margin-bottom: 20px;">
          <div style="font-size: 40px; margin-bottom: 6px;">🎉</div>
          <h2 style="margin: 0 0 6px; font-size: 22px; color: #5a2d18;">ยินดีต้อนรับคุณ ' . htmlspecialchars($name) . '!</h2>
          <p style="margin: 0; font-size: 14px; color: #7a6a5d;">การสมัครสมาชิก Cookie Club ของคุณเสร็จสมบูรณ์เรียบร้อยแล้ว</p>
        </div>

        <!-- Virtual VIP Card -->
        <div style="background: linear-gradient(135deg, #3a1a0c 0%, #6e381c 60%, #bf7739 100%); color: #ffffff; border-radius: 18px; padding: 22px 24px; box-shadow: 0 8px 20px rgba(90,45,24,0.25); margin: 20px 0 24px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <span style="font-size: 13px; font-weight: bold; letter-spacing: 1px; color: #f1c982;">COOKIE CLUB VIP PASS</span>
            <span style="background: rgba(255,255,255,0.2); padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold;">' . htmlspecialchars($memberTier) . '</span>
          </div>
          <div style="font-size: 20px; font-weight: bold; letter-spacing: 2px; margin-bottom: 16px; font-family: monospace;">' . $memberId . '</div>
          <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
              <td style="vertical-align: bottom;">
                <span style="font-size: 10px; color: #f1c982; display: block; text-transform: uppercase;">MEMBER NAME</span>
                <strong style="font-size: 15px; letter-spacing: 0.5px;">' . htmlspecialchars($name) . '</strong>
              </td>
              <td align="right" style="vertical-align: bottom;">
                <span style="font-size: 10px; color: #f1c982; display: block; text-transform: uppercase;">WELCOME BONUS</span>
                <strong style="font-size: 18px; color: #f1c982;">+' . $bonusPoints . ' pts</strong>
              </td>
            </tr>
          </table>
        </div>

        <!-- Points Alert Banner -->
        <div style="background: #fffbf5; border: 1.5px dashed #f1c982; border-radius: 14px; padding: 14px 18px; text-align: center; margin-bottom: 24px;">
          <span style="font-size: 13px; color: #5a2d18;">✨ คุณได้รับ <strong>50 Cookie Points</strong> เข้าบัญชีทันที สามารถสะสมแต้มไปแลกรับคุกกี้ฟรีและของรางวัลได้ที่ <a href="https://taksin105.github.io/cookie-cozy/auth.html" style="color: #bf7739; font-weight: bold;">Rewards Store</a></span>
        </div>

        <!-- Member Perks List -->
        <h3 style="margin: 0 0 14px; font-size: 16px; color: #5a2d18; border-bottom: 1px solid #ebd9c8; padding-bottom: 8px;">
          🌟 สิทธิประโยชน์ที่คุณจะได้รับในฐานะสมาชิก:
        </h3>
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #6b584a; line-height: 1.6;">
          <tr>
            <td style="padding: 6px 0; width: 28px; vertical-align: top;">🎁</td>
            <td style="padding: 6px 0;"><strong>Birthday Surprise:</strong> รับของขวัญคุกกี้กล่องพิเศษฟรีในเดือนเกิดของคุณ</td>
          </tr>
          <tr>
            <td style="padding: 6px 0; width: 28px; vertical-align: top;">⭐</td>
            <td style="padding: 6px 0;"><strong>Point Multiplier:</strong> ทุกยอดชำระ ฿50 ได้รับ 1 Cookie Point นำไปแลกส่วนลดได้ไม่จำกัด</td>
          </tr>
          <tr>
            <td style="padding: 6px 0; width: 28px; vertical-align: top;">🚚</td>
            <td style="padding: 6px 0;"><strong>Free Express Delivery:</strong> สิทธิ์ส่งฟรีประจำเดือนสำหรับสมาชิก</td>
          </tr>
          <tr>
            <td style="padding: 6px 0; width: 28px; vertical-align: top;">🍪</td>
            <td style="padding: 6px 0;"><strong>Secret Tasting:</strong> สิทธิ์ทดลองชิมรสชาติ Limited Edition ก่อนใคร</td>
          </tr>
        </table>

        <!-- CTA Button -->
        <div style="text-align: center; margin: 30px 0 10px;">
          <a href="https://taksin105.github.io/cookie-cozy/auth.html" style="display: inline-block; background: linear-gradient(135deg, #bf7739 0%, #5a2d18 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; border-radius: 28px; font-size: 15px; font-weight: bold; box-shadow: 0 6px 16px rgba(90,45,24,0.25);">
            เข้าสู่ระบบและเช็คคะแนนสะสม ➔
          </a>
        </div>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="padding: 18px 24px; background-color: #f6eee4; text-align: center; border-top: 1px solid #ebd9c8; font-size: 11px; color: #8c7b6d; line-height: 1.5;">
        Cookie Cozy Bakery Co., Ltd. • Cookie Club Privilege Program<br>
        หากมีข้อสงสัย ติดต่อเราได้ที่ support@cookiecozy.com
      </td>
    </tr>
  </table>
</body>
</html>';

// 5. Send Email using mailer_helper (Supports SMTP & Local Outbox File)
require_once __DIR__ . '/mailer_helper.php';
$subject = "🎉 ยินดีต้อนรับสู่ Cookie Club! มอบโบนัสต้อนรับ 50 Points สำหรับคุณ";
$sendResult = sendCookieEmail($email, $subject, $emailBody, 'Cookie Club');

// 6. Return JSON response
echo json_encode([
    'status' => 'success',
    'message' => 'สมัครสมาชิกและส่งอีเมลต้อนรับเรียบร้อยแล้ว!',
    'member_id' => $memberId,
    'name' => $name,
    'email' => $email,
    'bonus_points' => $bonusPoints,
    'mail_sent' => $sendResult['smtp_sent'] || $sendResult['mail_sent'],
    'saved_file' => $sendResult['saved_file'],
    'email_html' => $emailBody
], JSON_UNESCAPED_UNICODE);
