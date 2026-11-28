# SOLACE QA testing

## Local test setup

Run from the repository root using a private PHP session directory (confirmed on LMDE during QA-2 acceptance on 2026-11-27):

```sh
mkdir -p -m 700 "$HOME/.php-sessions/solace-qa"
php -d session.save_path="$HOME/.php-sessions/solace-qa" -d upload_max_filesize=10M -d post_max_size=55M -d max_file_uploads=20 \
    -S localhost:8001 -t public
```

Open http://localhost:8001/. Use another unused port if necessary. If submission returns HTTP 403 with `Invalid form session. Please reload the form.`, check the PHP server log for `session_start()` permission or session-read failures. Restart with the above session path and reload the form; clear the localhost `PHPSESSID` cookie if needed. Do not weaken system PHP session permissions. Retry the submission and verify the report in `data/submissions.json`.

Application hardening implemented in QA-3: both form and submission endpoints check the return value of `session_start()` and return HTTP 500 on initialization failure. Controlled failure-path testing remains deferred; production PHP must log errors rather than display internal diagnostic details. The local launch workaround does not replace application error handling.

## QA-1 acceptance (local functional pass, 2026-11-27)
1. Start PHP with public/ as document root. Verify SOLACE QA title and round logo at 80 CSS px width.
2. Submit a meaningful observation; verify data/submissions.json outside public/ contains it.
3. Submit a second observation; verify both persist and invalid submissions do not alter data.
4. Verify /data/submissions.json cannot be downloaded directly. Local curl returns the application form, not JSON. Isolation passes locally; verify independently on Hostinger.
5. On Hostinger, verify authenticated access, HTTPS, PHP write permission to sibling data/ and availability of `fileinfo`, `zip` and `intl` (QA-6 hosted verification). Do not deploy a diagnostic phpinfo page.
6. Verify redeploying public/ does not replace report data or authentication configuration.

## QA-2 acceptance (accepted with notes, 2026-11-27)

Tester reported all 19 manual acceptance checks passed and accepted the 10 refinement checks with one visual exception. The local PHP session permission issue was resolved using the setup above; successful submission to JSON was confirmed.

Acceptance notes:
- Tester reordered the environment actions locally: Forget on the left, detection/action on the right, consistent with Reset and Submit. Preserve this ordering when the change is committed.
- Severity slider thumb position, labels, numeric output and saved values work, but the blue track fill does not follow the thumb. Non-blocking visual issue; correct and retest at 0, 5 and 10, including reset, in a subsequent refinement.
- Session initialization error handling remains a follow-up as described above.

1. Verify 40px logo beside heading, responsive wrap, alpha.2 CSS loads.
2. Verify project, environment, scenario, observation and classification fields; date defaults to today.
3. Use browser detection; verify editable values and no assumption they represent target application.
4. Enter meaningful environment and observation, submit, return; environment retained, observation cleared.
5. Reset observation retains environment and clears observation fields. Forget saved environment and reload; no stored values. Uncheck remember and submit; no stored values.
6. Verify new report fields in JSON and previous valid primary copied to .bak. Existing records unchanged.
7. Invalid required field, date, selection or CSRF token must not change primary/backup.
8. Verify desktop/mobile layout and keyboard operation.
9. Environment is open without saved required values, collapsed with saved project/device, and opens for invalid hidden fields. Summary reflects current values. Verify single Interface Mode field.
10. Verify primary/danger outline environment actions, green success submit button, range track/thumb, labels and live value at 0, 5 and 10; reset returns severity to 0.

Later acceptance must cover multiple devices, environment overrides, attachments, failed uploads, concurrent writes/exports, interrupted export recovery, checksum/count validation, repeat export exclusion, archive re-download and reviewed state.

## QA-3 acceptance (accepted with technical testing limitations, 2026-11-27)

Tester confirmed all 26 manual acceptance checks passed, including the QA-2 slider refinement and environment button ordering, UUID and JSON persistence, screenshot handling, validation and responsive behavior.

Additional observed results:
- A PNG incorrectly named Pt7qrUao9iP4.jpeg was identified as image/png by both Linux file and PHP Fileinfo. SOLACE QA stored it with a .png extension; SHA-256 hashes of the original and stored file matched.
- Two JPEG files named screenshot1.jpg and screenshot1.jpeg normalized to the same base name and extension but were stored separately using attachment ordinals 1 and 2.
- XCF, PDF and PHP files were rejected. A 16 MB image was also rejected; this confirms rejection of an oversized individual file, not the aggregate request limit.
- Tester clarified the attachment help text to state that images are stored without resizing or recompression (commit bb2a835c).

Technical testing disposition (single-user internal utility):
- Concurrent submissions, controlled screenshot/JSON write failures and rollback cleanup, malformed/partial upload simulation, aggregate post_max_size rejection, and HTTP 500 session-initialization failure were not independently exercised; deferred, not passed.
- Session-related HTTP 403 rejection was observed in QA-2; deliberate invalid-CSRF-token testing was not independently confirmed. Keep CSRF protection, locking, atomic writes, backups and error handling in place.
- Revisit controlled failure and concurrency checks as usage expands, particularly before multi-user deployment or a potential MariaDB backend. Hosted configuration and storage isolation remain QA-6 checks.
- For LAN testing, replace `-S localhost:8001` with `-S 0.0.0.0:8001` in the local setup command and connect using the host's LAN IP. The development server is unauthenticated; use only on a trusted LAN and stop it afterward.

The original QA-3 verification scope follows for reference; technical cases listed above remain unverified.

1. Submit without attachments; verify a unique UUID v4 in id, images: [] and success page ID.
2. Submit PNG, JPEG and WebP screenshots together; verify each relative path, MIME and byte count, and compare originals to stored files (SHA-256 or cmp).
3. Submit two files with the same basename; verify separate filenames and original bytes.
4. Submit an invalid file renamed .png, an oversized file and more than five files; each request must be rejected without new report or image files. Verify malformed/partial uploads and requests beyond post_max_size likewise fail.
5. Verify old reports retain all prior fields and get stable IDs and empty images arrays on the next successful submission; verify backup contains the exact prior dataset.
6. Verify two successful submissions retain distinct IDs; concurrent writes must retain both observations.
7. Verify failure to write screenshots or JSON does not commit a partial report; ordinary failures remove newly moved files. A hard process kill can leave unreferenced files, which must not enter exports.
8. Verify private image paths are not served directly and attachment selection is cleared by reset.
9. Verify CSRF rejection and session initialization failure (HTTP 500) without data mutation. With display_errors disabled, no internal paths should appear in the response.
10. Verify severity track follows the thumb at 0, 5, 10 and resets to 0; verify environment button order remains Forget then Detect.
11. Verify desktop and phone attachment picker and successful submission. Hosted upload configuration and isolation remain QA-6 checks.

## QA-4 acceptance (accepted, 2026-11-27)

All 28 manual acceptance checks passed. Two ZIPs were exchanged and independently checked: batch 001 contained 13 reports and 11 screenshots; batch 002 contained 1 report and 1 screenshot. Payload inventories, sizes, SHA-256 hashes and associations matched; report UUIDs did not overlap. Both complete ZIP hashes matched the tester's local hashes. Repeat export with no pending reports created no archive. Historical download by export UUID worked. Original data and export history remained intact.

Controlled interruption, write-failure, concurrent export and deliberate endpoint security simulations were not independently confirmed; retain the safeguards and treat these cases as unverified. QA-5 adds the pending count and disables export at zero as requested during acceptance.


Check `php -m | grep -i '^zip$'` before testing. Use disposable fixture data for any failure simulations.

1. Export existing pending observations using the new button. Confirm verified counts and download the ZIP.
2. Confirm daily filename qa-reports-YYYY-MM-DD-001.zip (or next available sequence), private archive in data/archives and matching export-history.json entry.
3. Open ZIP; confirm manifest.json, submissions.json, and only referenced pics/ entries. Verify report IDs, image counts, dataset mapping and file inventory.
4. Independently verify SHA-256 and byte sizes of all payload entries, and ZIP checksum against history. Compare reports and images to originals.
5. Export again without submitting: no pending observations, no new ZIP or history entry.
6. Submit another observation (including a screenshot if desired), export again: only new report(s) included, incremented filename, distinct export UUID, prior archive/history intact.
7. Verify original submissions.json and pics/ remain unchanged by export; reviewed_at stays null.
8. Verify download link works again and invalid UUID/path requests cannot serve private files. Direct archive URL must not expose ZIP bytes.
9. Optional controlled fixture checks: missing referenced screenshot prevents export, restoring it permits retry; remove only the last history record from a disposable dataset and retry export to verify recovery of the finalized ZIP without duplicate creation. Do not edit actual QA history for testing.
10. Verify POST and CSRF protection; history interface and review mutation are QA-5.

## QA-5 acceptance (accepted, 2026-11-27)

Tester confirmed all remaining manual acceptance checks passed on desktop and phone, including the 32-item functional checklist and seven additional navigation checks. The history list was moved to a separate export-history.php page, keeping the observation form compact. Pending counts and disabled export at zero, historical downloads, independent review status, persisted review timestamps and incremental exports passed.

Presentation refinements accepted:
- Generation and review dates use IntlDateFormatter (en_US, LONG/SHORT, America/New_York); JSON retains ISO 8601 timestamps.
- The tester verified a reviewed archive's timestamp in export-history.json while the other two archives remained unreviewed.
- The Bootstrap 6 dismissible review-success alert was tested successfully. Reload before dismissal intentionally retains the alert; dismissal cleans the query string and fragment so it does not reappear on reload.
- Desktop and phone layout, navigation and usability passed; preserve the tester's styling refinements.

Original functional scope for regression:
1. Verify pending/exported/archive counts, export disabled at zero, and enabled after submission.
2. Verify the observation form shows only the summary and controls. View export history opens export-history.php; archive list is newest first, with correct filenames, dates, counts and review state. Return navigation works.
3. Download previous ZIPs and compare checksums.
4. Mark a batch reviewed; verify redirect, persisted timestamp and independent review status. Counts and ZIP contents remain unchanged.
5. Submit and export additional observations while earlier batches remain unreviewed; verify incremental selection, count updates and prior review state.
6. Verify desktop/phone layout, keyboard operation, wrapped filenames and review confirmation dismissal.

Optional fixture simulations (invalid CSRF, GET mutation, repeated review timestamp, missing archive, interrupted history recovery) were not separately confirmed. Retain safeguards and use disposable fixtures for such tests.

## QA-6 acceptance (hosted functional acceptance passed, 2026-11-27)

Tester reported all 35 acceptance checks in Sections A–E passed. Project closeout (Section F) is separate from functional acceptance.

### A — Hostinger configuration (8/8)
- PHP 8.4, matching the local minor version.
- fileinfo, zip and intl enabled (three checks).
- upload_max_filesize 256 MB; post_max_size 256 MB; max_file_uploads 20 (three checks).
- Server date.timezone UTC retained; export and review endpoints and history display explicitly use America/New_York.

### B — Deployment (5/5)
- public/ contents deployed to public_html/; existing authentication preserved.
- HTTPS verified.
- Unauthenticated root request returned HTTP/2 401 and WWW-Authenticate: Basic realm="Restricted Access" in tester's curl output.
- Private sibling data/ created; PHP initialized archives/ and storage.lock.

### C — Hosted observation capture (6/6)
Tester confirmed capture with and without screenshots, saved environment preferences, persisted JSON/UUID/image associations, mobile or tablet submission, and preservation of original screenshot bytes.

### D — Hosted export and review (9/9)
Tester confirmed pending counts, verified ZIP download, checksum comparison, incremental export, disabled export at zero, historical downloads, persisted review state, localized dates and alert dismissal/URL cleanup. First hosted archive exchanged during acceptance: qa-reports-2026-11-27-001.zip; 3 observations and 2 screenshots; supplied SHA-256:

`51facb09268e796724c24f9c0362e3a47d6eb45f4902c05f57c62cfacce0d19f`

The archive exchange was reported successful during acceptance. Original screenshot comparison and hosted history were verified by the tester rather than inferred from the ZIP alone.

### E — Security and data preservation (7/7)
Tester confirmed unauthenticated download endpoint denial, direct access denial for submissions.json, export-history.json and private screenshots/ZIPs, invalid export UUID handling, and retention of runtime data and authentication configuration across redeployment. These are functional isolation checks, not a comprehensive security audit.

### F — Project closeout
- QA-5 and QA-6 results documented; README and technical specification reconciled.
- Branch merge and project-plan synchronization remain separate closeout actions.
- Reset disposable hosted fixtures before recording real Garden observations (see below). Cleanup is not yet confirmed.

### Deferred technical testing
Controlled concurrent writes/exports, forced JSON or screenshot write failures, interrupted export recovery, malformed/partial uploads, aggregate request rejection, forced session initialization failure and deliberate CSRF/review endpoint failure simulations remain unverified unless specifically recorded otherwise. Do not count these as passed. Preserve the existing locking, atomic replacements, backups, validation and CSRF protections; revisit before broader or multi-user use.

### Disposable hosted test data reset

Only perform this procedure on a dataset confirmed to contain disposable acceptance fixtures, never on real QA evidence. Back up the dataset first if any uncertainty exists. Ensure no requests can be writing, exporting or reconciling data during cleanup (stop access temporarily or otherwise ensure exclusive maintenance).

In the private sibling data/ directory, remove the following together:
- submissions.json
- submissions.json.bak
- export-history.json
- pics/ and its contents
- archives/ and its contents

Keep data/ and the stable storage.lock file. Do not delete public_html/ or authentication configuration. Do not delete only the reports while retaining archives or export history, because their report IDs would conflict with the empty dataset.

Restore normal access and reload the observation page. Confirm pending, exported and archive counts are all zero, export is disabled, and the history page has no archives. PHP recreates required directories as needed. Browser-local environment preferences are separate; use Forget only if those also need resetting.
