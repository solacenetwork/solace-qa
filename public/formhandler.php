<?php
declare(strict_types=1);
date_default_timezone_set('America/New_York');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed.');
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

$directory = dirname(__DIR__) . '/data';
$lock = null;
$temp = null;
$backupTemp = null;
try {
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create data directory.');
    }
    $lock = fopen($directory . '/storage.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        throw new RuntimeException('Cannot acquire storage lock.');
    }
    $file = $directory . '/submissions.json';
    $data = [];
    if (file_exists($file)) {
        $contents = file_get_contents($file);
        if ($contents === false) {
            throw new RuntimeException('Cannot read submissions.');
        }
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !array_is_list($data)) {
            throw new RuntimeException('Invalid submissions file.');
        }
    }
    // Preserve the previous valid generation atomically before replacing it.
    if (isset($contents)) {
        $backupTemp = tempnam($directory, '.backup-');
        if ($backupTemp === false || file_put_contents($backupTemp, $contents) !== strlen($contents)
            || !rename($backupTemp, $file . '.bak')) {
            throw new RuntimeException('Cannot save previous generation.');
        }
        $backupTemp = null;
    }
    $data[] = $entry;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $temp = tempnam($directory, '.submissions-');
    if ($temp === false || file_put_contents($temp, $json) !== strlen($json) || !rename($temp, $file)) {
        throw new RuntimeException('Cannot save submissions.');
    }
    $temp = null;
} catch (Throwable $error) {
    error_log('SOLACE QA: ' . $error->getMessage());
    http_response_code(500);
    exit('Unable to save submission. Please retain your observation and retry.');
} finally {
    if (is_string($temp) && is_file($temp)) {
        unlink($temp);
    }
    if (is_string($backupTemp) && is_file($backupTemp)) {
        unlink($backupTemp);
    }
    if (is_resource($lock)) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SOLACE QA — Submission saved</title></head>
<body>
<h1>Submission saved</h1>
<p>Your test observation has been recorded.</p>
<p><a href="index.php">Submit another</a></p>
</body>
</html>
