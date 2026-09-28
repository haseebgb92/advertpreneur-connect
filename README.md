# Advertpreneur Connect — Vercel v0.2

Central MCP service for Advertpreneur Connect.

## Architecture

ChatGPT -> Vercel `/api/mcp` -> connected WordPress sites.

WordPress credentials are created through the native Application Password authorization screen and stored encrypted in Postgres. The service never asks for the user's normal WordPress password.

## Required environment variables

- `DATABASE_URL` — persistent PostgreSQL connection string (Supabase, Neon, Vercel-compatible Postgres, etc.)
- `ENCRYPTION_KEY` — long random secret for AES-256-GCM credential encryption
- `STATE_SECRET` — long random secret for signed WordPress connection state
- `PUBLIC_BASE_URL` — stable production URL such as `https://advertpreneur-connect.vercel.app`
- `MCP_BEARER_TOKEN` — optional private-test bearer token; production distribution should use OAuth
- `APP_ID` — stable UUID; default is included

## Deploy

1. Create/import this directory as a Vercel Next.js project.
2. Add the required environment variables.
3. Deploy.
4. Set `PUBLIC_BASE_URL` to the production URL and redeploy if needed.
5. Configure ChatGPT custom MCP endpoint as `https://<host>/api/mcp`.

## Direct image path

`upload_image_base64` sends real base64 image bytes to the Advertpreneur Connect WordPress plugin, which validates MIME, writes the image to WordPress uploads, creates the attachment, generates metadata, and returns a WordPress `media_id` and hosted URL.

`import_image_url` is the fallback path for a public HTTPS image URL.
