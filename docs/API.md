# API Status

AssetFlow currently exposes a web-first Filament administration interface and does not publish a versioned JSON API.

## Existing HTTP Surfaces

- Authenticated web routes serve asset exports, CSV templates, printable labels, receipts, private photos, attachments, and audit evidence packs.
- Public receipt acceptance routes use long tokenized links and CSRF-protected POST requests.
- Public asset scan routes show limited asset verification information to unauthenticated users and reveal management actions only to authenticated users with permission.
- Livewire and Filament internal endpoints are framework-managed and are not considered public API contracts.

## If A JSON API Is Added

Use a versioned route prefix such as `/api/v1`, token authentication, explicit form requests, pagination, consistent JSON error envelopes, rate limiting, policy checks, and tests for authorization, validation, filtering, sorting, pagination, and failure scenarios.
