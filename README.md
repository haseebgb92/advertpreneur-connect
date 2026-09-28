# Advertpreneur Connect — v0.4

Secure WordPress bridge hosted on Vercel.

## Architecture

Custom GPT Action -> Vercel `/api/action/*` -> connected WordPress sites.

The MCP endpoint remains available for future compatible clients but is disabled unless `ADPC_MCP_BEARER_TOKEN` is explicitly configured.

WordPress credentials are created through WordPress's native Application Password authorization screen, encrypted by the Vercel service, and persisted through a private Supabase Edge Function. The user's normal WordPress password is never requested.

## Managed WordPress data

- Blog posts
- Pages
- Media uploads/imports
- Featured images
- WooCommerce products when WooCommerce is active
- Local-site content search
- Deterministic repetition/thin-content checks
- Site-overlap/cannibalization checks
- Quality-attested publishing

The WordPress plugin never generates content itself.

## Required Vercel environment variables

- `ADPC_STORE_URL`
- `ADPC_STORE_TOKEN`
- `ADPC_ENCRYPTION_KEY`
- `ADPC_STATE_SECRET`
- `ADPC_ACTION_TOKEN`

`ADPC_MCP_BEARER_TOKEN` is optional. If absent, `/api/mcp` returns disabled rather than exposing write tools without authentication.

## Custom GPT Actions

Import the schema from:

`https://advertpreneur-connect.vercel.app/openapi.json`

Configure API-key authentication as Bearer and use the same value as `ADPC_ACTION_TOKEN`.

## Direct image path

`uploadImageBase64` accepts actual base64 image bytes and sends them to the WordPress plugin, which validates the image, writes it into WordPress uploads, creates the media attachment, and returns a real `media_id`.

`importImageUrl` is the fallback path for a public HTTPS image URL.

Do not report an image as uploaded until WordPress returns a real media ID.
