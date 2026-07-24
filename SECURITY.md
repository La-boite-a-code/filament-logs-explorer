# Security Policy

## Supported versions

Only the latest minor release receives security fixes.

| Version | Supported |
| ------- | --------- |
| 1.x     | Yes       |
| < 1.0   | No        |

## Reporting a vulnerability

Please do not open a public issue for a security problem. Use one of these
private channels instead:

- [Report a vulnerability](https://github.com/La-boite-a-code/filament-logs-explorer/security/advisories/new)
  through GitHub's private advisory form (preferred).
- Email [alexandre@laboiteacode.fr](mailto:alexandre@laboiteacode.fr).

Include the affected version, a description of the issue and, if possible, a
minimal way to reproduce it.

You will get an acknowledgement within 72 hours. Once the issue is confirmed,
a fix is developed privately, shipped as a patch release, and credited to you
in the changelog unless you prefer to stay anonymous.

## Scope

This plugin reads files from your configured log directories and can delete
them when deletion is enabled. Reports about reading or deleting files outside
those directories, authorization bypasses, or content injection through log
lines (such as XSS) are especially welcome.
