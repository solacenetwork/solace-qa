<?php
declare(strict_types=1);
if (!session_start()) {
    http_response_code(500);
    exit('Unable to initialize form session. Please contact the administrator.');
}
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/export-storage.php';
$csrf = $_SESSION['csrf'];
session_write_close();
header('Cache-Control: no-store');
$exportState = null;
$formatter = new IntlDateFormatter(
    'en_US', // Locale
    IntlDateFormatter::LONG,    // Date style
    IntlDateFormatter::SHORT,   // Time style
    'America/New_York'          // Timezone
);
try {
    $exportState = qa_history_snapshot(dirname(__DIR__) . '/data');
} catch (Throwable $error) {
    error_log('SOLACE QA history: ' . $error->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SOLACE QA — Export history</title>
<link rel="icon" href="assets/solace-logo-round-black.svg" sizes="any" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/qa.css" rel="stylesheet">
<script>
    function clearUrl() {
        const url = new URL(window.location.href);
        url.search = '';
        url.hash = '';
        history.replaceState({}, '', url.toString());  
    }
</script>
</head>
<body>
<main class="qa-main" data-bs-theme="light">
<header class="qa-header">
<img src="assets/solace-logo-round-black.svg" width="40" alt="">
<h1>SOLACE QA</h1>
</header>
<p><a href="index.php">Return to observation form</a></p>
<section class="qa-section" id="export-history" aria-labelledby="history-title">
<h2 id="history-title" class="fw-bold mt-4">Export history</h2>
<?php if (($_GET['review'] ?? '') === 'saved' && $exportState !== null): ?>
<div class="alert theme-success mb-2" role="alert">
  <p>Export marked as reviewed.</p>
  <button type="button" onclick="clearUrl()" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if ($exportState === null): ?>
<p class="fw-bold fg-danger">Export history is unavailable.</p>
<?php elseif ($exportState['history'] === []): ?>
<p class="fw-bold fg-danger">No archives yet.</p>
<?php else: ?>
<br>
<?php foreach ($exportState['history'] as $batch): ?>
<article class="qa-history-item">
<h3><b>Name:</b> <?= htmlspecialchars($batch['archive_name'], ENT_QUOTES, 'UTF-8') ?></h3>
<p><b>Date:</b> <?= htmlspecialchars($formatter->format(new DateTimeImmutable($batch['generated_at'])),ENT_QUOTES,'UTF-8') ?></p>
<p><?= (int) $batch['report_count'] ?> observations · <?= (int) $batch['image_count'] ?> screenshots</p>
<?php if (($batch['reviewed_at'] ?? null) !== null): ?>
<p><strong>Reviewed:</strong>
    <?= htmlspecialchars(
        $formatter->format(new DateTimeImmutable($batch['reviewed_at'])),
        ENT_QUOTES,
        'UTF-8'
    ) ?>
</p>
<?php else: ?>
<p><strong>Not reviewed</strong></p>
<?php endif; ?>
<div class="qa-actions">
<a class="btn-outline theme-primary" href="download.php?id=<?= rawurlencode($batch['export_uuid']) ?>">Download ZIP</a>
<?php if (($batch['reviewed_at'] ?? null) === null): ?>
<form action="review.php" method="post">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="id" value="<?= htmlspecialchars($batch['export_uuid'], ENT_QUOTES, 'UTF-8') ?>">
<button type="submit" class="btn-solid theme-success">Mark reviewed</button>
</form>
<?php endif; ?>
</div>
</article>
<?php endforeach; ?>
<?php endif; ?>
</section>
<p class="mt-4"><a href="index.php">Return to observation form</a></p>
<br>
</main>
<script type="module" src="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-+PW0IDVGGwmpaBQvcXY+W0BcpHpITFB3dSfSoGvDdHtLbrLpSbpGcvtQRM/BJpz+" crossorigin="anonymous"></script>
</body>
</html>
