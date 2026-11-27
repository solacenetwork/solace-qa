# SOLACE QA technical specification

## Purpose
A small, application-independent internal observation capture and batch exchange tool. It records evidence, not confirmed defect decisions. GitHub documentation and Monday.com remain the issue-tracking systems.

## Environment and deployment
- Vanilla PHP and JavaScript; Bootstrap 6 alpha.2 target (existing alpha.1 retained in QA-1 until dependency verification).
- PHP 8.1+; Fileinfo and ZipArchive required for later attachment/export stages.
- Local: `php -S localhost:8000 -t public`.
- Hostinger: deploy only contents of public/ into qa.solace.network/public_html/. Preserve hPanel-managed authentication files and HTTPS. Store runtime data in sibling data/; never deploy or overwrite it.
- No database, Composer, npm, application login system or PWA.
- Existing QA records are valuable even during alpha. Preserve valid reports, images and archives. Obsolete disposable development fixtures need no compatibility layer.

## Planned capture contract
Keep actions, actual and expected behavior, page/workflow, date, report type, suggestion and optional severity. Add project, scenario ID, app version/build, device/model, OS, browser/version, orientation, viewport/screen dimensions, pixel ratio, browser/PWA context, interface preference/effective mode and reproducibility (always/sometimes/once/not retested). Allow unknown values and manual overrides; reporting environment may differ from tested environment. Remember only reusable environment preferences locally. Add browser compatibility, accessibility, performance and coverage-gap classifications. Classification is preliminary.

## Planned persistence and images
Server-generated permanent report UUID. Optional multiple PNG/JPEG/WebP screenshots; validate actual image format, limit count/size, sanitize filename, prevent collisions, retain original quality. Paths relative to data/ use pics/<report-uuid>-<safe-name>-<date>.<ext>. Report records contain image associations. Keep storage outside web root. Use one stable lock file for all report/export mutations and atomic same-directory replacement of JSON. Failed uploads or writes must not commit incomplete observations. Use POST and CSRF protection for mutations before hosted acceptance.

## Planned batch exchange
Export only pending reports. ZIP contains manifest.json, submissions.json and associated pics/. Manifest includes format version, generator version, export UUID, ISO 8601 generated time, report/image counts, dataset inventory mapping report UUIDs to image paths, and file inventory with byte sizes and SHA-256 checksums (excluding manifest itself). Hashes verify integrity and identify identical contents, not authenticity. Report UUIDs identify records, export UUIDs identify batches. Do not silently discard distinct reports with identical images. Cross-batch image deduplication may be reported by hash; each archive remains self-contained.
Verify ZIP contents and checksums before marking batch exported. Retain previous archives, allow re-download, and recover safely if interrupted between archive creation and export-state commit. No report should vanish or enter a later batch accidentally. Maintain export history and separate reviewed status. Archive checksum can be stored in history, outside ZIP. Serve downloads through authenticated PHP with safe path handling.

## Milestones
- QA-1: repository organization, branding, private storage foundation and specification.
- QA-2: expanded form and remembered environment.
- QA-3: UUIDs, validated persistence and image attachments.
- QA-4: verified ZIP/manifest, checksums and pending batch boundaries.
- QA-5: history, downloads and reviewed status.
- QA-6: local and Hostinger acceptance.
