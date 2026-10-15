# Deploy Advertpreneur Connect v0.7 (Vercel + Supabase)

Advertpreneur Connect separates **public MCP OAuth** from **private infrastructure**. Never distribute `ADPC_STORE_TOKEN`, `ADPC_ACTION_TOKEN`, `ADPC_ENCRYPTION_KEY` or an Application Password to buyers.

## Storage schema

Create the connected-sites table and explicit legacy grants in a dedicated Supabase project. For existing installations, use the additive migrations recorded in this repository rather than dropping tables or replacing credentials.

```sql
create table if not exists public.adpc_connected_sites (
  id text primary key,
  owner_id text not null default 'legacy',
  site_url text not null,
  user_login text not null,
  credential_enc text not null,
  connected_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);
create index if not exists adpc_connected_sites_owner_id_idx
  on public.adpc_connected_sites (owner_id, connected_at desc);

create table if not exists public.adpc_site_access (
  owner_id text not null,
  site_id text not null references public.adpc_connected_sites(id) on delete cascade,
  created_at timestamptz not null default now(),
  primary key (owner_id, site_id)
);
alter table public.adpc_connected_sites enable row level security;
alter table public.adpc_site_access enable row level security;
revoke all on public.adpc_connected_sites from public, anon, authenticated;
revoke all on public.adpc_site_access from public, anon, authenticated;
```

A project using the v0.6.1 schema may have additional indexes, constraints, or entries. Always inspect and back up before migration. The former site_url-only unique constraint, if present in a self-hosted v0.6 installation, conflicts with per-owner site IDs; migrate it carefully after verifying no duplicate rows. Do not blindly drop a production constraint without a tested replacement.

The private service-role Edge Function is the only intended table client. Anonymous/authenticated Data API grants must never allow reading the credential ciphertext. Keep RLS enabled and no public row policies.

## Storage Edge Function

The current implementation is `supabase/functions/advertpreneur-store/index.ts`. Deploy it privately as `advertpreneur-store-v2`; do not use older global unscoped `list/get` handlers for multiple customers.

Set Supabase secret `ADPC_STORE_TOKEN_SHA256` to the lowercase SHA-256 digest of a cryptographically random, long, **independently stored** `ADPC_STORE_TOKEN`. Compute securely without printing either value in chat or build logs. Leave `verify_jwt=false` only for this service-to-service Edge Function because it validates its private `x-adpc-token` header using constant-time comparison and has no browser-facing credentials; all unrecognized or missing headers fail closed.

Supply the following values on the Vercel service:

| Setting | Purpose |
|---|---|
| `ADPC_STORE_URL` | New tenant-scoped Edge Function HTTPS endpoint |
| `ADPC_STORE_TOKEN` | Service-to-service token (secret) |
| `ADPC_ENCRYPTION_KEY` | Stable, strong secret for AES-GCM WP App Password encryption |
| `ADPC_STATE_SECRET` | Stable, strong secret for short-lived, signed WP OAuth state |
| `ADPC_MCP_BEARER_TOKEN` | Optional private legacy client only; **not for marketplace** |
| `ADPC_ACTION_TOKEN` | Optional private OpenAPI actions only; **not for marketplace** |
| `ADPC_APP_ID` | Stable UUID used by WP application authorization |

For existing encrypted records, never rotate `ADPC_ENCRYPTION_KEY` before re-encrypting all stored credentials through an approved migration.

## MCP OAuth

The Supabase Auth project URL is configured in `lib/oauth.js`. If you fork or redeploy on a different Supabase project, set the correct authorization server address in your source before deploying. Verify MCP protected-resource metadata, OAuth redirect/client configuration, and userinfo-based identity validation.

- MCP URL: `https://advertpreneur-connect.vercel.app/api/mcp`
- Public health: `https://advertpreneur-connect.vercel.app/api/health`
- Browser-based, owner-scoped WP connection: call authenticated MCP `connect_site` to obtain a WordPress Application Password approval URL.
- Anonymous legacy `/connect?site_url=...` is disabled by default in v0.7.0.

Each new OAuth owner uses a scoped site ID. Existing sites may retain legacy IDs via the explicit `adpc_site_access` grant table; grants must be added only for a verified owner and an explicitly authorized site. Never make global grants to every user.

## WordPress plugin

Install the reviewed plugin ZIP from `releases/advertpreneur-connect-wordpress-v0.7.0.zip` on **authorized sites only**. Version 0.7.0 has per-object checks around attestation, SEO updates, featured media and WooCommerce; existing companion v0.6.3 has passed read-only compatibility checks with backend 0.7.0, but upgrade deliberately after site backup.

The user connects via WordPress native Application Password approval. Neither normal WordPress passwords nor service tokens should be shared in chat.

## Pre-release verification

1. Run `npm test`; check PHP syntax. Build Next.js through protected preview CI.
2. Run `qa/wordpress-integration.php` and `qa/media-test.php` only on the isolated disposable Docker WordPress installation. Never reuse these fixtures on production sites.
3. Verify new owner can list their sites, read content, create drafts and be blocked by stale/missing attestation; verify an unrelated owner cannot read IDs of another owner (live two-account test is a release gate for general-public onboarding).
4. Confirm live health v0.7.0, anonymous MCP and private Action API return HTTP 401, and verified authorized clients can still access their original IDs.
5. Verify each marketed AI client independently. Installing `SKILL.md` alone does not provide MCP authentication or WordPress capabilities.
6. Record the previous known-good Vercel deployment before promotion; monitor logs and rollback on failed read/write smoke tests.

## Deployment history (October 15, 2026)

- Two historical connected sites preserved via tenant-grant migration.
- New `advertpreneur-store-v2` Edge Function deployed independently.
- Initial Node24 DNS pinning regression detected during live smoke test; rollback executed successfully.
- Corrected backend 0.7.0 redeployed; live WordPress list/status/search checks passed, WordPress plugin 0.6.3 remained compatible.
- Separate agent/tenant approval tests are still required for unlimited public onboarding.
