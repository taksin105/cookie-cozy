<?php
/**
 * Cookie Cozy Bakery - Order Confirmation & Receipt Email API
 * Endpoint: order_confirmation.php
 * Handles new order confirmation and sends a luxury receipt email to customer upon purchase.
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

if (empty($data)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบข้อมูลคำสั่งซื้อ (Empty order payload)'
    ]);
    exit;
}

$orderId = isset($data['id']) && !empty($data['id']) ? trim($data['id']) : ('#CCZ-' . date('Ym') . '-' . rand(1000, 9999));
$orderDate = isset($data['date']) && !empty($data['date']) ? trim($data['date']) : date('d M Y H:i');

$customer = isset($data['customer']) && is_array($data['customer']) ? $data['customer'] : [];
$name = isset($customer['name']) && !empty($customer['name']) ? trim($customer['name']) : 'ลูกค้าคนพิเศษ';
$email = isset($customer['email']) ? trim($customer['email']) : '';
$phone = isset($customer['phone']) && !empty($customer['phone']) ? trim($customer['phone']) : '-';

$address = isset($data['address']) && !empty($data['address']) ? trim($data['address']) : 'จัดส่งตามที่อยู่ที่ระบุ';
$delivery = isset($data['delivery']) && is_array($data['delivery']) ? $data['delivery'] : [];
$deliveryMethod = isset($delivery['method']) ? $delivery['method'] : 'standard';
$deliveryBatch = isset($delivery['batch']) && !empty($delivery['batch']) ? $delivery['batch'] : 'รอบเตาอบสดประจำวัน (10:00 - 12:00 น.)';
$deliveryNote = isset($delivery['note']) ? trim($delivery['note']) : '';

$payment = isset($data['payment']) && is_array($data['payment']) ? $data['payment'] : [];
$paymentMethod = isset($payment['method']) ? $payment['method'] : 'promptpay';
$hasSlip = !empty($payment['hasSlip']);

$pricing = isset($data['pricing']) && is_array($data['pricing']) ? $data['pricing'] : [];
$subtotal = isset($pricing['subtotal']) ? (float)$pricing['subtotal'] : 0;
$discount = isset($pricing['discount']) ? (float)$pricing['discount'] : 0;
$shipping = isset($pricing['shipping']) ? (float)$pricing['shipping'] : 0;
$codFee = isset($pricing['codFee']) ? (float)$pricing['codFee'] : 0;
$grandTotal = isset($pricing['grandTotal']) ? (float)$pricing['grandTotal'] : max(0, $subtotal - $discount + $shipping + $codFee);
$coupon = isset($pricing['coupon']) ? trim($pricing['coupon']) : '';

$pointsEarned = isset($data['pointsEarned']) ? (int)$data['pointsEarned'] : (int)floor($grandTotal / 50);
$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

// Validate Email
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'กรุณาระบุอีเมลที่ถูกต้องสำหรับจัดส่งใบเสร็จ (Invalid customer email)'
    ]);
    exit;
}

// 2. Save Order to persistent JSON store
$ordersFile = __DIR__ . '/orders.json';
$orders = [];
if (file_exists($ordersFile)) {
    $orders = json_decode(file_get_contents($ordersFile), true) ?: [];
}
$orders[$orderId] = [
    'id' => $orderId,
    'date' => $orderDate,
    'customer' => [
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ],
    'address' => $address,
    'delivery' => [
        'method' => $deliveryMethod,
        'batch' => $deliveryBatch,
        'note' => $deliveryNote
    ],
    'payment' => [
        'method' => $paymentMethod,
        'has_slip' => $hasSlip,
        'cod_fee' => $codFee
    ],
    'pricing' => [
        'subtotal' => $subtotal,
        'discount' => $discount,
        'shipping' => $shipping,
        'cod_fee' => $codFee,
        'grand_total' => $grandTotal,
        'coupon' => $coupon
    ],
    'points_earned' => $pointsEarned,
    'items_count' => count($items),
    'created_at' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
];
@file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 3. Map Human-readable labels
$paymentNames = [
    'promptpay' => 'พร้อมเพย์ QR Code (ชำระเงินเรียบร้อยแล้ว)',
    'card' => 'บัตรเครดิต / เดบิต (ชำระเงินเรียบร้อยแล้ว)',
    'cod' => 'ชำระเงินสดปลายทาง (Cash on Delivery)'
];
$paymentText = $paymentNames[$paymentMethod] ?? 'ชำระเงินเรียบร้อยแล้ว';

$shippingNames = [
    'standard' => 'ขนส่งพัสดุควบคุมความชื้น (Standard Express)',
    'express' => 'ส่งด่วนไรเดอร์เตาอบทันใจ (Express Rider)'
];
$shippingText = $shippingNames[$deliveryMethod] ?? 'Standard Delivery';

// 4. Build Itemized HTML Rows
$itemsHtml = '';
foreach ($items as $item) {
    $itemName = htmlspecialchars($item['name'] ?? 'คุกกี้โฮมเมด');
    $itemQty = (int)($item['qty'] ?? 1);
    $itemPrice = (float)($item['price'] ?? 0);
    $itemTotal = $itemPrice * $itemQty;
    $giftDetails = !empty($item['giftDetails']) ? htmlspecialchars($item['giftDetails']) : '';
    
    // Cookie thumbnail
    $itemImg = !empty($item['img']) ? $item['img'] : 'https://taksin105.github.io/cookie-cozy/images/cookie-cozy/cookie-chocochip.jpg';
    if (!preg_match('/^https?:\/\//i', $itemImg)) {
        $itemImg = 'https://taksin105.github.io/cookie-cozy/' . ltrim($itemImg, '/');
    }

    $itemsHtml .= '
    <tr>
      <td style="padding: 14px 12px; border-bottom: 1px solid #ebd9c8; vertical-align: middle;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td width="64" style="vertical-align: middle;">
              <img src="' . htmlspecialchars($itemImg) . '" alt="' . $itemName . '" width="58" height="58" style="width: 58px; height: 58px; object-fit: cover; border-radius: 12px; border: 1px solid #ebd9c8; display: block;">
            </td>
            <td style="padding-left: 12px; vertical-align: middle;">
              <strong style="color: #3a1a0c; font-size: 14px; display: block; line-height: 1.3;">' . $itemName . '</strong>
              ' . ($giftDetails ? '<span style="color: #bf7739; font-size: 11px; display: block; margin-top: 2px;">' . $giftDetails . '</span>' : '') . '
              <span style="color: #8c7b6d; font-size: 12px;">฿' . number_format($itemPrice) . ' / ชิ้น</span>
            </td>
          </tr>
        </table>
      </td>
      <td align="center" style="padding: 14px 8px; border-bottom: 1px solid #ebd9c8; color: #5a2d18; font-weight: bold; font-size: 14px; vertical-align: middle;">
        x' . $itemQty . '
      </td>
      <td align="right" style="padding: 14px 12px; border-bottom: 1px solid #ebd9c8; color: #3a1a0c; font-weight: 700; font-size: 14px; vertical-align: middle;">
        ฿' . number_format($itemTotal) . '
      </td>
    </tr>';
}

// Discount line
$discountRow = '';
if ($discount > 0) {
    $couponText = $coupon ? ' (' . htmlspecialchars($coupon) . ')' : '';
    $discountRow = '
    <tr>
      <td style="padding: 6px 12px; color: #2e7d32; font-size: 13px;">ส่วนลดพิเศษ' . $couponText . '</td>
      <td align="right" style="padding: 6px 12px; color: #2e7d32; font-weight: bold; font-size: 13px;">-฿' . number_format($discount) . '</td>
    </tr>';
}

// Shipping line
$shippingDisplay = ($shipping == 0) ? '<span style="color: #2e7d32; font-weight: bold;">ฟรี (ยอดครบ ฿350)</span>' : '฿' . number_format($shipping);

// COD line
$codRow = '';
if ($codFee > 0) {
    $codRow = '
    <tr>
      <td style="padding: 6px 12px; color: #7a6a5d; font-size: 13px;">ค่าธรรมเนียมเก็บเงินปลายทาง (COD)</td>
      <td align="right" style="padding: 6px 12px; color: #3a1a0c; font-weight: bold; font-size: 13px;">฿' . number_format($codFee) . '</td>
    </tr>';
}

// 5. Construct Luxury Order Confirmation Email Template
$emailBody = '<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ยืนยันคำสั่งซื้อ ' . $orderId . ' - Cookie Cozy</title>
</head>
<body style="margin: 0; padding: 24px 12px; background-color: #f7f1ea; font-family: \'Prompt\', \'Kanit\', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #3a1a0c;">
  
  <!-- Main Container -->
  <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 24px; border: 1px solid #ebd9c8; overflow: hidden; box-shadow: 0 12px 35px rgba(90,45,24,0.07);">
    
    <!-- Header Brand Bar -->
    <tr>
      <td align="center" style="padding: 26px 20px 20px; background: linear-gradient(135deg, #2c1409 0%, #4a210f 50%, #3a1a0c 100%);">
        <a href="https://taksin105.github.io/cookie-cozy/" target="_blank" style="text-decoration: none;">
          <img src="https://taksin105.github.io/cookie-cozy/images/cookie-cozy/logo.png" alt="Cookie Cozy" width="150" style="display: block; border: 0; height: auto; max-height: 50px; object-fit: contain; margin: 0 auto;">
        </a>
        <div style="margin-top: 10px; color: #f1c982; font-size: 11px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase;">
          ★ OFFICIAL ORDER RECEIPT & CONFIRMATION ★
        </div>
      </td>
    </tr>

    <!-- Status Banner -->
    <tr>
      <td style="padding: 26px 26px 14px; text-align: center; background-color: #fffaf2; border-bottom: 1px solid #ebd9c8;">
        <div style="display: inline-block; background: #e8f5e9; border: 1px solid #a5d6a7; color: #2e7d32; font-size: 12px; font-weight: 700; padding: 5px 16px; border-radius: 20px; margin-bottom: 12px;">
          ✓ คำสั่งซื้อได้รับการยืนยันแล้ว
        </div>
        <h1 style="margin: 0 0 6px; font-size: 22px; color: #3a1a0c; font-weight: 700;">
          ขอบคุณสำหรับคำสั่งซื้อของคุณ! ♡
        </h1>
        <p style="margin: 0; font-size: 14px; color: #7a6a5d; line-height: 1.5;">
          เชฟได้รับออเดอร์ของคุณแล้ว และกำลังเตรียมอบคุกกี้สดใหม่ด้วยเนยแท้ฝรั่งเศส 100%
        </p>
      </td>
    </tr>

    <!-- Order Metadata Box -->
    <tr>
      <td style="padding: 22px 26px 10px;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fcf8f3; border: 1px solid #ebd9c8; border-radius: 16px; padding: 16px 20px;">
          <tr>
            <td style="vertical-align: top; width: 50%;">
              <span style="font-size: 11px; color: #8c7b6d; text-transform: uppercase; font-weight: 600; display: block;">หมายเลขคำสั่งซื้อ</span>
              <strong style="font-size: 16px; color: #5a2d18; font-family: \'Courier New\', monospace; letter-spacing: 1px;">' . $orderId . '</strong>
            </td>
            <td align="right" style="vertical-align: top; width: 50%;">
              <span style="font-size: 11px; color: #8c7b6d; text-transform: uppercase; font-weight: 600; display: block;">วันที่และเวลา</span>
              <strong style="font-size: 13px; color: #3a1a0c;">' . htmlspecialchars($orderDate) . '</strong>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Shipping & Customer Details Card -->
    <tr>
      <td style="padding: 10px 26px 20px;">
        <div style="background-color: #ffffff; border: 1px solid #ebd9c8; border-radius: 16px; padding: 18px 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.02);">
          <h3 style="margin: 0 0 12px; font-size: 14px; color: #5a2d18; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px dashed #ebd9c8; padding-bottom: 8px;">
            📦 ข้อมูลการจัดส่งและผู้รับ
          </h3>
          <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px; color: #4a3427; line-height: 1.6;">
            <tr>
              <td width="100" style="color: #8c7b6d; font-weight: 600;">ชื่อผู้รับ:</td>
              <td><strong>' . htmlspecialchars($name) . '</strong></td>
            </tr>
            <tr>
              <td style="color: #8c7b6d; font-weight: 600;">เบอร์โทรศัพท์:</td>
              <td>' . htmlspecialchars($phone) . '</td>
            </tr>
            <tr>
              <td style="color: #8c7b6d; font-weight: 600;">อีเมลใบเสร็จ:</td>
              <td>' . htmlspecialchars($email) . '</td>
            </tr>
            <tr>
              <td style="color: #8c7b6d; font-weight: 600; vertical-align: top;">ที่อยู่จัดส่ง:</td>
              <td>' . htmlspecialchars($address) . '</td>
            </tr>
            <tr>
              <td style="color: #8c7b6d; font-weight: 600;">รอบเตาอบสด:</td>
              <td><span style="color: #bf7739; font-weight: 600;">' . htmlspecialchars($deliveryBatch) . '</span></td>
            </tr>
            <tr>
              <td style="color: #8c7b6d; font-weight: 600;">วิธีจัดส่ง:</td>
              <td>' . htmlspecialchars($shippingText) . '</td>
            </tr>
            ' . ($deliveryNote ? '
            <tr>
              <td style="color: #8c7b6d; font-weight: 600; vertical-align: top;">หมายเหตุ:</td>
              <td style="color: #7a6a5d; font-style: italic;">' . htmlspecialchars($deliveryNote) . '</td>
            </tr>' : '') . '
          </table>
        </div>
      </td>
    </tr>

    <!-- Order Items Table -->
    <tr>
      <td style="padding: 0 26px 20px;">
        <h3 style="margin: 0 0 12px; font-size: 15px; color: #5a2d18;">
          🍪 สรุปรายการสินค้าที่สั่งซื้อ
        </h3>
        
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border: 1px solid #ebd9c8; border-radius: 16px; overflow: hidden;">
          <thead>
            <tr style="background-color: #fcf7ef;">
              <th align="left" style="padding: 10px 14px; font-size: 12px; color: #5a2d18; border-bottom: 1px solid #ebd9c8;">รายการสินค้า</th>
              <th align="center" style="padding: 10px 8px; font-size: 12px; color: #5a2d18; border-bottom: 1px solid #ebd9c8; width: 60px;">จำนวน</th>
              <th align="right" style="padding: 10px 14px; font-size: 12px; color: #5a2d18; border-bottom: 1px solid #ebd9c8; width: 90px;">ราคารวม</th>
            </tr>
          </thead>
          <tbody>
            ' . $itemsHtml . '
          </tbody>
        </table>
      </td>
    </tr>

    <!-- Price Calculation Breakdown -->
    <tr>
      <td style="padding: 0 26px 24px;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #fff9f2; border: 1px solid #ebd9c8; border-radius: 16px; padding: 14px 18px;">
          <tr>
            <td style="padding: 6px 12px; color: #7a6a5d; font-size: 13px;">ยอดรวมสินค้า (Subtotal)</td>
            <td align="right" style="padding: 6px 12px; color: #3a1a0c; font-weight: 600; font-size: 13px;">฿' . number_format($subtotal) . '</td>
          </tr>
          ' . $discountRow . '
          <tr>
            <td style="padding: 6px 12px; color: #7a6a5d; font-size: 13px;">ค่าจัดส่ง (Shipping)</td>
            <td align="right" style="padding: 6px 12px; font-size: 13px;">' . $shippingDisplay . '</td>
          </tr>
          ' . $codRow . '
          <tr>
            <td colspan="2" style="border-top: 1.5px dashed #ebd9c8; padding-top: 10px; margin-top: 6px;"></td>
          </tr>
          <tr>
            <td style="padding: 8px 12px; color: #3a1a0c; font-weight: 800; font-size: 16px;">ยอดชำระสุทธิ (Grand Total)</td>
            <td align="right" style="padding: 8px 12px; color: #bf7739; font-weight: 800; font-size: 20px;">฿' . number_format($grandTotal) . '</td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Bonus Points & Payment Callout -->
    <tr>
      <td style="padding: 0 26px 26px;">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <!-- Points Box -->
            <td width="48%" style="background: linear-gradient(135deg, #fff5e6 0%, #faecd8 100%); border: 1px solid #f3d4a6; border-radius: 14px; padding: 14px; vertical-align: top;">
              <span style="font-size: 11px; color: #b06a28; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">⭐ COOKIE POINTS</span>
              <div style="font-size: 17px; font-weight: 800; color: #5a2d18;">+' . $pointsEarned . ' แต้มสะสม</div>
              <span style="font-size: 11px; color: #7a6a5d;">เข้าสู่กระเป๋าสำหรับแลกขนมฟรี</span>
            </td>
            <td width="4%"></td>
            <!-- Payment Status Box -->
            <td width="48%" style="background-color: #f7faf7; border: 1px solid #c8e6c9; border-radius: 14px; padding: 14px; vertical-align: top;">
              <span style="font-size: 11px; color: #2e7d32; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 2px;">💳 วิธีการชำระเงิน</span>
              <div style="font-size: 13px; font-weight: 700; color: #1b5e20;">' . htmlspecialchars($paymentText) . '</div>
              <span style="font-size: 11px; color: #66bb6a;">' . ($paymentMethod === 'cod' ? 'เตรียมเงินสดให้ไรเดอร์' : 'ยืนยันยอดเงินสำเร็จ') . '</span>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Baking & Warming Guide -->
    <tr>
      <td style="padding: 0 26px 28px;">
        <div style="background-color: #fbf8f5; border: 1px solid #ebd9c8; border-radius: 18px; padding: 18px 20px;">
          <h4 style="margin: 0 0 10px; font-size: 14px; color: #5a2d18; display: flex; align-items: center;">
            🔥 เคล็ดลับความอร่อยฟินเหมือนเพิ่งออกจากเตาอบ
          </h4>
          <table border="0" cellpadding="0" cellspacing="0" width="100%" style="font-size: 12px; color: #6b584a; line-height: 1.6;">
            <tr>
              <td width="24" style="vertical-align: top; color: #bf7739; font-weight: bold;">1.</td>
              <td><strong>อุ่นไมโครเวฟ 15 - 20 วินาที:</strong> ไส้ช็อกโกแลตและลาวาจะเยิ้มทะลัก เนื้อคุกกี้จะนุ่มหนึบฟินเต็มคำ</td>
            </tr>
            <tr>
              <td width="24" style="vertical-align: top; color: #bf7739; font-weight: bold;">2.</td>
              <td><strong>ทานคู่เครื่องดื่มโปรด:</strong> นมสดเย็น ชาร้อน หรือกาแฟดริปเข้มข้นจะตัดรสหวานได้อย่างลงตัวที่สุด</td>
            </tr>
            <tr>
              <td width="24" style="vertical-align: top; color: #bf7739; font-weight: bold;">3.</td>
              <td><strong>การเก็บรักษา:</strong> ทานไม่หมดสามารถเก็บในตู้เย็นได้นาน 14 วัน หรืออุณหภูมิห้อง 7 วัน</td>
            </tr>
          </table>
        </div>
      </td>
    </tr>

    <!-- Call to Action Buttons -->
    <tr>
      <td align="center" style="padding: 0 26px 30px;">
        <table border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td align="center" style="border-radius: 30px; background: linear-gradient(135deg, #bf7739 0%, #5a2d18 100%);">
              <a href="https://taksin105.github.io/cookie-cozy/#menu" target="_blank" style="padding: 14px 32px; border-radius: 30px; font-size: 14px; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block;">
                ดูเมนูคุกกี้บนเว็บไซต์ ➔
              </a>
            </td>
          </tr>
        </table>
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="padding: 22px 24px; background-color: #f6eee4; text-align: center; border-top: 1px solid #ebd9c8; font-size: 11px; color: #8c7b6d; line-height: 1.6;">
        <strong>Cookie Cozy Bakery Co., Ltd.</strong><br>
        Artisan Homemade Bakery • อบสดใหม่ด้วยเนยแท้ฝรั่งเศส 100%<br>
        หากมีข้อสงสัยเกี่ยวกับคำสั่งซื้อ ติดต่อฝ่ายบริการลูกค้าได้ที่ <a href="mailto:hello@cookiecozy.com" style="color: #bf7739; text-decoration: underline;">hello@cookiecozy.com</a> หรือโทร 02-999-COZY<br>
        <span style="font-size: 10px; color: #aa9c90; margin-top: 6px; display: block;">เอกสารนี้ออกโดยระบบอัตโนมัติของ Cookie Cozy Bakery วันที่ ' . date('d/m/Y H:i:s') . '</span>
      </td>
    </tr>

  </table>

</body>
</html>';

// 6. Send Email using mailer_helper (Supports SMTP & Local Outbox File)
require_once __DIR__ . '/mailer_helper.php';

$subject = "🧾 ใบเสร็จรับเงิน & ยืนยันคำสั่งซื้อ " . $orderId . " จาก Cookie Cozy Bakery";
$sendResult = sendCookieEmail($email, $subject, $emailBody, 'Cookie Cozy Bakery');

// 7. Output JSON Response
echo json_encode([
    'status' => 'success',
    'message' => 'ยืนยันคำสั่งซื้อและจัดส่งใบเสร็จไปยังอีเมลของลูกค้าเรียบร้อยแล้ว',
    'order_id' => $orderId,
    'email' => $email,
    'grand_total' => $grandTotal,
    'points_earned' => $pointsEarned,
    'mail_sent' => $sendResult['smtp_sent'] || $sendResult['mail_sent'],
    'saved_file' => $sendResult['saved_file'],
    'email_html' => $emailBody
], JSON_UNESCAPED_UNICODE);
