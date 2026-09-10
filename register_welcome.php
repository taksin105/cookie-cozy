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
<body style="margin: 0; padding: 20px 10px; background-color: #f7f0e7; font-family: \'Prompt\', \'Kanit\', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #3a1a0c;">
  <!-- Email Wrapper -->
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 24px; border: 1px solid #ebd9c8; overflow: hidden; box-shadow: 0 12px 36px rgba(90,45,24,0.08);">
    
    <!-- Header Brand Bar -->
    <tr>
      <td align="center" style="padding: 26px 20px 20px; background: linear-gradient(135deg, #2c1409 0%, #4a210f 50%, #3a1a0c 100%);">
        <a href="https://taksin105.github.io/cookie-cozy/" target="_blank" style="text-decoration: none;">
          <img src="https://taksin105.github.io/cookie-cozy/images/cookie-cozy/logo.png" alt="Cookie Cozy" width="150" style="display: block; border: 0; height: auto; max-height: 52px; object-fit: contain; margin: 0 auto;">
        </a>
        <div style="margin-top: 10px; color: #f1c982; font-size: 11px; font-weight: 600; letter-spacing: 2px; text-transform: uppercase;">
          ★ OFFICIAL WELCOME TO COOKIE CLUB ★
        </div>
      </td>
    </tr>

    <!-- Hero Image Banner -->
    <tr>
      <td style="padding: 0; position: relative;">
        <img src="https://taksin105.github.io/cookie-cozy/images/cookie-cozy/hero-banner.jpg" alt="Freshly Baked Cozy Cookies" width="100%" style="display: block; width: 100%; max-height: 240px; object-fit: cover; border: 0;">
      </td>
    </tr>

    <!-- Main Content Area -->
    <tr>
      <td style="padding: 30px 28px 20px;">
        
        <!-- Welcome Greeting -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: center; margin-bottom: 24px;">
          <tr>
            <td align="center">
              <div style="display: inline-block; background: #fff5e6; border: 1px solid #f8dfbe; border-radius: 30px; padding: 5px 16px; margin-bottom: 12px;">
                <span style="font-size: 13px; color: #bf7739; font-weight: 600;">✨ การสมัครสมาชิกเสร็จสมบูรณ์</span>
              </div>
              <h1 style="margin: 0 0 8px; font-size: 24px; color: #3a1a0c; font-weight: 700; line-height: 1.3;">
                ยินดีต้อนรับคุณ ' . htmlspecialchars($name) . '!
              </h1>
              <p style="margin: 0; font-size: 14px; color: #7a6a5d; line-height: 1.6;">
                ขอต้อนรับสู่ครอบครัว <strong>Cookie Cozy</strong> เราพร้อมส่งมอบคุกกี้โฮมเมดระดับพรีเมียม อบสดใหม่ด้วยเนยแท้ฝรั่งเศส 100% ตรงถึงมือคุณแล้ววันนี้
              </p>
            </td>
          </tr>
        </table>

        <!-- Luxury VIP Member Card -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: linear-gradient(135deg, #241107 0%, #461f0d 50%, #703617 85%, #b5672c 100%); border-radius: 20px; border: 2px solid #e2a85c; box-shadow: 0 10px 24px rgba(58,26,12,0.25); margin-bottom: 24px;">
          <tr>
            <td style="padding: 24px 26px; color: #ffffff;">
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 20px;">
                <tr>
                  <td>
                    <span style="font-size: 11px; font-weight: 700; letter-spacing: 1.5px; color: #f1c982; text-transform: uppercase;">COOKIE CLUB VIP PASS</span>
                  </td>
                  <td align="right">
                    <span style="background: rgba(241, 201, 130, 0.2); border: 1px solid rgba(241, 201, 130, 0.5); color: #fdfaf6; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                      ✦ ' . htmlspecialchars($memberTier) . '
                    </span>
                  </td>
                </tr>
              </table>

              <!-- Member ID display -->
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 18px;">
                <tr>
                  <td>
                    <div style="font-size: 10px; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">CARD NUMBER</div>
                    <div style="font-family: \'Courier New\', Courier, monospace; font-size: 22px; font-weight: 700; letter-spacing: 3px; color: #fff8eb; text-shadow: 0 2px 4px rgba(0,0,0,0.4);">
                      ' . $memberId . '
                    </div>
                  </td>
                </tr>
              </table>

              <!-- Card Bottom Details -->
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-top: 1px solid rgba(255,255,255,0.15); padding-top: 14px;">
                <tr>
                  <td style="vertical-align: bottom;">
                    <div style="font-size: 9px; color: #f1c982; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px;">MEMBER NAME</div>
                    <div style="font-size: 16px; font-weight: 700; color: #ffffff; letter-spacing: 0.5px;">
                      ' . htmlspecialchars($name) . '
                    </div>
                  </td>
                  <td align="right" style="vertical-align: bottom;">
                    <div style="font-size: 9px; color: #f1c982; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 3px;">WELCOME BONUS</div>
                    <div style="display: inline-block; background: #f1c982; color: #3a1a0c; font-size: 14px; font-weight: 800; padding: 4px 12px; border-radius: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.2);">
                      ⭐ +' . $bonusPoints . ' PTS
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>

        <!-- Bonus Points Alert & Promo Voucher -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background: #fffdf9; border: 1.5px dashed #e2a85c; border-radius: 16px; margin-bottom: 28px;">
          <tr>
            <td style="padding: 16px 20px;">
              <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                  <td style="width: 44px; vertical-align: middle;" align="center">
                    <span style="font-size: 28px;">🎁</span>
                  </td>
                  <td style="vertical-align: middle; padding-left: 10px;">
                    <div style="font-size: 14px; font-weight: 700; color: #5a2d18; margin-bottom: 2px;">
                      ของขวัญต้อนรับ: แต้มสะสม 50 Points พร้อมใช้งานทันที!
                    </div>
                    <div style="font-size: 12px; color: #7a6a5d; line-height: 1.5;">
                      สะสมแต้มแลกรับคุกกี้ฟรีที่ Rewards Store หรือใช้โค้ดลดพิเศษ <strong style="color: #bf7739; background: #fff0db; padding: 1px 6px; border-radius: 4px; font-family: monospace;">WELCOMEVIP</strong> รับส่วนลด 10% สำหรับคำสั่งซื้อแรก
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
        </table>

        <!-- Signature Cookies Showcase (ใส่รูปคุ้กกี้ขายดี 3 รสชาติ) -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
          <tr>
            <td colspan="3" style="padding-bottom: 14px; border-bottom: 2px solid #f3e5d8;">
              <h3 style="margin: 0; font-size: 17px; color: #3a1a0c; font-weight: 700;">
                🍪 เมนูคุกกี้ Signature ยอดนิยมที่คุณไม่ควรพลาด
              </h3>
              <p style="margin: 3px 0 0; font-size: 12px; color: #8c7b6d;">
                คัดสรร 3 เมนูขายดีอันดับหนึ่ง อบสดใหม่เนื้อนุ่มไส้เยิ้มทะลัก
              </p>
            </td>
          </tr>
          <tr><td height="14" colspan="3"></td></tr>

          <!-- 3 Cookies Row -->
          <tr>
            <!-- Cookie 1: Classic Choco Chip -->
            <td width="31%" style="vertical-align: top; background: #fffaf5; border: 1px solid #ebd9c8; border-radius: 14px; overflow: hidden; padding: 10px; text-align: center;">
              <img src="https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-chocochip.jpg" alt="Classic Choco Chip" width="100%" style="display: block; width: 100%; height: 110px; object-fit: cover; border-radius: 10px; margin-bottom: 8px;">
              <span style="display: inline-block; background: #fee2e2; color: #b91c1c; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; margin-bottom: 4px;">
                ⭐ Best Seller
              </span>
              <div style="font-size: 12px; font-weight: 700; color: #3a1a0c; line-height: 1.3; margin-bottom: 4px; min-height: 32px;">
                Classic Choco Chip
              </div>
              <div style="font-size: 11px; color: #7a6a5d; margin-bottom: 8px; line-height: 1.3;">
                ช็อกโกแลตเยิ้มๆ เนยแท้ฝรั่งเศส
              </div>
              <div style="font-size: 13px; font-weight: 800; color: #bf7739;">
                ฿45
              </div>
            </td>

            <td width="3.5%"></td>

            <!-- Cookie 2: Red Velvet Cream Cheese -->
            <td width="31%" style="vertical-align: top; background: #fffaf5; border: 1px solid #ebd9c8; border-radius: 14px; overflow: hidden; padding: 10px; text-align: center;">
              <img src="https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-redvelvet.jpg" alt="Red Velvet Cream Cheese" width="100%" style="display: block; width: 100%; height: 110px; object-fit: cover; border-radius: 10px; margin-bottom: 8px;">
              <span style="display: inline-block; background: #fef3c7; color: #b45309; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; margin-bottom: 4px;">
                💖 ครีมชีสลาวา
              </span>
              <div style="font-size: 12px; font-weight: 700; color: #3a1a0c; line-height: 1.3; margin-bottom: 4px; min-height: 32px;">
                Red Velvet Cream Cheese
              </div>
              <div style="font-size: 11px; color: #7a6a5d; margin-bottom: 8px; line-height: 1.3;">
                ครีมชีสนิวซีแลนด์รสละมุนลิ้น
              </div>
              <div style="font-size: 13px; font-weight: 800; color: #bf7739;">
                ฿55
              </div>
            </td>

            <td width="3.5%"></td>

            <!-- Cookie 3: Lotus Biscoff Lava -->
            <td width="31%" style="vertical-align: top; background: #fffaf5; border: 1px solid #ebd9c8; border-radius: 14px; overflow: hidden; padding: 10px; text-align: center;">
              <img src="https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-biscoff.jpg" alt="Lotus Biscoff Lava" width="100%" style="display: block; width: 100%; height: 110px; object-fit: cover; border-radius: 10px; margin-bottom: 8px;">
              <span style="display: inline-block; background: #ffedd5; color: #c2410c; font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 6px; margin-bottom: 4px;">
                🔥 ลาวาคาราเมล
              </span>
              <div style="font-size: 12px; font-weight: 700; color: #3a1a0c; line-height: 1.3; margin-bottom: 4px; min-height: 32px;">
                Lotus Biscoff Lava
              </div>
              <div style="font-size: 11px; color: #7a6a5d; margin-bottom: 8px; line-height: 1.3;">
                บิสกิตเบลเยียม ลาวาคาราเมล
              </div>
              <div style="font-size: 13px; font-weight: 800; color: #bf7739;">
                ฿55
              </div>
            </td>
          </tr>
        </table>

        <!-- Member Perks Grid (2x2 Cards) -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
          <tr>
            <td colspan="3" style="padding-bottom: 12px; border-bottom: 1px solid #ebd9c8;">
              <h3 style="margin: 0; font-size: 15px; color: #5a2d18; font-weight: 700;">
                🌟 สิทธิพิเศษสำหรับสมาชิก Cookie Club
              </h3>
            </td>
          </tr>
          <tr><td height="12" colspan="3"></td></tr>

          <!-- Row 1 -->
          <tr>
            <td width="48%" style="vertical-align: top; background: #faf5ee; border-radius: 12px; padding: 12px 14px;">
              <div style="font-size: 13px; font-weight: 700; color: #3a1a0c; margin-bottom: 3px;">
                🎂 Birthday Surprise
              </div>
              <div style="font-size: 11.5px; color: #6b584a; line-height: 1.4;">
                รับคุกกี้กล่องพิเศษฟรีในเดือนเกิดของคุณ ส่งตรงถึงบ้าน
              </div>
            </td>
            <td width="4%"></td>
            <td width="48%" style="vertical-align: top; background: #faf5ee; border-radius: 12px; padding: 12px 14px;">
              <div style="font-size: 13px; font-weight: 700; color: #3a1a0c; margin-bottom: 3px;">
                ⭐ Points Multiplier
              </div>
              <div style="font-size: 11.5px; color: #6b584a; line-height: 1.4;">
                ทุกยอดซื้อ ฿50 ได้รับ 1 Point แลกส่วนลดได้ไม่จำกัด
              </div>
            </td>
          </tr>

          <tr><td height="10" colspan="3"></td></tr>

          <!-- Row 2 -->
          <tr>
            <td width="48%" style="vertical-align: top; background: #faf5ee; border-radius: 12px; padding: 12px 14px;">
              <div style="font-size: 13px; font-weight: 700; color: #3a1a0c; margin-bottom: 3px;">
                🚚 Free Express Delivery
              </div>
              <div style="font-size: 11.5px; color: #6b584a; line-height: 1.4;">
                รับสิทธิ์คูปองจัดส่งฟรีประจำเดือนสำหรับสมาชิก
              </div>
            </td>
            <td width="4%"></td>
            <td width="48%" style="vertical-align: top; background: #faf5ee; border-radius: 12px; padding: 12px 14px;">
              <div style="font-size: 13px; font-weight: 700; color: #3a1a0c; margin-bottom: 3px;">
                🍪 Secret Tasting
              </div>
              <div style="font-size: 11.5px; color: #6b584a; line-height: 1.4;">
                สิทธิ์สั่งทดลองชิมรสชาติ Limited Edition ก่อนใคร
              </div>
            </td>
          </tr>
        </table>

        <!-- Main Call To Action Button -->
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="text-align: center; margin: 20px 0 10px;">
          <tr>
            <td align="center">
              <a href="https://taksin105.github.io/cookie-cozy/" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #d98236 0%, #bf7739 50%, #8c4217 100%); color: #ffffff; text-decoration: none; padding: 16px 38px; border-radius: 30px; font-size: 15px; font-weight: 700; letter-spacing: 0.5px; box-shadow: 0 8px 20px rgba(191,119,57,0.35);">
                🍪 สั่งซื้อคุกกี้ & ใช้คะแนนสะสม ➔
              </a>
              <div style="margin-top: 10px; font-size: 12px; color: #8c7b6d;">
                หรือจัดการบัญชีของคุณได้ที่ <a href="https://taksin105.github.io/cookie-cozy/auth.html" style="color: #bf7739; font-weight: 600; text-decoration: underline;">หน้าสมาชิก Cookie Club</a>
              </div>
            </td>
          </tr>
        </table>

      </td>
    </tr>

    <!-- Bakery Guarantee Badges -->
    <tr>
      <td style="padding: 16px 24px; background-color: #fbf6ef; border-top: 1px solid #ebd9c8; text-align: center;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 11px; color: #6b584a; font-weight: 600;">
          <tr>
            <td align="center">🧈 เนยแท้ฝรั่งเศส 100%</td>
            <td align="center">•</td>
            <td align="center">🔥 อบสดใหม่จากเตาทุกวัน</td>
            <td align="center">•</td>
            <td align="center">🚫 ไม่ใช้วัตถุกันเสีย</td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="padding: 22px 28px; background-color: #2c1409; text-align: center; color: #cfb9a8; font-size: 11px; line-height: 1.6;">
        <div style="font-weight: 700; color: #fdfaf6; font-size: 13px; margin-bottom: 4px;">Cookie Cozy Bakery Co., Ltd.</div>
        <div>อบด้วยรักและวัตถุดิบพรีเมียม เพื่อทุกโมเมนต์ที่อบอุ่นของคุณ</div>
        <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); color: #8c7b6d;">
          อีเมลนี้ส่งถึงคุณเนื่องจากคุณได้สมัครสมาชิก Cookie Club หากไม่ได้ดำเนินการ กรุณาติดต่อ support@cookiecozy.com
        </div>
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
