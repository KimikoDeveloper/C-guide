<?php
/**
 * contact-handler.php
 *
 * Receives the POST from contact.html, validates the fields server-side
 * (never trust client-side validation alone), appends the message to
 * data/messages.json, and redirects back to contact.html with a status
 * flag so the page can show a success or error banner.
 */

declare(strict_types=1);

const DATA_FILE = __DIR__ . '/data/messages.json';
const REDIRECT_TARGET = 'contact.html';

function redirect_with_status(bool $ok, string $errorMessage = ''): void {
    $query = $ok
        ? 'sent=1'
        : 'sent=0&err=' . urlencode($errorMessage);
    header('Location: ' . REDIRECT_TARGET . '?' . $query);
    exit;
}

// Only accept POST requests — visiting this file directly via GET shouldn't do anything.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status(false, 'this endpoint only accepts form submissions.');
}

// --- Gather + sanitize input -------------------------------------------------
$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$topic   = trim((string)($_POST['topic'] ?? 'other'));
$message = trim((string)($_POST['message'] ?? ''));

$allowedTopics = ['bug', 'question', 'request', 'other'];
if (!in_array($topic, $allowedTopics, true)) {
    $topic = 'other';
}

// --- Validate -----------------------------------------------------------------
$errors = [];

if ($name === '' || mb_strlen($name) > 80) {
    $errors[] = 'name is required (max 80 characters).';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'a valid email is required.';
}
if ($message === '' || mb_strlen($message) > 2000) {
    $errors[] = 'message is required (max 2000 characters).';
}

if (!empty($errors)) {
    redirect_with_status(false, implode(' ', $errors));
}

// --- Persist --------------------------------------------------------------
// Store as a JSON array on disk. In a production app you'd use a real
// database; a flat file keeps this example dependency-free and easy to read.
$dataDir = dirname(DATA_FILE);
if (!is_dir($dataDir)) {
    // 0775 lets the web server user write while staying group-readable.
    mkdir($dataDir, 0775, true);
}

$entry = [
    'name'    => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    'email'   => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
    'topic'   => $topic,
    'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
    'sentAt'  => date(DATE_ATOM),
    'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
];

$existing = [];
if (is_readable(DATA_FILE)) {
    $raw = file_get_contents(DATA_FILE);
    $decoded = json_decode($raw ?: '[]', true);
    if (is_array($decoded)) {
        $existing = $decoded;
    }
}

$existing[] = $entry;

// LOCK_EX avoids two simultaneous submissions corrupting the file.
$written = file_put_contents(
    DATA_FILE,
    json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);

if ($written === false) {
    redirect_with_status(false, 'server could not save the message — please try again later.');
}

redirect_with_status(true);
