# SOLACE QA testing

## QA-1 acceptance (local functional pass, 2026-11-27)
1. Start PHP with public/ as document root. Verify SOLACE QA title and round logo at 80 CSS px width.
2. Submit a meaningful observation; verify data/submissions.json outside public/ contains it.
3. Submit a second observation; verify both persist and invalid submissions do not alter data.
4. Verify /data/submissions.json cannot be downloaded directly. Local curl returns the application form, not JSON. Isolation passes locally; verify independently on Hostinger.
5. On Hostinger, verify authenticated access, HTTPS, PHP write permission to sibling data/ and availability of Fileinfo/ZipArchive. Do not deploy a diagnostic phpinfo page.
6. Verify redeploying public/ does not replace report data or authentication configuration.

## QA-2 acceptance (pending)
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
