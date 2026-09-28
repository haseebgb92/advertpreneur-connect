# Advertpreneur Connect — Vercel v0.2

Central MCP service for Advertpreneur Connect.

## Architecture

ChatGPT -> Vercel `/api/mcp` -> connected WordPress sites.

WordPress credentials are created through the native Application Password authorization screen and stored encrypted in Postgres. The service never asks for the user's normal WordPress password.

## Environment variables

- `ADPC_DATABASE_URL` — Supabase transaction-pooler PostgreSQL connection string
- `ADPC_ENCRYPTION_KEY` — long random secret for AES-256-GCM credential encryption
- `ADPC_STATE_SECRET` — long random secret for signed WordPress connection state
- `ADPC_APP_ID` — stable UUID; default is included
- `ADPC_BASE_URL` — optional override; Vercel production URL is auto-detected
- `ADPC_MCP_BEARER_TOKEN` — optional private-test bearer token; production distribution should use proper MCP authentication/OAuth

## Direct image path

`upload_image_base64` sends real base64 image bytes to the Advertpreneur Connect WordPress plugin, which validates MIME, writes the image to WordPress uploads, creates the attachment, generates metadata, and returns a WordPress `media_id` and hosted URL.

`import_image_url` is the fallback path for a public HTTPS image URL.
