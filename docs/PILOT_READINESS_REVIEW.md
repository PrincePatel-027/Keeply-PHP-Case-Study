# K-062 Pilot Accessibility and Security Review

Reviewed: 11 October 2026

## Scope

This review covered the pre-pilot paths that carry the highest user or data risk:

- Authentication, email verification, password reset, and session handling
- Workspace, product, case, reminder, document, assessment, and Action Pack authorization
- Nested case-event ownership checks
- Private upload validation and authorized downloads
- Account export, soft deletion, retention, and permanent purge
- Blade output escaping and Action Pack PDF rendering
- CSRF coverage and throttling on login, verification, deletion, and uploads
- Shared navigation, keyboard focus, narrow-screen access, landmarks, labels, and validation errors

## Findings closed

### 1. Browser responses had no explicit security-header baseline

**Risk:** Without clickjacking, MIME-sniffing, content-source, referrer, and browser-capability restrictions, a deployment would rely on browser defaults and hosting configuration for protections that belong to the application.

**Fix:** Added global response middleware with:

- Content Security Policy restricted to same-origin application assets
- `frame-ancestors 'none'` and `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- Same-origin referrer and cross-origin isolation policies
- Camera, geolocation, and microphone disabled
- HSTS for secure production requests
- `private, no-store` caching for authenticated responses

The Action Pack print control was moved from an inline event handler to the same-origin `public/js/keeply.js` script so printing continues to work under the strict policy.

### 2. Mobile CSS hid every primary authenticated navigation link

**Risk:** At 640 px and below, users could not reach Today, Products, Cases, or Account from the shared header. This blocked the core application flow on phones and narrow zoomed layouts.

**Fix:** The narrow header now stacks the brand and navigation, keeps every link visible, and permits horizontal overflow at the smallest widths. Active sections expose `aria-current="page"`.

### 3. Sensitive form errors were not consistently associated with their controls

**Risk:** A screen-reader user could hear that account deletion or outcome recording failed without hearing which field caused the failure.

**Fix:** Login, account-deletion, and final-outcome controls now expose `aria-invalid` and reference stable error or hint IDs through `aria-describedby`.

### 4. A failed private-file purge could leave untracked retained data

**Risk:** Database records were deleted before private files. If storage deletion failed afterward, Keeply would lose the metadata needed to locate and retry that file, leaving private data retained beyond the promised period.

**Fix:** Purging now removes and verifies every private file first. Any storage failure aborts the database deletion, preserving the account, workspace, and document metadata so the scheduled purge can be retried and diagnosed.

## Existing controls confirmed

- Every private route requires authenticated, verified users.
- Workspace policies positively verify membership for products, cases, reminders, documents, and Action Packs.
- Nested case events must belong to the case in the route.
- State-changing browser requests use CSRF protection.
- Upload bytes are checked with `finfo`; accepted files are stored outside the public directory.
- Downloads require authorization and send `nosniff`.
- Blade uses escaped output for user-authored facts and letters.
- Dompdf remote loading and PHP execution are disabled.
- Account deletion requires the current password and exact confirmation text, blocks shared workspaces, and retains data for the documented 30-day recovery period.

## Verification

Focused automated gates:

- `Tests\\Feature\\PilotReadinessTest`: 4 passed, 26 assertions
- `Tests\\Feature\\AccountPurgeSafetyTest`: 1 passed, 6 assertions
- Scoped Laravel Pint: all 5 checked PHP files passed

The gates verify the security headers, authenticated no-store caching, sensitive error associations, external print script, mobile-navigation CSS regression, and retry-safe private-file purging.

## Remaining non-blocking verification debt

A narrow visual screenshot was attempted once, but the temporary Laravel preview process exited before browser navigation and `playwright-cli` received `ERR_CONNECTION_REFUSED`. Per the bounded verification policy, it was not retried. No screenshot evidence was produced. The existing K-013 independent first-time-user usability review also remains pending.

No critical security or accessibility finding remains open from this focused review. Production deployment still requires K-063 configuration and one production smoke test.
