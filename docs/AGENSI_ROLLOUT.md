# v0.7.0 private tenant-isolation rollout plan (not yet deployed)

## Critical reason
The historical MCP authorizer validates OAuth tokens, but the old storage function lists/loads *all* WordPress sites regardless of user. A public marketplace listing cannot ship against that shared implementation.

## Controlled rollout (staging first)
1. Back up the database and existing `advertpreneur-store` Edge Function code; record the original production deployment.
2. Run the additive migration in `supabase/migrations/20261015_adpc_owner_scope.sql`. Existing sites become `legacy`; existing private Action API use remains available.
3. Configure `ADPC_STORE_TOKEN_SHA256` as the lower-case SHA-256 digest of the *existing* ADPC_STORE_TOKEN via a secure environment/secrets mechanism. Never commit this value or the plaintext token.
4. Deploy the tenant-scoped `advertpreneur-store` Edge Function. Verify `list` and `get` are filtered by owner and that an owner mismatch returns no site.
5. Deploy Vercel v0.7.0 on a staging URL first. `/api/mcp` must use per-request owner-specific tool handlers; `connect_site` signs the authenticated owner's identity and callback saves only to that owner.
6. Run staging tests with two distinct OAuth users and two WordPress test sites, including attempts to read a known ID belonging to the other user, concurrent clients, and a fresh connection for each.
7. Run draft, SEO, image and quality tests with a test account and WordPress staging site. For publication tests, use staging only and require explicit approval.
8. Deploy to production only after test records, rollback method and approval are recorded. Keep the static global Action token private; Agensi buyers must never receive it.
9. Update setup documentation and support checklist, then request Agensi human review. Do not call 'all agents tested' until individual MCP authentication/tool execution has been checked.

## Compatibility notes
- WordPress plugin's legacy `/connect` path continues to attach sites to private `legacy` owner for existing Action API users. OAuth users should instead use the owner-scoped MCP `connect_site` tool to attach *their own* sites.
- The transport-specific MCP stream lifecycle must be tested after the change to construct request-scoped handlers.
- URLs pointing at local/private domains are blocked at parsing, but DNS rebinding/SSRF prevention and redirect behavior still require a security review before public multi-tenant launch.
- No cross-user data isolation claim is made until backend, store, database and agent integration are deployed and tested together.

## Verified 2026-10-15 progress
- Additive production Supabase migration applied; two previously connected legacy sites retained.
- Isolated new Edge Function `advertpreneur-store-v2` deployed; legacy `advertpreneur-store` unchanged.
- PHP companion version 0.7.0 tested on isolated Docker WordPress 7.1.2 installation with real draft, quality checks, permissions, stale attestation refusal, successful publish and idempotent retry.
- Secure outbound request uses DNS-resolved pinned public IP and rejects redirects/private destinations.
- Legacy production Vercel endpoint has not been changed.
