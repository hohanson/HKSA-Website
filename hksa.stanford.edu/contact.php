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

/* Sanitise and validate */
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

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

/* Compose email */
$to         = 'hohanson@stanford.edu';
$from_email = trim(strtok($to, ','));

/* Strip headers from user input to prevent injection */
$safe_name    = str_replace(["\r", "\n"], '', $name);
$safe_email   = str_replace(["\r", "\n"], '', $email);
$safe_subject = str_replace(["\r", "\n"], '', $subject);
$safe_message = str_replace(["\r\n", "\r"], "\n", $message); /* normalise line endings */

$body  = $safe_message . "\n\n";
$body .= "---\n";
$body .= "Sent via hksa.stanford.edu\n";

$headers  = "From: Stanford HKSA <" . $from_email . ">\r\n";
$headers .= "Reply-To: " . $safe_name . " <" . $safe_email . ">\r\n";
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
