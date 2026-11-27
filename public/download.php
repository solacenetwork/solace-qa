<?php
declare(strict_types=1);
require_once __DIR__ . '/export-storage.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    exit('Method Not Allowed.');
}
$id = $_GET['id'] ?? null;
if (!qa_valid_uuid($id)) {
    http_response_code(404);
    exit('Archive not found.');
}
$directory = dirname(__DIR__) . '/data';
try {
    $history = qa_read_list($directory . '/export-history.json');
    foreach ($history as $batch) {
        if (($batch['export_uuid'] ?? null) !== $id) {
            continue;
        }
        $name = $batch['archive_name'] ?? null;
        if (!qa_archive_name($name)) {
            throw new RuntimeException('Invalid archive name.');
        }
        $path = $directory . '/archives/' . $name;
        if (!is_file($path) || is_link($path) || !is_readable($path)
            || hash_file('sha256', $path) !== ($batch['archive_sha256'] ?? null)
            || filesize($path) !== ($batch['archive_size'] ?? null)) {
            throw new RuntimeException('Archive unavailable or checksum mismatch.');
        }
        $stream = fopen($path, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Cannot open archive.');
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . $batch['archive_size']);
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        fpassthru($stream);
        fclose($stream);
        exit;
    }
    http_response_code(404);
    exit('Archive not found.');
} catch (Throwable $error) {
    error_log('SOLACE QA download: ' . $error->getMessage());
    http_response_code(500);
    exit('Unable to download archive.');
}
