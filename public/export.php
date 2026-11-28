<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');
require_once __DIR__ . '/export-storage.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed.');
}
if (!session_start()) {
    http_response_code(500);
    exit('Unable to initialize form session.');
}
$token = $_POST['csrf'] ?? '';
if (!is_string($token) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(403);
    exit('Invalid form session. Please reload the form.');
}
session_write_close();
$batch = null;
$error = false;
try {
    $batch = qa_create_export(dirname(__DIR__) . '/data');
} catch (Throwable $exception) {
    error_log('SOLACE QA export: ' . $exception->getMessage());
    http_response_code(500);
    $error = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SOLACE QA — Export</title>
<link rel="icon" href="assets/solace-logo-round-black.svg" sizes="any" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/qa.css" rel="stylesheet">
</head>
<body>
<main class="qa-main" data-bs-theme="light">
<header class="qa-header"><img src="assets/solace-logo-round-black.svg" width="40" alt=""><h1>SOLACE QA</h1></header>
<?php if ($error): ?>
<h2>Export could not be completed</h2>
<p>Check the server log. Existing reports and finalized archives are retained; retry after resolving the error.</p>
<?php elseif ($batch === null): ?>
<h2>No pending observations</h2>
<p>There are no new reports to export. Previously created archives remain in storage.</p>
<?php else: ?>
<h2>Export verified</h2>
<p><?= (int) $batch['report_count'] ?> reports and <?= (int) $batch['image_count'] ?> screenshots.</p>
<p><?= htmlspecialchars($batch['archive_name'], ENT_QUOTES, 'UTF-8') ?></p>
<p>SHA-256: <code style="overflow-wrap:anywhere"><?= htmlspecialchars($batch['archive_sha256'], ENT_QUOTES, 'UTF-8') ?></code></p>
<p><a class="btn-solid theme-primary" href="download.php?id=<?= rawurlencode($batch['export_uuid']) ?>">Download ZIP</a></p>
<?php endif; ?>
<p><a href="index.php">Return to observation form</a> · <a href="export-history.php">View export history</a></p>
</main>
</body>
</html>
