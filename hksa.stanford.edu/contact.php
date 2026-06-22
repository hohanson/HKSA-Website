<?php
/* ============================================================
   HKSA WEBSITE — contact.php
   Contact form handler. Accepts POST, sends email via mail().
   Returns JSON {success: bool, message: string}.
   Deploy to: hksa.stanford.edu/contact.php
   ============================================================ */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

/* Only accept POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

/* Honeypot — bots fill hidden fields, humans don't */
if (!empty($_POST['website'])) {
    /* Silently succeed so bots don't know they were caught */
    echo json_encode(['success' => true, 'message' => 'Message sent.']);
    exit;
}

/* Recipient — loaded from contact.json; falls back to defaults if missing */
$contact_raw = @file_get_contents(__DIR__ . '/../private/hksa/contact.json');
$config      = ($contact_raw !== false) ? (json_decode($contact_raw, true) ?? []) : [];
$recipients  = array_values(array_filter($config['form_recipients'] ?? ['hohanson@stanford.edu']));
$to          = $recipients[0];
$cc          = count($recipients) > 1 ? implode(', ', array_slice($recipients, 1)) : '';
$from_name   = $config['form_sender_name']  ?? 'Stanford HKSA';
$from_email  = $config['form_sender_email'] ?? 'hohanson@stanford.edu';

/* Sanitise and validate */
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = str_replace(["\r\n", "\r"], "\n", trim($_POST['message'] ?? ''));

$errors = [];

if ($name === '') {
    $errors[] = 'Name is required.';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Name must be under 100 characters.';
}

if ($email === '') {
    $errors[] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
} elseif (mb_strlen($email) > 254) {
    $errors[] = 'Email address is too long.';
}

if ($subject === '') {
    $errors[] = 'Subject is required.';
} elseif (mb_strlen($subject) > 78) {
    $errors[] = 'Subject must be under 78 characters.';
}

if ($message === '') {
    $errors[] = 'Message is required.';
} elseif (mb_strlen($message) > 3000) {
    $errors[] = 'Message must be under 3000 characters.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

/* reCAPTCHA verification */
$recaptcha_token = trim($_POST['g-recaptcha-response'] ?? '');
$recaptcha_ok    = false;
$recaptcha_score = null;
$version         = 'v3';   /* default; overwritten from config when available */
$config_failed   = false;

/* bypass: true in recaptcha.json skips verification entirely (for testing) */
$rc_config_raw = @file_get_contents(__DIR__ . '/../private/hksa/recaptcha.json');
$rc_config_arr = ($rc_config_raw !== false) ? json_decode($rc_config_raw, true) : [];
if (!empty($rc_config_arr['bypass'])) {
    $recaptcha_ok = true;
} elseif ($recaptcha_token !== '') {
    $rc_config = $rc_config_raw;
    if ($rc_config === false) {
        $config_failed = true;            /* json missing or unreadable */
    } else {
        $rc      = json_decode($rc_config, true);
        $version = $rc['version'] ?? 'v3';
        $secret  = $rc[$version]['secret_key'] ?? '';
        if ($secret === '') {
            $config_failed = true;        /* config present but key blank */
        } else {
            $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'secret'   => $secret,
                'response' => $recaptcha_token,
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $verify = curl_exec($ch);
            curl_close($ch);
            if ($verify !== false) {
                $result = json_decode($verify, true);
                if ($version === 'v3') {
                    $recaptcha_score = $result['score'] ?? null;
                    if (
                        !empty($result['success']) &&
                        ($recaptcha_score ?? 0) >= 0.5 &&
                        ($result['action'] ?? '') === 'contact'
                    ) {
                        $recaptcha_ok = true;
                    }
                } else {
                    /* v2: just check success */
                    if (!empty($result['success'])) {
                        $recaptcha_ok = true;
                    }
                }
            }
        }
    }
}

/* Config-failure alert: gate stays closed (no spam through), but notify the admin so a
   broken/blank recaptcha.json doesn't silently take the form down. Fires only on a real
   submission (token present) that hit broken config — not on empty-token bots, and not on
   legitimate low-score rejections. Throttled to once/hour so submissions can't flood the
   inbox. Same From header as normal mail, so the existing inbox whitelist covers it. */
if ($config_failed) {
    error_log('[HKSA] reCAPTCHA config load failed — contact form is rejecting all submissions.');
    $lock = sys_get_temp_dir() . '/hksa_rc_alert.lock';
    $last = @file_exists($lock) ? (int) @file_get_contents($lock) : 0;
    if (time() - $last >= 3600) {
        @file_put_contents($lock, (string) time());
        $alert_headers  = "From: " . $from_name . " <" . $from_email . ">\r\n";
        $alert_headers .= "MIME-Version: 1.0\r\n";
        $alert_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $alert_body  = "The HKSA contact form could not load reCAPTCHA config and is\n";
        $alert_body .= "rejecting all submissions.\n\n";
        $alert_body .= "Fix: check private/hksa/recaptcha.json on cPanel.\n";
        $alert_body .= "Time: " . gmdate('Y-m-d H:i:s') . " UTC\n";
        @mail($to, '[HKSA] reCAPTCHA config failure', $alert_body, $alert_headers);
    }
}

if (!$recaptcha_ok) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Verification failed. Please refresh and try again.']);
    exit;
}

/* Compose email */

/* Strip headers from user input to prevent injection */
$safe_name    = str_replace(["\r", "\n"], '', $name);
$safe_email   = str_replace(["\r", "\n"], '', $email);
$safe_subject = str_replace(["\r", "\n"], '', $subject);
$safe_message = $message; /* line endings already normalised on input */
$safe_message = wordwrap($safe_message, 998, "\n", true);   /* hard-wrap long lines for Exim (RFC 5322 hard limit) */

$captcha_str = ($version === 'v3')
    ? (($recaptcha_score !== null) ? number_format($recaptcha_score, 2) : 'n/a')
    : 'passed';
$time_str    = gmdate('Y-m-d H:i:s') . ' UTC';
$ip_str      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

$body  = $safe_message . "\n\n";
$body .= "---\n";
$body .= "Name:     " . $safe_name . "\n";
$body .= "Email:    " . $safe_email . "\n";
$body .= "Captcha:  " . $captcha_str . "\n";
$body .= "Time:     " . $time_str . "\n";
$body .= "IP:       " . $ip_str . "\n";
$body .= "Sent via  hksa.stanford.edu\n";

$headers  = "From: " . $from_name . " <" . $from_email . ">\r\n";
$headers .= "Reply-To: " . $safe_name . " <" . $safe_email . ">\r\n";
if ($cc !== '') {
    $headers .= "Cc: " . $cc . "\r\n";
}
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($to, '[HKSA] ' . $safe_name . ': ' . $safe_subject, $body, $headers);

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Message sent. We\'ll get back to you soon.']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Something went wrong. Please try again or email us directly.']);
}
