<?php
declare(strict_types=1);
if (!session_start()) {
    http_response_code(500);
    exit('Unable to initialize form session. Please contact the administrator.');
}
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SOLACE QA</title>
<link rel="icon" href="assets/solace-logo-round-black.svg" sizes="any" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@6.0.0-alpha.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/qa.css" rel="stylesheet">
<script src="assets/qa.js" defer></script>
</head>
<body>
<main class="qa-main" data-bs-theme="light">
<header class="qa-header">
<img src="assets/solace-logo-round-black.svg" width="40" alt="">
<h1>SOLACE QA</h1>
</header>
<p>Capture a testing observation. Required fields are marked *.</p>
<form action="formhandler.php" method="post" enctype="multipart/form-data" id="observation-form">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
<section class="qa-section" aria-labelledby="environment-title">
<details id="environment-details" open>
<summary><span id="environment-title">Testing environment</span><span id="environment-summary"></span></summary>
<div class="qa-environment-body">
<p>Describe the application being tested, not necessarily this reporting browser. Detected values can be edited.</p>
<div class="qa-grid">
<div class="form-field"><label for="project" class="form-label">Project *</label><input type="text" class="form-control" id="project" name="project" maxlength="250" placeholder="SOLACE Garden" required></div>
<div class="form-field"><label for="app_version" class="form-label">Application version / build</label><input type="text" class="form-control" id="app_version" name="app_version" maxlength="250" placeholder="0.0.3-alpha" ></div>
<div class="form-field"><label for="device" class="form-label">Device *</label><select class="form-control" id="device" name="device" required><option value="">Select</option><option>Desktop</option><option>Laptop</option><option>Mobile phone</option><option>Tablet</option><option>Television</option><option>Other</option></select></div>
<div class="form-field"><label for="device_model" class="form-label">Device model</label><input type="text" class="form-control" id="device_model" name="device_model" maxlength="250" placeholder="Galaxy S22" ></div>
<div class="form-field"><label for="os" class="form-label">Operating system / version</label><input type="text" class="form-control" id="os" name="os" maxlength="250" placeholder="Android 16" ></div>
<div class="form-field"><label for="browser" class="form-label">Browser / version</label><input type="text" class="form-control" id="browser" name="browser" maxlength="250" placeholder="Vivaldi" ></div>
<div class="form-field"><label for="orientation" class="form-label">Screen orientation</label><select class="form-control" id="orientation" name="orientation" ><option value="">Select</option><option>Landscape</option><option>Portrait</option><option>Unknown</option></select></div>
<div class="form-field"><label for="context" class="form-label">Execution context</label><select class="form-control" id="context" name="context" ><option value="">Select</option><option>Browser tab</option><option>Installed PWA</option><option>Other</option><option>Unknown</option></select></div>
<div class="form-field"><label for="viewport" class="form-label">Viewport (CSS pixels)</label><input type="text" class="form-control" id="viewport" name="viewport" maxlength="250" placeholder="390 × 844" ></div>
<div class="form-field"><label for="screen" class="form-label">Screen (CSS pixels)</label><input type="text" class="form-control" id="screen" name="screen" maxlength="250" placeholder="" ></div>
<div class="form-field"><label for="pixel_ratio" class="form-label">Device pixel ratio</label><input type="text" class="form-control" id="pixel_ratio" name="pixel_ratio" maxlength="250" placeholder="" ></div>
<div class="form-field"><label for="interface_mode" class="form-label">Interface mode</label><select class="form-control" id="interface_mode" name="interface_mode" ><option value="">Select</option><option>Simple</option><option>Standard</option><option>Not applicable</option><option>Unknown</option></select></div>
</div>
<div class="qa-actions">
    <button type="button" class="btn-outline theme-danger" id="forget-environment">Forget saved environment</button>
    <button type="button" class="btn-outline theme-primary" id="detect-environment">Use this browser's environment</button>
</div>
<div class="form-field"><input type="checkbox" class="check" id="remember-environment" checked><label for="remember-environment">Remember environment for future reports on this browser</label></div>
<p id="environment-message" role="status"></p>
</div>
</details>
</section>
<section class="qa-section" aria-labelledby="scenario-title">
<h2 id="scenario-title">Testing scenario</h2>
<div class="qa-grid">
<div class="form-field"><label for="date" class="form-label">Date *</label><input type="date" class="form-control" name="date" id="date" required></div>
<div class="form-field"><label for="scenario_id" class="form-label">Scenario ID</label><input type="text" class="form-control" id="scenario_id" name="scenario_id" maxlength="250" placeholder="G-04" ></div>
<div class="form-field"><label for="page" class="form-label">Page or workflow *</label><input type="text" class="form-control" id="page" name="page" maxlength="250" placeholder="/gardens" required></div>
</div>
</section>
<section class="qa-section" aria-labelledby="observation-title">
<h2 id="observation-title">Observation</h2>
<div class="form-field"><label for="whatidid" class="form-label">What I did *</label><textarea class="form-control" name="whatidid" id="whatidid" rows="4" maxlength="5000" required></textarea></div>
<div class="form-field"><label for="whathappened" class="form-label">What happened *</label><textarea class="form-control" name="whathappened" id="whathappened" rows="4" maxlength="5000" required></textarea></div>
<div class="form-field"><label for="expectation" class="form-label">What I expected / why it felt awkward</label><textarea class="form-control" name="expectation" id="expectation" rows="4" maxlength="5000" ></textarea></div>
<div class="form-field"><label for="suggestion" class="form-label">Possible improvement</label><textarea class="form-control" name="suggestion" id="suggestion" rows="4" maxlength="5000" ></textarea></div>
<div class="qa-grid">
<div class="form-field"><label for="reporttype" class="form-label">Report type *</label><select class="form-control" id="reporttype" name="reporttype" required><option value="">Select</option><option>Functional defect</option><option>Workflow friction</option><option>Visual or responsive issue</option><option>Browser/PWA compatibility issue</option><option>Accessibility issue</option><option>Performance issue</option><option>Automated test coverage gap</option><option>Future idea</option><option>Other</option></select></div>
<div class="form-field"><label for="reproducibility" class="form-label">Reproducibility *</label><select class="form-control" id="reproducibility" name="reproducibility" required><option value="">Select</option><option>Always</option><option>Sometimes</option><option>Once</option><option>Not retested</option></select></div>
<div class="form-field"><label for="severitylevel" class="form-label">Severity (0 = not rated, 10 = high)</label><div class="form-range"><input type="range" class="form-range-input" id="severitylevel" name="severitylevel" min="0" max="10" step="1" value="0" list="severity-level-list"></div><datalist id="severity-level-list"><option value="0" label="Not Rated"></option><option value="5" label="Medium"></option><option value="10" label="High"></option></datalist><div class="qa-range-labels" aria-hidden="true"><span>Not rated</span><span>Medium</span><span>High</span></div><output for="severitylevel" id="severity-value">0</output></div>
</div>
</section>
<section class="qa-section" aria-labelledby="screenshots-title">
<h2 id="screenshots-title">Screenshots (optional)</h2>
<div class="form-field">
<label for="screenshots" class="form-label">Attach screenshots</label>
<input type="file" class="form-control" id="screenshots" name="screenshots[]" accept="image/png,image/jpeg,image/webp" multiple aria-describedby="screenshots-help">
<small id="screenshots-help" class="form-text">Up to 5 PNG, JPEG or WebP images, 10 MiB each. Images are stored without resizing or recompression.</small>
</div>
</section>
<div class="qa-actions"><button type="reset" class="btn-outline theme-danger">Reset observation</button><button type="submit" class="btn-solid theme-success">Submit observation</button></div>
</form>
<section class="qa-section" aria-labelledby="export-title">
<h2 id="export-title">Export observations</h2>
<p>Create a verified ZIP of reports not previously exported. Existing observations and archives are retained.</p>
<form action="export.php" method="post">
<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">
<button type="submit" class="btn-solid theme-primary">Export pending observations</button>
</form>
</section>
</main>
</body>
</html>
