<?php
declare(strict_types=1);

const QA_MAX_IMAGES = 5;
const QA_MAX_IMAGE_BYTES = 10 * 1024 * 1024;

function qa_uuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    $hex = bin2hex($bytes);
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
        . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
}

function qa_validate_uploads(?array $files): array
{
    if ($files === null) {
        return [];
    }
    foreach (['name', 'tmp_name', 'error', 'size'] as $key) {
        if (!isset($files[$key]) || !is_array($files[$key]) || !array_is_list($files[$key])) {
            throw new InvalidArgumentException('Invalid attachment data.');
        }
    }
    $count = count($files['error']);
    if ($count > QA_MAX_IMAGES || count($files['name']) !== $count
        || count($files['tmp_name']) !== $count || count($files['size']) !== $count) {
        throw new InvalidArgumentException('Attach no more than five screenshots.');
    }
    $uploads = [];
    $types = ['image/png' => ['png', IMAGETYPE_PNG], 'image/jpeg' => ['jpg', IMAGETYPE_JPEG],
        'image/webp' => ['webp', IMAGETYPE_WEBP]];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($files['error'] as $i => $error) {
        if ($error === UPLOAD_ERR_NO_FILE && $count === 1) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('A screenshot upload failed. Check the server upload limits and retry.');
        }
        $tmp = $files['tmp_name'][$i];
        $original = $files['name'][$i];
        if (!is_string($tmp) || !is_string($original) || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Invalid uploaded screenshot.');
        }
        $size = filesize($tmp);
        if ($size === false || $size < 1 || $size > QA_MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Each screenshot must be between 1 byte and 10 MiB.');
        }
        $mime = $finfo->file($tmp);
        $image = @getimagesize($tmp);
        if (!isset($types[$mime]) || $image === false || $image[2] !== $types[$mime][1]
            || $image[0] < 1 || $image[1] < 1) {
            throw new InvalidArgumentException('Only actual PNG, JPEG or WebP images are accepted.');
        }
        $original = basename(str_replace(chr(92), '/', $original));
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '-', pathinfo($original, PATHINFO_FILENAME));
        $safe = substr(trim($safe ?? '', '-_'), 0, 80) ?: 'screenshot';
        $uploads[] = ['tmp' => $tmp, 'name' => $safe, 'extension' => $types[$mime][0],
            'mime' => $mime, 'size' => $size];
    }
    return $uploads;
}

function qa_ensure_directory(string $directory): void
{
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create storage directory.');
    }
}

function qa_stage_json(string $directory, string $contents): string
{
    $temp = tempnam($directory, '.qa-');
    if ($temp === false) {
        throw new RuntimeException('Cannot create temporary JSON file.');
    }
    if (dirname($temp) !== realpath($directory) || file_put_contents($temp, $contents) !== strlen($contents)) {
        @unlink($temp);
        throw new RuntimeException('Cannot stage JSON file.');
    }
    return $temp;
}

function qa_store_report(string $directory, array $entry, array $uploads): string
{
    qa_ensure_directory($directory);
    // Preserve the existing stable lock path so future exports share the same lock.
    $lock = fopen($directory . '/storage.lock', 'c');
    if ($lock === false) {
        throw new RuntimeException('Cannot open storage lock.');
    }
    $temp = null;
    $backupTemp = null;
    $created = [];
    $committed = false;
    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('Cannot acquire storage lock.');
        }
        $file = $directory . '/submissions.json';
        $data = [];
        $contents = null;
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
        $ids = [];
        foreach ($data as &$report) {
            if (!is_array($report) || array_is_list($report)) {
                throw new RuntimeException('Invalid existing report.');
            }
            if (isset($report['id'])) {
                if (!is_string($report['id']) || isset($ids[$report['id']])) {
                    throw new RuntimeException('Invalid or duplicate existing report ID.');
                }
                $ids[$report['id']] = true;
            }
        }
        unset($report);
        // Preserve older observations, adding permanent IDs only when absent.
        foreach ($data as &$report) {
            if (!isset($report['id'])) {
                do { $report['id'] = qa_uuid(); } while (isset($ids[$report['id']]));
                $ids[$report['id']] = true;
            }
            if (!array_key_exists('images', $report)) {
                $report['images'] = [];
            }
        }
        unset($report);
        do { $id = qa_uuid(); } while (isset($ids[$id]));
        $entry['id'] = $id;
        $entry['images'] = [];
        if ($uploads !== []) {
            qa_ensure_directory($directory . '/pics');
        }
        foreach ($uploads as $i => $upload) {
            $relative = 'pics/' . $id . '-' . ($i + 1) . '-' . $upload['name']
                . '-' . $entry['date'] . '.' . $upload['extension'];
            $target = $directory . '/' . $relative;
            if (file_exists($target) || !move_uploaded_file($upload['tmp'], $target)) {
                throw new RuntimeException('Cannot store screenshot.');
            }
            $created[] = $target;
            if (!chmod($target, 0600)) {
                throw new RuntimeException('Cannot restrict screenshot permissions.');
            }
            $entry['images'][] = ['path' => $relative, 'mime' => $upload['mime'], 'size' => $upload['size']];
        }
        $data[] = $entry;
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        $temp = qa_stage_json($directory, $json);
        if ($contents !== null) {
            $backupTemp = qa_stage_json($directory, $contents);
            if (!rename($backupTemp, $file . '.bak')) {
                throw new RuntimeException('Cannot save previous generation.');
            }
            $backupTemp = null;
        }
        if (!rename($temp, $file)) {
            throw new RuntimeException('Cannot replace submissions.');
        }
        $temp = null;
        $committed = true;
        return $id;
    } finally {
        if (!$committed) {
            foreach ($created as $target) {
                if (!@unlink($target)) {
                    error_log('SOLACE QA: Could not remove uncommitted screenshot.');
                }
            }
        }
        foreach ([$temp, $backupTemp] as $staged) {
            if (is_string($staged)) {
                @unlink($staged);
            }
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
