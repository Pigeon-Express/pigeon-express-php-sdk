# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in this SDK, please report it
**privately**. Do **not** open a public GitHub issue for security problems.

- Email: **dev@pigeonexpress.bg** (subject: `SECURITY`)
- Please include: a description, steps to reproduce, the affected version, and
  the potential impact.

We will acknowledge your report within **5 business days** and provide a
remediation timeline after triage. Please give us a reasonable opportunity to
fix the issue before any public disclosure.

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅         |

## Credentials & secrets

API credentials (`pk_…` / `sk_…`) are supplied **at runtime** to the client
constructor (`new PigeonExpress($apiKey, $apiSecret)`) — they are never stored in
this repository, and none should ever be committed. Keep them in environment
variables or your application's secret store. When filing issues, redact any real
keys, tokens, or full request/response payloads.
