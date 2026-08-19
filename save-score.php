<?php
/**
 * save-score.php
 *
 * Called via fetch() from assets/js/script.js whenever someone finishes
 * the quiz on quiz.html. Accepts a name, a quiz identifier, and a
 * correct/total pair, then appends a row to data/scores.json.
 *
 * Returns JSON so the caller could react to it (the current front-end
 * fires-and-forgets, but the response is here for completeness / reuse).
 */

declare(strict_types=1);

header('Content-Type: application/json');

const SCORES_FILE = __DIR__ . '/data/scores.json';

function respond(int $httpCode, array $payload): void {
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'POST only']);
}

$name    = trim((string)($_POST['name'] ?? 'anonymous'));
$quiz    = trim((string)($_POST['quiz'] ?? 'full-quiz'));
$correct = filter_var($_POST['correct'] ?? null, FILTER_VALIDATE_INT);
$total   = filter_var($_POST['total'] ?? null, FILTER_VALIDATE_INT);

if ($name === '') {
    $name = 'anonymous';
}
$name = mb_substr($name, 0, 24);

if ($correct === false || $total === false || $total <= 0 || $correct < 0 || $correct > $total) {
    respond(400, ['ok' => false, 'error' => 'invalid score payload']);
}

$dataDir = dirname(SCORES_FILE);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$scores = [];
if (is_readable(SCORES_FILE)) {
    $decoded = json_decode((string)file_get_contents(SCORES_FILE), true);
    if (is_array($decoded)) {
        $scores = $decoded;
    }
}

$scores[] = [
    'name'    => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
    'quiz'    => htmlspecialchars($quiz, ENT_QUOTES, 'UTF-8'),
    'correct' => $correct,
    'total'   => $total,
    'percent' => (int)round(($correct / $total) * 100),
    'at'      => date(DATE_ATOM),
];

// Keep only the most recent 200 attempts so the file doesn't grow forever.
if (count($scores) > 200) {
    $scores = array_slice($scores, -200);
}

$written = file_put_contents(
    SCORES_FILE,
    json_encode($scores, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    LOCK_EX
);

if ($written === false) {
    respond(500, ['ok' => false, 'error' => 'could not write scores file']);
}

respond(200, ['ok' => true]);
