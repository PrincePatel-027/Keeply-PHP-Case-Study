# Keeply account data retention

## Export

A verified user can download a JSON export containing account details, workspace settings, products, dynamic product values, cases, timelines, reminders, assessment snapshots, Action Pack snapshots, and private-document metadata with SHA-256 integrity hashes.

Binary receipts, photographs, evidence files, and generated PDFs are not embedded in the JSON export. Users should download required private files from their product or case records before requesting deletion. Internal storage paths, passwords, session data, and password-reset tokens are never exported.

## Account deletion request

Deletion requires the current password and the exact phrase `DELETE MY ACCOUNT`. A successful request signs the user out immediately and soft-deletes the account, preventing further sign-in.

Keeply retains the account, sole-owner workspace records, and private files for 30 days as a recovery window. Operational recovery tooling is required before a production pilot; the application does not present a self-service undo action.

Deletion is blocked when the user belongs to a workspace with another member. Keeply does not yet implement ownership transfer, so it will not silently delete or orphan shared records.

## Permanent purge

The `accounts:purge-deleted` command runs daily. Once an account has been deleted for at least 30 days, it permanently removes:

- the user account;
- sole-owner workspaces and memberships;
- products and dynamic values;
- cases, events, reminders, assessments, and Action Packs;
- private document metadata; and
- the corresponding private files from their configured storage disks.

Accounts inside the recovery window are not purged. Shared workspaces are skipped as a final safety check.

Production backup retention is not configured in this application checkpoint and must be defined before the production pilot.
