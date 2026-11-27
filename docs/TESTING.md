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
5. On Hostinger, verify authenticated access, HTTPS, PHP write permission to sibling data/ and availability of Fileinfo/ZipArchive. Do not deploy a diagnostic phpinfo page.
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

## QA-4 acceptance (pending)

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
