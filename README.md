# SOLACE QA

Internal QA observation capture and exchange tool for SOLACE Network.

Run locally: `php -S localhost:8000 -t public`.

Deploy only `public/` contents into the authenticated Hostinger document root; preserve server authentication configuration. Runtime data belongs in sibling `data/` and is excluded from Git. See [technical specification](docs/TECH_SPEC.md) and [testing](docs/TESTING.md).
