# Advertpreneur Connect — Production readiness record

**Reviewed:** October 15, 2026
**Product:** Advertpreneur Connect 0.7.3 WordPress/MCP backend, plugin 0.7.1, Agensi skill package 0.2.1.
**Production endpoint:** https://advertpreneur-connect.vercel.app/api/mcp
**Companion WordPress ZIP:** [v0.7.1](../releases/advertpreneur-connect-wordpress-v0.7.1.zip)
**Agensi skill ZIP:** [v0.2.1](../releases/advertpreneur-connect-agensi-skill-v0.2.1.zip)

## Verified in production

- Vercel build deployed; public `/api/health` reported version 0.7.0; unauthenticated MCP and Action API calls returned 401.
- Authenticated ChatGPT/advert_connect connector listed two pre-existing authorized WordPress sites and performed `site_status` on both; read-only content search and SEO detection also succeeded.
- Existing connected-site IDs survived the tenant migration, with private explicit access grants and separate v2 storage endpoint.

## Verified in isolated staging / automation

- WordPress 7.1.2, PHP 8.3 with Advertpreneur Connect plugin 0.7.0: real REST draft creation, read-back, structural and overlap checks, document attestation, forbidden contributor edits, stale attestation rejection, authorized publish and idempotent retry passed.
- Real PNG upload, native dimensions, media metadata, featured image placement and unauthorized contributor rejection passed.
- WooCommerce: draft creation, duplicate prevention, contributor price-write denial, structural checks, product attestation, content-update invalidation, reattestation and authorized publication passed.
- Fifteen backend Node.js unit/integration tests passed; a real HTTPS pinned-DNS probe returned an expected WordPress 401 for invalid test credentials.
- Next.js optimized build and PHP syntax checks passed. GitHub Actions release workflow passed on commit `4c099392`. Dependency audit returned zero known advisories at the checked lockfile versions.
- Agensi ZIP has a valid folder-root `SKILL.md` + reference files, no executable scripts or hard-coded credentials; archive integrity checked. Agensi has not reviewed or approved the listing.

## Still required before claiming universal commercial compatibility

1. Independent OAuth login as a **second distinct user** and proof of no cross-user site discovery/read/write access. Node regression checks are not a substitute for a full second-account end-to-end test.
2. MCP live draft and media workflow using a new, authorized WordPress staging domain reachable via HTTPS through the public Vercel bridge (localhost Docker REST tests don't exercise remote WordPress transport end-to-end).
3. Real client end-to-end tests in Claude Code, Cursor, Codex CLI and other advertised Agensi clients. Codex's MCP OAuth setup was successful, but agent invocation was blocked by unrelated missing OpenAI model authentication in the isolated test profile. Other named clients are not verified.
4. Controlled companion plugin upgrade at customer's WordPress sites; the two existing connections were last observed to run plugin v0.6.3. Do not upgrade client sites as part of a backend deployment without site-specific approval.
5. Agensi acceptance review and seller payment eligibility/onboarding check. Neither is complete.

## Marketing claim boundary

Reasonable: **“Advertpreneur Connect is a working, tested WordPress MCP publishing bridge with a portable Agensi SKILL.md. WordPress staging content, media and WooCommerce workflows pass, while supported-agent integration is verified individually.”**

Not yet substantiated: “All AI agents fully supported,” “zero setup,” “Agensi approved,” or “all customer sites upgraded.”

The global Action token and optional static MCP bearer are reserved for private integrations; public marketplace customers must use individual OAuth identities, and must never receive shared secrets. Publishing additionally requires final user approval in the AI workflow; backend enforcement proves only the current quality-attestation condition, not that a human reviewed the factual claims.

## Hosted upload constraints

The MCP base64 image upload now rejects payloads above 4,000,000 characters before contacting WordPress. This is necessary because Vercel Function request bodies are limited to 4.5 MB including base64 expansion. For larger assets use `import_image_url` with approved public HTTPS media; WordPress validates the image and can accept up to 8 MB via that route.

## Hostinger candidate assessment (2026-10-15)

The user expressly excluded Advertpreneur.com and CoocooBabys.com from current host testing. Approved candidates: tauqva.com, lovfah.com, myecomnews.com. Lovfah.com public WP REST index returned 200, companion status route 404; WordPress admin confirmed a multisite network. MyEcomNews.com WP REST index returned 500, companion route 404, WP Admin lists active Rank Math and Hostinger displayed a 16-vulnerability warning. Tauqva.com HTTP checks timed out; Hostinger displayed a domain renewal-failed alert. No plugins, posts, settings, credentials or content on these three sites were changed by this assessment. Hostinger staging/plugin upload operations could not be safely completed through the available UI controls.

These candidates are **not yet end-to-end certified**. Before a full customer-site test, use a working WordPress staging environment under a publicly resolvable HTTPS domain, install plugin v0.7.0, authorize a test account through MCP, execute draft/media/SEO read-back, and test cross-account isolation. Do not call a domain tested until this has succeeded.

## Reproducible WordPress integration suite (backend 0.7.2)

The `qa/run-wordpress-e2e.sh` runner provisions an isolated local WordPress Docker site, installs plugin 0.7.0, WooCommerce and Rank Math, runs REST tests for draft/attestation/publishing, uploads a real PNG, validates permissions, checks WooCommerce catalog protections, and verifies Rank Math native metadata round-trips. It is now included as a dedicated GitHub Actions job. The runner creates no public website, stores no customer credentials and leaves all customer domains untouched. A passing local integration suite is **not** a substitute for authorized public HTTPS MCP end-to-end testing.

## Further Hostinger-compatibility hardening (backend 0.7.3)

The WordPress Application Password callback accepts the site URL reported as `http://` **only when its hostname exactly matches the HMAC-signed HTTPS site**. The stored destination remains the signed HTTPS origin. This addresses proxies/WordPress multisite setups such as the Lovfah configuration where `siteurl` and `home` differ in scheme, without accepting cross-site callbacks, embedded credentials or alternate ports. Dedicated security regression tests were added.

GitHub Actions run `38705273957` passed both the backend/build and real disposable WordPress integration jobs for version 0.7.2. The 0.7.3 release adds the callback check and must also pass CI after deployment.

## Additional commercial-readiness audit — October 15, 2026

- Public API `/api/mcp` and the private Action API return 401 to unauthenticated callers. The Supabase private store returns 401 to a deliberately invalid token, and 405 for a GET request.
- Production npm dependency audit yielded zero known advisories. CI now runs `npm audit --omit=dev --audit-level=high` and verifies current WordPress v0.7.0 and Agensi skill v0.2.1 ZIP files match every checked-in source file byte-for-byte.
- Supabase connected-site and site-access tables have RLS enabled with no `anon`/`authenticated` policies (deny-by-default); only the private token-protected Edge Function uses privileged access. The security advisor labels this as informational. Other shared-project advisories (public pg_net extension, leaked-password protection disabled) are outside this bridge's direct scope and should be assessed separately.
- Current read-only Hostinger observations: Lovfah public WP REST 200 and companion route 404; WordPress Network Admin reported database-connection error. Tauqva timed out repeatedly, including Hostinger WP admin auto-login (500 timeout). MyEcomNews public REST later recovered to HTTP 200 but companion route is still 404. None of the three had a successful hosted plugin installation or remote end-to-end publishing test in this round.
- Automatic isolated multisite conversion test was not completed because the hosting/runtime action was blocked by a safety control. Do **not** report multisite integration as verified.
- Production Vercel runtime log retrieval returned HTTP 403 from the log API; no absence-of-errors conclusion is possible.
- Real distinct-user OAuth cross-account isolation and actual Claude/Cursor/Codex agent content writes remain unverified. **Do not advertise unrestricted commercial production readiness yet.**

## Security hardening in companion plugin 0.7.1

Content search, WooCommerce product listing, similarity/overlap reports, and internal-link source checks now enforce per-object unpublished-content visibility. A contributor can search their own drafts but cannot enumerate another author's unpublished titles and excerpts. A dedicated real WordPress role-isolation regression was added; the CI harness also validates explicit per-suite pass markers to prevent falsely green builds when an eval fixture fails to execute. The old v0.7.0 ZIP is preserved as immutable historical artifact; **use v0.7.1 for new installations**.
