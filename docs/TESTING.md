# SOLACE QA testing

## QA-1 acceptance (pending)
1. Start PHP with public/ as document root. Verify SOLACE QA title and round logo at 80 CSS px width.
2. Submit a meaningful observation; verify data/submissions.json outside public/ contains it.
3. Submit a second observation; verify both persist and invalid submissions do not alter data.
4. Verify /data/submissions.json cannot be downloaded directly.
5. On Hostinger, verify authenticated access, HTTPS, PHP write permission to sibling data/ and availability of Fileinfo/ZipArchive. Do not deploy a diagnostic phpinfo page.
6. Verify redeploying public/ does not replace report data or authentication configuration.

Later acceptance must cover multiple devices, environment overrides, attachments, failed uploads, concurrent writes/exports, interrupted export recovery, checksum/count validation, repeat export exclusion, archive re-download and reviewed state.
