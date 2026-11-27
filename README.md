# SOLACE QA

Internal QA observation capture and exchange tool for SOLACE Network.

## Local development

From the repository root, start PHP using a private, user-writable session directory:

```sh
mkdir -p -m 700 "$HOME/.php-sessions/solace-qa"
php -d session.save_path="$HOME/.php-sessions/solace-qa" -d upload_max_filesize=10M -d post_max_size=55M -d max_file_uploads=20 \
    -S localhost:8001 -t public
```

Open http://localhost:8001/. Choose another unused port if necessary. On Debian/LMDE, the default `/var/lib/php/sessions` may contain session files owned by another PHP process or user; a permission failure can cause CSRF validation to reject submissions with `Invalid form session. Please reload the form.` After restarting with the private session path, reload the form; if needed, clear the localhost `PHPSESSID` cookie first. Do not run the development server as root or loosen system session-directory permissions. The override applies only to this local PHP process; Hostinger uses its configured session storage.

QA-3 attachments require PHP Fileinfo; use server limits of at least 10 MiB per file and 55 MiB per request for the full five-image allowance. Verify hosting limits independently.

QA-4 requires PHP ZipArchive. Check with `php -m | grep -i '^zip

Deploy only `public/` contents into the authenticated Hostinger document root; preserve server authentication configuration. Runtime data belongs in sibling `data/` and is excluded from Git. See [technical specification](docs/TECH_SPEC.md) and [testing](docs/TESTING.md).
`. Exports retain original reports and screenshots, store verified ZIPs privately in data/archives, and record batch IDs in data/export-history.json.

## Deployment

Deploy only `public/` contents into the authenticated Hostinger document root; preserve server authentication configuration. Runtime data belongs in sibling `data/` and is excluded from Git. See [technical specification](docs/TECH_SPEC.md) and [testing](docs/TESTING.md).
