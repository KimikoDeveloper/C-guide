<?php
/**
 * leaderboard.php
 *
 * Reads data/scores.json (written by save-score.php) and renders the
 * best attempt per person, ranked by percent correct. This page has to
 * be served through PHP — visiting it as a static file will show raw
 * PHP source instead of rendering, since there's no PHP interpreter
 * involved when opening a file directly from disk.
 */

declare(strict_types=1);

const SCORES_FILE = __DIR__ . '/data/scores.json';

$scores = [];
if (is_readable(SCORES_FILE)) {
    $decoded = json_decode((string)file_get_contents(SCORES_FILE), true);
    if (is_array($decoded)) {
        $scores = $decoded;
    }
}

// Keep only each person's best attempt (by percent, then by most recent).
$best = [];
foreach ($scores as $row) {
    $key = strtolower($row['name'] ?? 'anonymous');
    if (!isset($best[$key]) || $row['percent'] > $best[$key]['percent']) {
        $best[$key] = $row;
    }
}

usort($best, function ($a, $b) {
    return $b['percent'] <=> $a['percent'];
});

$totalAttempts = count($scores);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Leaderboard — C++ Learning Guide</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="titlebar">
  <div class="wrap">
    <div class="dots"><span></span><span></span><span></span></div>
    <a class="brand" href="index.html"><span class="path">cpp-guide/</span><span class="file">leaderboard.php</span></a>
    <button class="navtoggle" aria-label="toggle menu">☰</button>
    <nav class="navlinks">
      <a href="index.html">home</a>
      <a href="data-types.html">data-types</a>
      <a href="operators.html">operators</a>
      <a href="control-flow.html">control-flow</a>
      <a href="loops.html">loops</a>
      <a href="functions.html">functions</a>
      <a href="arrays-strings.html">arrays-strings</a>
      <a href="pointers.html">pointers</a>
      <a href="quiz.html">quiz</a>
      <a href="contact.html">contact</a>
    </nav>
  </div>
</header>

<section class="hero" style="padding:48px 0 36px;">
  <div class="wrap">
    <div class="eyebrow">rendered server-side by PHP</div>
    <h1>Quiz <span class="accent">Leaderboard</span></h1>
    <p class="lede">
      Best score per person, across
      <?= htmlspecialchars((string)$totalAttempts, ENT_QUOTES, 'UTF-8') ?> total attempt<?= $totalAttempts === 1 ? '' : 's' ?>.
    </p>
  </div>
</section>

<main>
  <div class="wrap" style="max-width:760px;">

    <?php if (empty($best)): ?>
      <div class="note note-tip">
        <div class="note-head">no scores yet</div>
        Nobody has completed the <a href="quiz.html">quiz</a> on this server yet — be the first.
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr><th>rank</th><th>name</th><th>score</th><th>percent</th><th>when</th></tr>
        </thead>
        <tbody>
          <?php foreach ($best as $i => $row): ?>
            <tr>
              <td><code>#<?= $i + 1 ?></code></td>
              <td><?= htmlspecialchars($row['name'] ?? 'anonymous', ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= (int)$row['correct'] ?> / <?= (int)$row['total'] ?></td>
              <td><?= (int)$row['percent'] ?>%</td>
              <td><code><?= htmlspecialchars(substr($row['at'] ?? '', 0, 10), ENT_QUOTES, 'UTF-8') ?></code></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <p style="margin-top:26px;"><a class="btn btn-ghost" href="quiz.html">← take the quiz</a></p>
  </div>
</main>

<footer>
  <div class="wrap" style="display:flex; justify-content:space-between; width:100%; flex-wrap:wrap; gap:10px;">
    <span>cpp-guide/ — leaderboard.php</span>
    <span><a href="index.html">home</a> · <a href="contact.html">contact</a></span>
  </div>
</footer>

</body>
</html>
