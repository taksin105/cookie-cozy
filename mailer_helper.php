<?php
/**
 * Cookie Cozy Bakery - Mailer Helper
 * Supports direct SMTP (Gmail, Outlook, Custom) and Local Outbox File Storage
 */

function sendCookieEmail($to, $subject, $htmlBody, $fromName = 'Cookie Cozy Bakery') {
    $result = [
        'smtp_sent' => false,
        'mail_sent' => false,
        'saved_file' => null,
        'error' => null
    ];

    // 1. Always save to sent_emails folder for local review
    $dir = __DIR__ . '/sent_emails';
    if (!file_exists($dir)) {
        @mkdir($dir, 0777, true);
    }
    $safeEmail = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $to);
    $filename = date('Ymd_His') . '_' . $safeEmail . '.html';
    $filepath = $dir . '/' . $filename;
    @file_put_contents($filepath, $htmlBody);
    $result['saved_file'] = 'sent_emails/' . $filename;

    // 2. Check SMTP Config
    $configFile = __DIR__ . '/smtp_config.php';
    if (file_exists($configFile)) {
        $config = include $configFile;
        if (!empty($config['enabled']) && !empty($config['host']) && !empty($config['username']) && !empty($config['password'])) {
            $smtpResult = sendViaSMTP(
                $config['host'],
                $config['port'] ?? 587,
                $config['username'],
                $config['password'],
                $config['from_email'] ?? $config['username'],
                $fromName,
                $to,
                $subject,
                $htmlBody
            );
            if ($smtpResult === true) {
                $result['smtp_sent'] = true;
                return $result;
            } else {
                $result['error'] = $smtpResult;
            }
        }
    }

    // 3. Fallback to standard PHP mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <no-reply@cookiecozy.com>\r\n";
    $headers .= "Reply-To: hello@cookiecozy.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $result['mail_sent'] = @mail($to, $subject, $htmlBody, $headers);
    return $result;
}

function sendViaSMTP($host, $port, $user, $pass, $fromEmail, $fromName, $to, $subject, $htmlBody) {
    $user = trim($user);
    $pass = str_replace(' ', '', trim($pass));
    $fromEmail = trim($fromEmail);
    $to = trim($to);

    $timeout = 10;
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        return "Cannot connect to $host:$port ($errstr)";
    }

    $read = function($expectedCode) use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        if (substr($response, 0, 3) !== (string)$expectedCode) {
            return false;
        }
        return true;
    };

    if (!$read(220)) return "Failed greeting";

    fputs($socket, "EHLO " . gethostname() . "\r\n");
    if (!$read(250)) return "Failed EHLO";

    if ($port == 587) {
        fputs($socket, "STARTTLS\r\n");
        if (!$read(220)) return "Failed STARTTLS";

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            return "TLS handshake failed";
        }

        fputs($socket, "EHLO " . gethostname() . "\r\n");
        if (!$read(250)) return "Failed second EHLO";
    }

    fputs($socket, "AUTH LOGIN\r\n");
    if (!$read(334)) return "AUTH LOGIN rejected";

    fputs($socket, base64_encode($user) . "\r\n");
    if (!$read(334)) return "Username rejected";

    fputs($socket, base64_encode($pass) . "\r\n");
    if (!$read(235)) return "Password rejected (Authentication failed)";

    fputs($socket, "MAIL FROM: <$fromEmail>\r\n");
    if (!$read(250)) return "MAIL FROM rejected";

    fputs($socket, "RCPT TO: <$to>\r\n");
    if (!$read(250)) return "RCPT TO rejected";

    fputs($socket, "DATA\r\n");
    if (!$read(354)) return "DATA rejected";

    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";

    $headers = "From: $encodedFromName <$fromEmail>\r\n";
    $headers .= "To: <$to>\r\n";
    $headers .= "Subject: $encodedSubject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";

    $body = chunk_split(base64_encode($htmlBody));

    fputs($socket, $headers . $body . "\r\n.\r\n");
    if (!$read(250)) return "Message send failed";

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return true;
}
