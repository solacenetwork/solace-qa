# SOLACE QA testing

## Local test setup

Run from the repository root using a private PHP session directory (confirmed on LMDE during QA-2 acceptance on 2026-11-27):

```sh
mkdir -p -m 700 "$HOME/.php-sessions/solace-qa"
php -d session.save_path="$HOME/.php-sessions/solace-qa" -S localhost:8001 -t public
```

Open http://localhost:8001/. Use another unused port if necessary. If submission returns HTTP 403 with `Invalid form session. Please reload the form.`, check the PHP server log for `session_start()` permission or session-read failures. Restart with the above session path and reload the form; clear the localhost `PHPSESSID` cookie if needed. Do not weaken system PHP session permissions. Retry the submission and verify the report in `data/submissions.json`.

Follow-up application hardening: check the return value of `session_start()` on the form and submission endpoints. If initialization fails, return an appropriate server configuration error (HTTP 500), do not render an unusable form or accept mutations, and keep internal paths and diagnostic details in server logs rather than the browser. Verify this failure path separately; the local launch workaround does not replace application error handling.

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
