# SOLACE QA

Internal QA observation capture and batch exchange tool for SOLACE Network. Collect observations and original screenshots, export verified incremental ZIP batches, download previous archives, and track review status independently of export status.

## Requirements

- PHP 8.1 or later; PHP 8.4 used for local and hosted acceptance.
- Enabled PHP extensions: `fileinfo`, `zip` and `intl`.
- Bootstrap 6.0.0-alpha.2 CSS and JavaScript via jsDelivr; internet access expected.
- No database, Composer, npm or application-level login required. Hosted access must be protected by server authentication and HTTPS.
- Up to five screenshots per observation, 10 MiB per image. Configure PHP for at least 10 MiB per file, 55 MiB per request and five file uploads. Check the actual web PHP runtime independently of CLI settings.

Verify local extensions with `php -m`; confirm `fileinfo`, `zip` and `intl` are listed.

## Local development

From the repository root, start PHP using a private, user-writable session directory:

```sh
mkdir -p -m 700 "$HOME/.php-sessions/solace-qa"
php -d session.save_path="$HOME/.php-sessions/solace-qa" -d upload_max_filesize=10M -d post_max_size=55M -d max_file_uploads=20 \
    -S localhost:8001 -t public
```

Open http://localhost:8001/. Choose another unused port if necessary. On Debian/LMDE, the default `/var/lib/php/sessions` may contain session files owned by another PHP process or user; a permission failure can cause CSRF validation to reject submissions with `Invalid form session. Please reload the form.` After restarting with the private session path, reload the form; if needed, clear the localhost `PHPSESSID` cookie first. Do not run the development server as root or loosen system session-directory permissions. The override applies only to this local PHP process; Hostinger uses its configured session storage.

## Usage

Capture an observation, optionally attach PNG, JPEG or WebP screenshots, and submit. Reusable testing-environment preferences can be remembered in the reporting browser. Export pending observations to create a verified ZIP; previously exported reports are excluded. The dedicated export history page allows re-downloads and marking a batch reviewed. Neither action changes the original report or screenshot files. Review status is independent of export status.

## Deployment

Deploy only the contents of `public/` to the authenticated Hostinger document root (`qa.solace.network/public_html/`). Preserve the existing server authentication configuration, including hidden authentication files. Create a sibling `data/` directory outside the document root, writable by the PHP account (owner-only permissions where supported). The application initializes storage as needed. Never deploy local test data or overwrite hosted runtime data during updates.

Runtime data includes `submissions.json`, `submissions.json.bak`, `export-history.json`, `storage.lock`, `pics/` and `archives/`. Keep the entire dataset together in backups. Real QA evidence is valuable, even during alpha development. For disposable acceptance fixtures only, follow the documented [test-data reset procedure](docs/TESTING.md#disposable-hosted-test-data-reset); do not reset a live evidence dataset.

Hosted functional acceptance was reported passed on 2026-11-27. See the [technical specification](docs/TECH_SPEC.md) and [acceptance record](docs/TESTING.md) for configuration, scope and deferred technical tests.
