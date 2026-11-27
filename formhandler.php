<?php
date_default_timezone_set('America/New_York');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method Not Allowed. Please <a href="index.html">go back to the form</a>.');
}

$required = ['date', 'device', 'page', 'whatidid', 'whathappened', 'reporttype'];

$errors = [];
foreach ($required as $field) {
    if (empty($_POST[$field]) || trim($_POST[$field]) === '') {
        $errors[] = $field;
    }
}

if (!empty($errors)) {
    echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
    echo '<h2>Missing Required Fields</h2>';
    echo '<p>The following fields are required but were left blank:</p><ul>';
    foreach ($errors as $e) {
        echo "<li>$e</li>";
    }
    echo '</ul>';
    echo '<a href="index.html">← Back to form</a>';
    echo '</body></html>';
    exit;
}

// Build the entry
$entry = [
    'date'          => $_POST['date'],
    'device'        => $_POST['device'],
    'orientation'   => $_POST['orientation'] ?? '',
    'page'          => $_POST['page'],
    'whatidid'      => $_POST['whatidid'],
    'whathappened'  => $_POST['whathappened'],
    'expectation'   => $_POST['expectation'] ?? '',
    'reporttype'    => $_POST['reporttype'],
    'suggestion'    => $_POST['suggestion'] ?? '',
    'severitylevel' => (int)($_POST['severitylevel'] ?? 0),
    'repeatable'    => isset($_POST['repeatable']),
    'submitted_at'  => date('Y-m-d H:i:s'),
];

// Append to JSON file
$file = __DIR__ . '/submissions.json';

$handle = fopen($file, 'c+');

if ($handle === false) {
    http_response_code(500);
    exit('Unable to open submissions file.');
}

try {
    if (!flock($handle, LOCK_EX)) {
        throw new RuntimeException('Unable to lock submissions file.');
    }

    $contents = stream_get_contents($handle);

    $data = $contents !== ''
        ? json_decode($contents, true, 512, JSON_THROW_ON_ERROR)
        : [];

    if (!is_array($data) || !array_is_list($data)) {
        throw new RuntimeException('Invalid submissions file.');
    }

    $data[] = $entry;

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    // Back up the previous submissions before writing changes.
    $backupFile = __DIR__ . '/submissions.json.bak';

    if ($contents !== '') {
        $written = file_put_contents($backupFile, $contents, LOCK_EX);
        if ($written === false || $written !== strlen($contents)) {
            throw new RuntimeException('Unable to create complete submissions backup.');
        }
    }

    rewind($handle);
    if (fwrite($handle, $json) !== strlen($json)) {
        throw new RuntimeException('Unable to write complete submission.');
    }
    ftruncate($handle, strlen($json));
    fflush($handle);
} catch (Throwable $error) {
    http_response_code(500);
    exit('Unable to save submission.');
} finally {
    flock($handle, LOCK_UN);
    fclose($handle);
}
?>
<!DOCTYPE html>
<html>
<head><title>Submission Received</title></head>
<body>
<h2>Submission Saved</h2>
<p>Your test observation has been recorded.</p>
<p><a href="index.html">← Submit another</a></p>
</body>
</html>   