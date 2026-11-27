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
$id = $_POST['id'] ?? null;
if (!qa_valid_uuid($id)) {
    http_response_code(404);
    exit('Export not found.');
}
try {
    qa_history_snapshot(dirname(__DIR__) . '/data', $id);
} catch (OutOfBoundsException $error) {
    http_response_code(404);
    exit('Export not found.');
} catch (Throwable $error) {
    error_log('SOLACE QA review: ' . $error->getMessage());
    http_response_code(500);
    exit('Unable to save review status. Return to export history and retry.');
}
header('Location: export-history.php?review=saved#export-history', true, 303);
exit;
