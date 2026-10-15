# Advertpreneur Connect — AI-to-WordPress MCP & Action Bridge

**Backend v0.7.0** · Next.js · Vercel · Supabase · WordPress · WooCommerce

Advertpreneur Connect is a controlled bridge that lets trusted AI clients work with WordPress content, SEO metadata, media and WooCommerce while enforcing site-aware checks and a quality-gated publishing workflow.\n\n**Website:** https://advertpreneur-connect.vercel.app · **Setup guide:** https://advertpreneur-connect.vercel.app/setup · **Supabase/OAuth setup:** [docs/SUPABASE_SETUP.md](docs/SUPABASE_SETUP.md) · **OpenAPI:** https://advertpreneur-connect.vercel.app/openapi.json

## Architecture

Custom GPT Action -> Vercel `/api/action/*` -> connected WordPress sites.

The MCP endpoint is active with Supabase OAuth for authenticated agents. An optional static bearer is reserved for trusted private clients, and cannot be shared with marketplace buyers.

WordPress credentials are created through WordPress's native Application Password authorization screen, encrypted by the Vercel service, and persisted through a private Supabase Edge Function. The user's normal WordPress password is never requested.

## What it can manage

- Blog posts
- Pages
- Media uploads/imports
- Featured images
- WooCommerce products when WooCommerce is active
- Local-site content search
- Deterministic repetition/thin-content checks
- Site-overlap/cannibalization checks
- Quality-attested publishing

The WordPress plugin never generates content itself. AI clients provide the writing; Advertpreneur Connect provides the controlled WordPress operations, site context and publication safeguards.

## Quality-gated publishing

The intended workflow is:

1. Search the existing site before drafting.
2. Read the real content/SEO/taxonomy state.
3. Create or update a draft.
4. Run SEO, repetition and site-overlap checks.
5. Store a passing quality attestation tied to the exact current content.
6. Publish only while that approval is still valid.

Any content mutation invalidates the earlier approval.

## Full deployment setup

The production stack has four distinct layers:

1. **Vercel / Next.js service** — public API, OpenAPI, MCP endpoint, OAuth metadata and WordPress connection callbacks.
2. **Supabase private storage** — stores connected-site records through a private Edge Function.
3. **Supabase Auth / OAuth** — authorization server for OAuth-capable MCP clients.
4. **WordPress companion plugin** — site-side REST capabilities for content, SEO, media, quality checks and WooCommerce.

A complete setup guide is available at:

**https://advertpreneur-connect.vercel.app/setup**

### Supabase storage

Advertpreneur Connect calls a private Supabase Edge Function configured through:

- `ADPC_STORE_URL`
- `ADPC_STORE_TOKEN`

The service sends storage actions such as `upsert`, `list` and `get` using the `x-adpc-token` header. WordPress Application Password credentials are encrypted by Advertpreneur Connect before they are persisted.

### MCP OAuth with Supabase Auth

The MCP endpoint supports Supabase-backed OAuth. Advertpreneur Connect publishes protected-resource metadata at:

```text
/.well-known/oauth-protected-resource
```

The OAuth flow is:

```text
MCP client
  → /api/mcp
  → OAuth challenge / protected-resource metadata
  → Supabase Auth authorization server
  → Supabase bearer token
  → Advertpreneur Connect validates /oauth/userinfo
  → MCP tools become available
```

The current MCP OAuth scope is `email`.

A separate static bearer can still be enabled for private non-OAuth MCP clients with `ADPC_MCP_BEARER_TOKEN`.

### WordPress site authorization

WordPress sites use native **Application Password** authorization rather than the normal WordPress password:

```text
MCP connect_site {"site_url":"https://example.com"}
        ↓
WordPress /wp-admin/authorize-application.php
        ↓
user approves Advertpreneur Connect
        ↓
/api/connect/callback
        ↓
credential encrypted and stored through Supabase
```

## Required Vercel environment variables

- `ADPC_STORE_URL`
- `ADPC_STORE_TOKEN`
- `ADPC_ENCRYPTION_KEY`
- `ADPC_STATE_SECRET`
- `ADPC_ACTION_TOKEN`

`ADPC_MCP_BEARER_TOKEN` is optional and intended only for a private operator. Without it, OAuth clients can still authenticate to `/api/mcp`; requests without valid bearer authentication return HTTP 401.

## Action API / compatible AI clients

Import the schema from:

`https://advertpreneur-connect.vercel.app/openapi.json`

The global `ADPC_ACTION_TOKEN` is a privileged legacy/private integration credential: **never distribute it to customers or in an Agensi skill**. Marketplace buyers must authenticate using the per-user OAuth MCP endpoint.

## Direct image path

`uploadImageBase64` accepts actual base64 image bytes and sends them to the WordPress plugin, which validates the image, writes it into WordPress uploads, creates the media attachment, and returns a real `media_id`.

`importImageUrl` is the fallback path for a public HTTPS image URL.

Do not report an image as uploaded until WordPress returns a real media ID.

## Agensi marketplace distribution

Use the [Advertpreneur Connect v0.2.0 agent skill](releases/advertpreneur-connect-agensi-skill-v0.2.0.zip) from the Agensi ZIP to guide content planning, real WordPress draft creation, SEO checks, approved image uploads, factual/overlap assessment, and user-approved publishing. A separate WordPress companion plugin is required. Skill installation alone never grants WordPress access.

- **Buyer MCP URL:** `https://advertpreneur-connect.vercel.app/api/mcp`
- **Companion WordPress plugin:** [`releases/advertpreneur-connect-wordpress-v0.7.0.zip`](releases/advertpreneur-connect-wordpress-v0.7.0.zip)
- **Compatibility:** see [verified and pending agent tests](docs/AGENSI_COMPATIBILITY.md). ChatGPT MCP read-only operations are verified, Codex MCP OAuth setup is verified; other clients need end-to-end tests.
- **Multi-tenant isolation:** OAuth users see only sites owned or explicitly granted to their identity. Existing private legacy IDs are preserved via a private grant table.
- **Release tests:** `npm test` and the disposable WordPress QA scripts in `qa/`, plus live read-only smoke tests.

Keep static bearer tokens, Application Passwords and production environment values out of marketplace ZIPs, web pages and support emails.
