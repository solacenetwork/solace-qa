<?php
declare(strict_types=1);
require_once __DIR__ . '/storage.php';

const QA_EXPORT_FORMAT = 1;
const QA_GENERATOR_VERSION = 'QA-4';

function qa_json(array $value): string
{
    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
}

function qa_read_list(string $path): array
{
    if (!file_exists($path)) {
        return [];
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Cannot read dataset.');
    }
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !array_is_list($data)) {
        throw new RuntimeException('Invalid dataset.');
    }
    return $data;
}

function qa_valid_uuid(mixed $id): bool
{
    return is_string($id) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $id) === 1;
}

function qa_archive_name(mixed $name): bool
{
    return is_string($name) && preg_match('/^qa-reports-\d{4}-\d{2}-\d{2}-\d{3,}\.zip$/D', $name) === 1;
}

function qa_report_inventory(array $reports): array
{
    $dataset = [];
    $paths = [];
    foreach ($reports as $report) {
        if (!is_array($report) || !qa_valid_uuid($report['id'] ?? null) || isset($dataset[$report['id']])
            || !isset($report['images']) || !is_array($report['images']) || !array_is_list($report['images'])) {
            throw new RuntimeException('Invalid or missing report ID or image inventory.');
        }
        $id = $report['id'];
        $dataset[$id] = [];
        foreach ($report['images'] as $image) {
            $path = $image['path'] ?? null;
            if (!is_string($path) || !preg_match('/^pics\/' . preg_quote($id, '/') . '-[1-9][0-9]*-[A-Za-z0-9_-]+-\d{4}-\d{2}-\d{2}\.(png|jpg|webp)$/D', $path)
                || isset($paths[$path]) || !is_int($image['size'] ?? null) || $image['size'] < 1) {
                throw new RuntimeException('Invalid or duplicate screenshot association.');
            }
            $paths[$path] = $image['size'];
            $dataset[$id][] = $path;
        }
    }
    return [$dataset, $paths];
}

function qa_zip_digest(ZipArchive $zip, string $name): array
{
    $stream = $zip->getStream($name);
    if ($stream === false) {
        throw new RuntimeException('Cannot read archived file.');
    }
    try {
        $hash = hash_init('sha256');
        $size = hash_update_stream($hash, $stream);
        if (!feof($stream)) {
            throw new RuntimeException('Incomplete archive read.');
        }
        return ['size' => $size, 'sha256' => hash_final($hash)];
    } finally {
        fclose($stream);
    }
}

// Verify the actual archive, not just the source files or ZIP central directory.
function qa_verify_archive(string $path): array
{
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CHECKCONS | ZipArchive::RDONLY) !== true) {
        throw new RuntimeException('Cannot open archive for verification.');
    }
    try {
        $manifestRaw = $zip->getFromName('manifest.json');
        $reportsRaw = $zip->getFromName('submissions.json');
        if ($manifestRaw === false || $reportsRaw === false) {
            throw new RuntimeException('Archive metadata missing.');
        }
        $manifest = json_decode($manifestRaw, true, 512, JSON_THROW_ON_ERROR);
        $reports = json_decode($reportsRaw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest) || ($manifest['format_version'] ?? null) !== QA_EXPORT_FORMAT
            || !qa_valid_uuid($manifest['export_uuid'] ?? null)
            || !qa_archive_name($manifest['archive_name'] ?? null)
            || ($manifest['checksum_algorithm'] ?? null) !== 'sha256'
            || !is_string($manifest['generated_at'] ?? null)
            || !is_string($manifest['generator_version'] ?? null)
            || !is_array($reports) || !array_is_list($reports) || $reports === []) {
            throw new RuntimeException('Invalid archive metadata.');
        }
        [$dataset, $paths] = qa_report_inventory($reports);
        if (($manifest['dataset'] ?? null) !== $dataset
            || ($manifest['report_count'] ?? null) !== count($reports)
            || ($manifest['image_count'] ?? null) !== count($paths)) {
            throw new RuntimeException('Archive counts or associations differ.');
        }
        $expected = ['submissions.json' => strlen($reportsRaw)] + $paths;
        $inventory = $manifest['files'] ?? null;
        if (!is_array($inventory) || !array_is_list($inventory) || count($inventory) !== count($expected)
            || $zip->numFiles !== count($expected) + 1) {
            throw new RuntimeException('Archive file count differs.');
        }
        $seen = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === false || isset($seen[$name]) || ($name !== 'manifest.json' && !array_key_exists($name, $expected))) {
                throw new RuntimeException('Unexpected or duplicate archive entry.');
            }
            $seen[$name] = true;
        }
        $verified = [];
        foreach ($inventory as $item) {
            $name = $item['path'] ?? null;
            if (!is_string($name) || isset($verified[$name]) || !array_key_exists($name, $expected)) {
                throw new RuntimeException('Invalid file inventory.');
            }
            $digest = qa_zip_digest($zip, $name);
            if ($digest['size'] !== $expected[$name] || ($item['size'] ?? null) !== $digest['size']
                || ($item['sha256'] ?? null) !== $digest['sha256']) {
                throw new RuntimeException('Archive checksum or size mismatch.');
            }
            $verified[$name] = true;
        }
        return $manifest;
    } finally {
        $zip->close();
    }
}

function qa_history_record(array $manifest, string $archive): array
{
    $hash = hash_file('sha256', $archive);
    $size = filesize($archive);
    if ($hash === false || $size === false) {
        throw new RuntimeException('Cannot calculate archive checksum.');
    }
    return [
        'export_uuid' => $manifest['export_uuid'],
        'generated_at' => $manifest['generated_at'],
        'archive_name' => $manifest['archive_name'],
        'report_ids' => array_keys($manifest['dataset']),
        'report_count' => $manifest['report_count'],
        'image_count' => $manifest['image_count'],
        'archive_size' => $size,
        'archive_sha256' => $hash,
        'reviewed_at' => null,
    ];
}

function qa_save_history(string $directory, array $history): void
{
    $temp = qa_stage_json($directory, qa_json($history));
    try {
        if (!rename($temp, $directory . '/export-history.json')) {
            throw new RuntimeException('Cannot save export history.');
        }
    } finally {
        if (is_file($temp)) {
            @unlink($temp);
        }
    }
}

// Finalized archives are a durable recovery record. Never silently ignore a
// finalized archive that is missing from history, or re-export its reports.
function qa_reconcile_history(string $directory, array $reports): array
{
    $history = qa_read_list($directory . '/export-history.json');
    [$dataset] = qa_report_inventory($reports);
    $names = [];
    $exports = [];
    $exported = [];
    $archives = $directory . '/archives';
    foreach ($history as $batch) {
        $name = $batch['archive_name'] ?? null;
        $id = $batch['export_uuid'] ?? null;
        $ids = $batch['report_ids'] ?? null;
        if (!qa_archive_name($name) || !qa_valid_uuid($id) || isset($names[$name]) || isset($exports[$id])
            || !is_array($ids) || !array_is_list($ids) || $ids === []
            || ($batch['report_count'] ?? null) !== count($ids)
            || !is_file($archives . '/' . $name) || is_link($archives . '/' . $name)) {
            throw new RuntimeException('Invalid history or missing historical archive.');
        }
        foreach ($ids as $reportId) {
            if (!qa_valid_uuid($reportId) || !isset($dataset[$reportId]) || isset($exported[$reportId])) {
                throw new RuntimeException('Invalid or duplicate exported report ID.');
            }
            $exported[$reportId] = true;
        }
        $names[$name] = true;
        $exports[$id] = true;
    }
    $entries = scandir($archives);
    if ($entries === false) {
        throw new RuntimeException('Cannot list archives.');
    }
    $changed = false;
    foreach ($entries as $name) {
        if (!qa_archive_name($name) || isset($names[$name])) {
            continue;
        }
        $path = $archives . '/' . $name;
        if (!is_file($path) || is_link($path)) {
            throw new RuntimeException('Invalid finalized archive path.');
        }
        $manifest = qa_verify_archive($path);
        if ($manifest['archive_name'] !== $name || isset($exports[$manifest['export_uuid']])) {
            throw new RuntimeException('Conflicting recovered archive.');
        }
        foreach ($manifest['dataset'] as $reportId => $images) {
            if (!isset($dataset[$reportId]) || isset($exported[$reportId]) || $dataset[$reportId] !== $images) {
                throw new RuntimeException('Recovered archive conflicts with stored reports.');
            }
            $exported[$reportId] = true;
        }
        $history[] = qa_history_record($manifest, $path);
        $exports[$manifest['export_uuid']] = true;
        $names[$name] = true;
        $changed = true;
    }
    if ($changed) {
        qa_save_history($directory, $history);
    }
    return [$history, $exported];
}

function qa_create_export(string $directory): ?array
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('PHP ZipArchive is required.');
    }
    qa_ensure_directory($directory);
    $lock = fopen($directory . '/storage.lock', 'c');
    if ($lock === false) {
        throw new RuntimeException('Cannot open storage lock.');
    }
    $temp = null;
    try {
        if (!flock($lock, LOCK_EX)) {
            throw new RuntimeException('Cannot acquire storage lock.');
        }
        qa_ensure_directory($directory . '/archives');
        $reports = qa_read_list($directory . '/submissions.json');
        [$history, $exported] = qa_reconcile_history($directory, $reports);
        $pending = array_values(array_filter($reports, static fn(array $r): bool => !isset($exported[$r['id']])));
        if ($pending === []) {
            return null;
        }
        [$dataset, $paths] = qa_report_inventory($pending);
        $date = date('Y-m-d');
        $sequence = 1;
        do {
            $name = sprintf('qa-reports-%s-%03d.zip', $date, $sequence++);
            $destination = $directory . '/archives/' . $name;
        } while (file_exists($destination) || is_link($destination));
        $id = qa_uuid();
        $json = qa_json($pending);
        $inventory = [['path' => 'submissions.json', 'size' => strlen($json), 'sha256' => hash('sha256', $json)]];
        $temp = tempnam($directory . '/archives', '.export-');
        if ($temp === false || dirname($temp) !== realpath($directory . '/archives')) {
            throw new RuntimeException('Cannot stage archive.');
        }
        $zip = new ZipArchive();
        if ($zip->open($temp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create archive.');
        }
        try {
            if (!$zip->addFromString('submissions.json', $json)) {
                throw new RuntimeException('Cannot add reports.');
            }
            foreach ($paths as $relative => $size) {
                $source = $directory . '/' . $relative;
                if (!is_file($source) || is_link($source) || realpath(dirname($source)) !== realpath($directory . '/pics')
                    || filesize($source) !== $size) {
                    throw new RuntimeException('Screenshot missing or size changed.');
                }
                $hash = hash_file('sha256', $source);
                if ($hash === false || !$zip->addFile($source, $relative)) {
                    throw new RuntimeException('Cannot add screenshot.');
                }
                $inventory[] = ['path' => $relative, 'size' => $size, 'sha256' => $hash];
            }
            $manifest = [
                'format_version' => QA_EXPORT_FORMAT, 'generator_version' => QA_GENERATOR_VERSION,
                'export_uuid' => $id, 'generated_at' => date(DATE_ATOM), 'archive_name' => $name,
                'report_count' => count($pending), 'image_count' => count($paths),
                'checksum_algorithm' => 'sha256', 'dataset' => $dataset, 'files' => $inventory,
            ];
            if (!$zip->addFromString('manifest.json', qa_json($manifest))) {
                throw new RuntimeException('Cannot add manifest.');
            }
        } finally {
            if (!$zip->close()) {
                throw new RuntimeException('Cannot finalize temporary archive.');
            }
        }
        if (qa_verify_archive($temp) !== $manifest) {
            throw new RuntimeException('Manifest changed during archive creation.');
        }
        if (!chmod($temp, 0600) || !rename($temp, $destination)) {
            throw new RuntimeException('Cannot finalize archive.');
        }
        $temp = null;
        // A crash or history write failure after this point leaves a verified
        // archive that the next export reconciles before selecting pending IDs.
        $batch = qa_history_record($manifest, $destination);
        $history[] = $batch;
        qa_save_history($directory, $history);
        return $batch;
    } finally {
        if (is_string($temp) && is_file($temp)) {
            @unlink($temp);
        }
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
