# Security hardening summary

Implemented controls:

- Removed hardcoded administrator fallback credentials and automatic administrator seeding.
- Added inactive-account rejection at login and global logout enforcement.
- Invalidated sessions/remember tokens after password rotation, reset or deactivation.
- Regenerated sessions when entering and leaving impersonation.
- Added deny-by-default permission enforcement for shared back-office routes.
- Added authentication, password-reset, registration, ticket, newsletter and payment rate limits.
- Centralized and hardened local redirect validation.
- Added exact trusted-host configuration.
- Added baseline response security headers and optional report-only CSP/HSTS.
- Moved new ticket attachments to private storage with MIME/extension allowlisting and authorized downloads.
- Added a command to migrate legacy public ticket attachments.
- Locked Razorpay order/invoice callbacks and prevented captured payments from being downgraded by stale failure events.
- Locked offline payment submission/approval workflows to prevent duplicate application.
- Replaced unrestricted mass assignment on Order and ProductOffer with explicit fillable fields.
- Added production security configuration audit and security regression tests.
- Removed unreachable bootstrap configuration.

Not automatically completed:

- Frontend dependency lockfile upgrade (run the supplied npm script on a normal registry-connected build machine).
- Composer advisory audit (Composer was unavailable in the review environment).
- Web-server/TLS/database/firewall/backup configuration.
- Enforced Content Security Policy, because enabling it without a report-only observation period could break Razorpay and current inline scripts.
