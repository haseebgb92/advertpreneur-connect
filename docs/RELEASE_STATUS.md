# Advertpreneur Connect — Production readiness record

**Reviewed:** October 15, 2026  
**Product:** Advertpreneur Connect 0.7.0 WordPress/MCP backend, plugin 0.7.0, Agensi skill package 0.2.0.  
**Production endpoint:** https://advertpreneur-connect.vercel.app/api/mcp  
**Companion WordPress ZIP:** [v0.7.0](../releases/advertpreneur-connect-wordpress-v0.7.0.zip)  
**Agensi skill ZIP:** [v0.2.0](../releases/advertpreneur-connect-agensi-skill-v0.2.0.zip)

## Verified in production

- Vercel build deployed; public `/api/health` reported version 0.7.0; unauthenticated MCP and Action API calls returned 401.
- Authenticated ChatGPT/advert_connect connector listed two pre-existing authorized WordPress sites and performed `site_status` on both; read-only content search and SEO detection also succeeded.
- Existing connected-site IDs survived the tenant migration, with private explicit access grants and separate v2 storage endpoint.

## Verified in isolated staging / automation

- WordPress 7.1.2, PHP 8.3 with Advertpreneur Connect plugin 0.7.0: real REST draft creation, read-back, structural and overlap checks, document attestation, forbidden contributor edits, stale attestation rejection, authorized publish and idempotent retry passed.
- Real PNG upload, native dimensions, media metadata, featured image placement and unauthorized contributor rejection passed.
- WooCommerce: draft creation, duplicate prevention, contributor price-write denial, structural checks, product attestation, content-update invalidation, reattestation and authorized publication passed.
- Ten backend Node.js unit/integration tests passed; a real HTTPS pinned-DNS probe returned an expected WordPress 401 for invalid test credentials.
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
