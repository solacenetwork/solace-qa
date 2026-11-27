<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');
if (!session_start()) {
    http_response_code(500);
    exit('Unable to initialize form session. Please contact the administrator.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed.');
}

// PHP discards both POST and FILES when post_max_size is exceeded.
if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    http_response_code(413);
    exit('Request exceeds the server upload limit. Please reduce the attachments and retry.');
}
$token = $_POST['csrf'] ?? '';
if (!is_string($token) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(403);
    exit('Invalid form session. Please reload the form.');
}
session_write_close();
$required = ['date', 'project', 'device', 'page', 'whatidid', 'whathappened', 'reporttype', 'reproducibility'];
$optional = ['orientation', 'expectation', 'suggestion', 'app_version', 'device_model', 'os', 'browser', 'context', 'viewport', 'screen', 'pixel_ratio', 'interface_mode', 'scenario_id'];
$entry = [];
foreach (array_merge($required, $optional) as $field) {
    $value = $_POST[$field] ?? '';
    if (!is_string($value) || strlen($value) > 20000 || (in_array($field, $required, true) && trim($value) === '')) {
        http_response_code(400);
        exit('Invalid or missing form field. Please return to the form.');
    }
    $entry[$field] = trim($value);
}
$choices = [
    'device' => ['Desktop', 'Laptop', 'Mobile phone', 'Tablet', 'Television', 'Other'],
    'orientation' => ['Landscape', 'Portrait', 'Unknown'],
    'context' => ['Browser tab', 'Installed PWA', 'Other', 'Unknown'],
    'interface_mode' => ['Simple', 'Standard', 'Not applicable', 'Unknown'],
    'reporttype' => ['Functional defect', 'Workflow friction', 'Visual or responsive issue', 'Browser/PWA compatibility issue', 'Accessibility issue', 'Performance issue', 'Automated test coverage gap', 'Future idea', 'Other'],
    'reproducibility' => ['Always', 'Sometimes', 'Once', 'Not retested'],
];
foreach ($choices as $field => $allowed) {
    if ($entry[$field] !== '' && !in_array($entry[$field], $allowed, true)) {
        http_response_code(400);
        exit('Invalid selection.');
    }
}
$parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $entry['date']);
if (!$parsedDate || $parsedDate->format('Y-m-d') !== $entry['date']) {
    http_response_code(400);
    exit('Invalid date.');
}
$severity = filter_var($_POST['severitylevel'] ?? '0', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10]]);
if ($severity === false) {
    http_response_code(400);
    exit('Invalid severity.');
}
$entry['severitylevel'] = $severity;

$entry['submitted_at'] = date(DATE_ATOM);

require_once __DIR__ . '/storage.php';
try {
    $uploads = qa_validate_uploads($_FILES['screenshots'] ?? null);
    $id = qa_store_report(dirname(__DIR__) . '/data', $entry, $uploads);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    exit(htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8'));
} catch (Throwable $error) {
    error_log('SOLACE QA: ' . $error->getMessage());
    http_response_code(500);
    exit('Unable to save submission. Please retain your observation and retry.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SOLACE QA — Submission saved</title></head>
<body>
<h1>Submission saved</h1>
<p>Your test observation has been recorded.</p>
<p>Report ID: <code><?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?></code></p>
<p><a href="index.php">Submit another</a></p>
</body>
</html>
