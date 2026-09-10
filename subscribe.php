<?php
/**
 * Cookie Cozy Bakery - Newsletter Subscription & Personalized Marketing API
 * Endpoint: subscribe.php
 * Handles newsletter subscription and sends personalized marketing recommendation email.
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

// 1. Parse Input (JSON or Form Data)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!$data) {
    $data = $_POST;
}

$email = isset($data['email']) ? trim($data['email']) : '';
$name = isset($data['name']) && !empty($data['name']) ? trim($data['name']) : 'Cookie Lover';
$interests = isset($data['interests']) ? $data['interests'] : ['choco'];

if (is_string($interests)) {
    $interests = array_filter(array_map('trim', explode(',', $interests)));
}
if (empty($interests)) {
    $interests = ['choco'];
}

// Validate Email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณาระบุอีเมลที่ถูกต้อง (Invalid email format)'
    ]);
    exit;
}

// 2. Cookie Catalog for Personalized Marketing
$cookieCatalog = [
    'biscoff-lava' => [
        'name' => 'Lotus Biscoff Molten Lava',
        'tagline' => 'ไส้บิสคอฟสเปรดลาวาเยิ้มทะลัก บิสกิตกรุบกรอบ',
        'price' => '฿58',
        'category' => 'lava',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-biscoff.jpg'
    ],
    'nutella-hazelnut' => [
        'name' => 'Nutella Lava & Roasted Hazelnut',
        'tagline' => 'นูเทลล่าเยิ้มๆ โกโก้เข้มข้น เฮเซลนัทคั่วทั้งเม็ด',
        'price' => '฿58',
        'category' => 'choco',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-nutella.jpg'
    ],
    'double-dark' => [
        'name' => 'Double Dark Fudge Sea Salt',
        'tagline' => 'ดาร์กช็อกโกแลต 70% โรยเกล็ดเกลือสมุทรเบลเยียม หวานน้อย',
        'price' => '฿49',
        'category' => 'choco',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-double-dark.jpg'
    ],
    'blueberry-cheesecake' => [
        'name' => 'Wild Blueberry Cheesecake Crumble',
        'tagline' => 'นิวยอร์กครีมชีสแท้ แยมบลูเบอร์รีป่า ครัมเบิลเนยทอง',
        'price' => '฿58',
        'category' => 'lava',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-blueberry.jpg'
    ],
    'hojicha-mochi' => [
        'name' => 'Kyoto Hojicha Mochi & Black Sesame',
        'tagline' => 'ชาเขียวคั่วเกียวโต สอดไส้โมจิญี่ปุ่นยืดนุ่ม งาดำหอมละมุน',
        'price' => '฿55',
        'category' => 'tea',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-hojicha.jpg'
    ],
    'matcha-macadamia' => [
        'name' => 'Ceremonial Matcha Macadamia',
        'tagline' => 'มัทฉะอุจิแท้เกรดพิธีการ ถั่วแมคคาเดเมียอบกรุบกรอบ',
        'price' => '฿55',
        'category' => 'tea',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-matcha.jpg'
    ],
    'earl-grey-lemon' => [
        'name' => 'Earl Grey Lavender & Lemon Glaze',
        'tagline' => 'ใบชาเอิร์ลเกรย์เบอร์กามอต เกลซเลมอนสดชื่น หวานน้อย',
        'price' => '฿50',
        'category' => 'less-sweet',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-earlgrey.jpg'
    ],
    'corncheese' => [
        'name' => 'Honey Butter Corn Cheese Crunch',
        'tagline' => 'ข้าวโพดหวานฉ่ำ มอสซาเรลลาชีสยืดสะใจ คอร์นเฟลกกรอบ',
        'price' => '฿52',
        'category' => 'lava',
        'img' => 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-corncheese.jpg'
    ]
];

// 3. Select Personalized Recommended Cookies
$recommended = [];
$primaryInterest = $interests[0];

foreach ($cookieCatalog as $id => $cookie) {
    if (in_array($cookie['category'], $interests) || ($primaryInterest === 'less-sweet' && in_array($id, ['double-dark', 'hojicha-mochi', 'earl-grey-lemon']))) {
        $recommended[$id] = $cookie;
    }
}

// Fallback if less than 3
if (count($recommended) < 3) {
    foreach ($cookieCatalog as $id => $cookie) {
        if (!isset($recommended[$id])) {
            $recommended[$id] = $cookie;
            if (count($recommended) >= 3) break;
        }
    }
}
$recommended = array_slice($recommended, 0, 3, true);

// Map Interest Labels
$interestLabels = [
    'choco' => 'ช็อกโกแลตเข้มข้น (Choco Lovers)',
    'lava' => 'ลาวาเยิ้มๆ & ชีสเค้ก (Molten Lava)',
    'tea' => 'ชาและผลไม้ (Tea & Fruity)',
    'less-sweet' => 'หวานน้อย สุขภาพดี (Low Sugar)'
];
$selectedInterestText = [];
foreach ($interests as $in) {
    if (isset($interestLabels[$in])) {
        $selectedInterestText[] = $interestLabels[$in];
    }
}
$interestsString = !empty($selectedInterestText) ? implode(', ', $selectedInterestText) : 'รสชาติยอดนิยม';

// 4. Save to mock subscribers database
$subscribersFile = __DIR__ . '/subscribers.json';
$subscribers = [];
if (file_exists($subscribersFile)) {
    $subscribers = json_decode(file_get_contents($subscribersFile), true) ?: [];
}
$subscribers[] = [
    'email' => $email,
    'name' => $name,
    'interests' => $interests,
    'subscribed_at' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];
@file_put_contents($subscribersFile, json_encode($subscribers, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 5. Build HTML Email Template (Personalized Marketing)
$promoCode = 'COZYNEWS15';

$productsHtml = '';
foreach ($recommended as $id => $c) {
    $productsHtml .= '
    <td style="padding: 10px; width: 33.33%; vertical-align: top;">
      <div style="background: #ffffff; border: 1px solid #ebd9c8; border-radius: 14px; overflow: hidden; text-align: center; padding-bottom: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
        <a href="https://taksin105.github.io/cookie-cozy/?product=' . urlencode($id) . '#cookie-' . urlencode($id) . '" target="_blank" style="text-decoration: none; display: block;">
          <img src="' . htmlspecialchars($c['img']) . '" alt="' . htmlspecialchars($c['name']) . '" style="width: 100%; height: 130px; object-fit: cover; display: block;">
        </a>
        <div style="padding: 10px 8px 4px;">
          <h4 style="margin: 0 0 4px; font-size: 13px; color: #3a1a0c; font-family: sans-serif;">
            <a href="https://taksin105.github.io/cookie-cozy/?product=' . urlencode($id) . '#cookie-' . urlencode($id) . '" target="_blank" style="color: #3a1a0c; text-decoration: none;">' . htmlspecialchars($c['name']) . '</a>
          </h4>
          <p style="margin: 0 0 8px; font-size: 11px; color: #7a6a5d; line-height: 1.4;">' . htmlspecialchars($c['tagline']) . '</p>
          <div style="font-size: 14px; font-weight: bold; color: #bf7739; margin-bottom: 8px;">' . $c['price'] . '</div>
          <a href="https://taksin105.github.io/cookie-cozy/?product=' . urlencode($id) . '#cookie-' . urlencode($id) . '" target="_blank" style="display: inline-block; background: #5a2d18; color: #ffffff; text-decoration: none; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: bold;">สั่งชิมรสนี้ ➔</a>
        </div>
      </div>
    </td>';
}

$emailBody = '<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ต้อนรับสู่ Cookie Cozy Bakery</title>
</head>
<body style="margin: 0; padding: 20px; background-color: #fdfaf6; font-family: \'Prompt\', \'Kanit\', Helvetica, Arial, sans-serif; color: #3a1a0c;">
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; border: 1px solid #ebd9c8; overflow: hidden; box-shadow: 0 10px 25px rgba(90,45,24,0.06);">
    <!-- Header -->
    <tr>
      <td align="center" style="padding: 28px 20px 20px; background: linear-gradient(135deg, #5a2d18 0%, #3a1a0c 100%);">
        <h1 style="margin: 0; color: #fdfaf6; font-size: 26px; letter-spacing: 1px;">COOKIE COZY</h1>
        <p style="margin: 4px 0 0; color: #f1c982; font-size: 13px;">Artisan Homemade Bakery • กรอบนอก นุ่มใน</p>
      </td>
    </tr>

    <!-- Main Message -->
    <tr>
      <td style="padding: 30px 26px 20px;">
        <h2 style="margin: 0 0 10px; font-size: 20px; color: #5a2d18;">สวัสดีคุณ ' . htmlspecialchars($name) . ' ♡</h2>
        <p style="margin: 0 0 16px; font-size: 14px; color: #6b584a; line-height: 1.6;">
          ยินดีต้อนรับสู่ครอบครัวคนรักคุกกี้! เราพร้อมส่งต่อความอบอุ่นและกลิ่นหอมกรุ่นจากเตาอบส่งตรงถึงบ้านคุณทุกวัน
        </p>

        <!-- Promo Code Voucher -->
        <div style="background: #fff8eb; border: 2px dashed #bf7739; border-radius: 14px; padding: 18px; text-align: center; margin: 20px 0;">
          <span style="font-size: 12px; color: #bf7739; font-weight: bold; text-transform: uppercase;">🎁 โค้ดส่วนลดพิเศษ 15% สำหรับคุณ</span>
          <div style="font-size: 26px; font-weight: bold; letter-spacing: 2px; color: #5a2d18; margin: 6px 0;">' . $promoCode . '</div>
          <span style="font-size: 12px; color: #7a6a5d;">ใช้ได้กับการสั่งซื้อคุกกี้ทุกรายการ ไม่มีขั้นต่ำ!</span>
        </div>

        <!-- Personalized Recommendations Header -->
        <div style="margin-top: 26px; border-top: 1px solid #ebd9c8; padding-top: 22px;">
          <div style="display: inline-block; background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-bottom: 6px;">
            ✦ Personalized Recommendations
          </div>
          <h3 style="margin: 0 0 6px; font-size: 17px; color: #5a2d18;">เมนูแนะนำพิเศษคัดสรรตามสไตล์คุณ</h3>
          <p style="margin: 0 0 16px; font-size: 13px; color: #7a6a5d;">
            จากที่คุณสนใจ: <strong>' . htmlspecialchars($interestsString) . '</strong> เชฟขอแนะนำ 3 รสชาติที่ต้องลอง:
          </p>

          <!-- 3 Recommended Cookies Grid -->
          <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
              ' . $productsHtml . '
            </tr>
          </table>
        </div>

        <!-- CTA Button -->
        <div style="text-align: center; margin: 30px 0 10px;">
          <a href="https://taksin105.github.io/cookie-cozy/#menu" style="display: inline-block; background: linear-gradient(135deg, #bf7739 0%, #5a2d18 100%); color: #ffffff; text-decoration: none; padding: 14px 34px; border-radius: 28px; font-size: 15px; font-weight: bold; box-shadow: 0 6px 16px rgba(90,45,24,0.25);">
            สั่งซื้อและใช้คูปองส่วนลด ➔
          </a>
        </div>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="padding: 18px 24px; background-color: #f6eee4; text-align: center; border-top: 1px solid #ebd9c8; font-size: 11px; color: #8c7b6d; line-height: 1.5;">
        Cookie Cozy Bakery Co., Ltd. • อบสดใหม่ด้วยเนยแท้ฝรั่งเศส 100%<br>
        หากต้องการยกเลิกการรับข่าวสาร <a href="https://taksin105.github.io/cookie-cozy/" style="color: #bf7739; text-decoration: underline;">คลิกที่นี่เพื่อ Unsubscribe</a>
      </td>
    </tr>
  </table>
</body>
</html>';

// 6. Send Email using mailer_helper (Supports SMTP & Local Outbox File)
require_once __DIR__ . '/mailer_helper.php';
$subject = "🍪 คูปองลด 15% พร้อมคุกกี้รสโปรดของคุณจาก Cookie Cozy Bakery!";
$sendResult = sendCookieEmail($email, $subject, $emailBody, 'Cookie Cozy Bakery');

// 7. Return JSON response
echo json_encode([
    'status' => 'success',
    'message' => 'สมัครรับข่าวสารสำเร็จแล้ว! เราได้จัดส่งคูปองส่วนลด 15% และคุกกี้แนะนำไปยังอีเมลของคุณเรียบร้อยแล้ว',
    'email' => $email,
    'interests' => $interests,
    'promo_code' => $promoCode,
    'mail_sent' => $sendResult['smtp_sent'] || $sendResult['mail_sent'],
    'saved_file' => $sendResult['saved_file'],
    'recommended_cookies' => array_values($recommended),
    'email_html' => $emailBody
], JSON_UNESCAPED_UNICODE);
