# Advertpreneur Connect — AI-to-WordPress MCP & Action Bridge

**Current public backend: v0.6.1** · Next.js · Vercel · Supabase · WordPress · WooCommerce\n\nAdvertpreneur Connect is a controlled bridge that lets trusted AI clients work with WordPress content, SEO metadata, media and WooCommerce while enforcing site-aware checks and a quality-gated publishing workflow.\n\n**Website:** https://advertpreneur-connect.vercel.app · **Setup guide:** https://advertpreneur-connect.vercel.app/setup · **OpenAPI:** https://advertpreneur-connect.vercel.app/openapi.json

## Architecture

Custom GPT Action -> Vercel `/api/action/*` -> connected WordPress sites.

The MCP endpoint remains available for future compatible clients but is disabled unless `ADPC_MCP_BEARER_TOKEN` is explicitly configured.

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

The WordPress plugin never generates content itself. AI clients provide the writing; Advertpreneur Connect provides the controlled WordPress operations, site context and publication safeguards.\n\n## Quality-gated publishing\n\nThe intended workflow is:\n\n1. Search the existing site before drafting.\n2. Read the real content/SEO/taxonomy state.\n3. Create or update a draft.\n4. Run SEO, repetition and site-overlap checks.\n5. Store a passing quality attestation tied to the exact current content.\n6. Publish only while that approval is still valid.\n\nAny content mutation invalidates the earlier approval.

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
/connect?site_url=https://example.com
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

`ADPC_MCP_BEARER_TOKEN` is optional. If absent, `/api/mcp` returns disabled rather than exposing write tools without authentication.

## Action API / compatible AI clients

Import the schema from:

`https://advertpreneur-connect.vercel.app/openapi.json`

Configure API-key authentication as Bearer and use the same value as `ADPC_ACTION_TOKEN`.

## Direct image path

`uploadImageBase64` accepts actual base64 image bytes and sends them to the WordPress plugin, which validates the image, writes it into WordPress uploads, creates the media attachment, and returns a real `media_id`.

`importImageUrl` is the fallback path for a public HTTPS image URL.

Do not report an image as uploaded until WordPress returns a real media ID.
