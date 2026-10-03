# Supabase setup for Advertpreneur Connect

Advertpreneur Connect uses Supabase for two separate purposes:

1. **Private connected-site storage** through a protected Edge Function.
2. **OAuth authorization for MCP clients** through Supabase Auth.

The WordPress site's normal login password is never stored. WordPress issues an Application Password after the user approves the connection, and Advertpreneur Connect encrypts that credential before sending it to Supabase storage.

## 1. Create the Supabase project

Create a new Supabase project and keep its project reference handy.

The production Advertpreneur Connect deployment uses a standard Supabase project with PostgreSQL, Auth and Edge Functions.

## 2. Create the connected-sites table

Run this SQL in the Supabase SQL editor:

```sql
create table if not exists public.adpc_connected_sites (
  id text primary key,
  site_url text not null unique,
  user_login text not null,
  credential_enc text not null,
  connected_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

alter table public.adpc_connected_sites enable row level security;
```

Advertpreneur Connect does **not** access this table directly from the browser. The private storage Edge Function uses a Supabase backend secret key, so there is no reason to create permissive public RLS policies.

## 3. Create the storage Edge Function

Create an Edge Function named:

```text
advertpreneur-store
```

This function accepts only `POST` requests and supports four actions:

- `health`
- `list`
- `get`
- `upsert`

Use a custom shared secret between Vercel and the Edge Function. Store the raw secret as an Edge Function secret named:

```text
ADPC_STORE_TOKEN
```

Then use this function implementation:

```ts
import { createClient } from "npm:@supabase/supabase-js@2";

function adminClient() {
  const url = Deno.env.get("SUPABASE_URL");
  const modern = Deno.env.get("SUPABASE_SECRET_KEYS");
  const legacy = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY");

  let key = legacy || "";
  if (modern) {
    try {
      const parsed = JSON.parse(modern);
      key = parsed.default || key;
    } catch {}
  }

  if (!url || !key) {
    throw new Error("Supabase admin environment is unavailable");
  }

  return createClient(url, key, {
    auth: { persistSession: false, autoRefreshToken: false },
  });
}

Deno.serve(async (req) => {
  try {
    if (req.method !== "POST") {
      return Response.json({ error: "Method not allowed" }, { status: 405 });
    }

    const expected = Deno.env.get("ADPC_STORE_TOKEN");
    const supplied = req.headers.get("x-adpc-token");

    if (!expected || !supplied || supplied !== expected) {
      return Response.json({ error: "Unauthorized" }, { status: 401 });
    }

    const body = await req.json().catch(() => ({}));
    const action = body?.action;
    const supabase = adminClient();

    if (action === "health") {
      return Response.json({ ok: true, service: "advertpreneur-store" });
    }

    if (action === "list") {
      const { data, error } = await supabase
        .from("adpc_connected_sites")
        .select("id,site_url,user_login,connected_at,updated_at")
        .order("connected_at", { ascending: false });

      if (error) throw error;
      return Response.json({ sites: data ?? [] });
    }

    if (action === "get") {
      const id = String(body?.id || "");
      if (!id) {
        return Response.json({ error: "id required" }, { status: 400 });
      }

      const { data, error } = await supabase
        .from("adpc_connected_sites")
        .select("id,site_url,user_login,credential_enc,connected_at,updated_at")
        .eq("id", id)
        .maybeSingle();

      if (error) throw error;
      return Response.json({ site: data ?? null });
    }

    if (action === "upsert") {
      const site = body?.site || {};

      if (!site.id || !site.site_url || !site.user_login || !site.credential_enc) {
        return Response.json({ error: "Incomplete site payload" }, { status: 400 });
      }

      const { data, error } = await supabase
        .from("adpc_connected_sites")
        .upsert({
          id: String(site.id),
          site_url: String(site.site_url),
          user_login: String(site.user_login),
          credential_enc: String(site.credential_enc),
          updated_at: new Date().toISOString(),
        }, { onConflict: "id" })
        .select("id,site_url,user_login,connected_at,updated_at")
        .single();

      if (error) throw error;
      return Response.json({ site: data });
    }

    return Response.json({ error: "Unknown action" }, { status: 400 });
  } catch (error) {
    console.error(error);
    return Response.json(
      { error: error instanceof Error ? error.message : "Internal error" },
      { status: 500 },
    );
  }
});
```

Because the function authenticates requests with its own `x-adpc-token` secret, deploy it with Supabase JWT verification disabled and keep the function private through the custom token check.

## 4. Set the Edge Function secret

Create a long random value and use the same value in two places:

- Supabase Edge Function secret: `ADPC_STORE_TOKEN`
- Vercel environment variable: `ADPC_STORE_TOKEN`

Set the Vercel `ADPC_STORE_URL` to:

```text
https://<project-ref>.supabase.co/functions/v1/advertpreneur-store
```

Never commit the token.

## 5. Configure Supabase Auth as the MCP OAuth server

Advertpreneur Connect exposes:

```text
/.well-known/oauth-protected-resource
```

That metadata points OAuth-capable MCP clients to the Supabase Auth authorization server:

```text
https://<project-ref>.supabase.co/auth/v1
```

The current MCP implementation validates access tokens using:

```text
/auth/v1/oauth/userinfo
```

and requires a token containing a `client_id` claim.

The requested scope is currently:

```text
email
```

## 6. Enable the Supabase OAuth server

In the Supabase Dashboard, configure the Auth OAuth server for your project.

Set the authorization/consent path to the Advertpreneur Connect consent page:

```text
https://<your-advertpreneur-connect-domain>/oauth/consent
```

Advertpreneur Connect already contains the consent UI at:

```text
app/oauth/consent/page.js
```

That page receives Supabase's `authorization_id`, shows the requesting client and requested permissions, then approves or denies the authorization through Supabase Auth.

## 7. Register the MCP client

In Supabase:

**Authentication → OAuth Apps → Add a new client**

Create a separate OAuth client for each environment/client where practical.

Configure:

- Client name
- Exact redirect URI required by your MCP client
- Client type: public or confidential as appropriate

OAuth redirect URIs must be exact matches.

For production, use HTTPS.

## 8. Vercel environment variables

Set these variables on the Advertpreneur Connect Vercel project:

```text
ADPC_STORE_URL=
ADPC_STORE_TOKEN=
ADPC_ENCRYPTION_KEY=
ADPC_STATE_SECRET=
ADPC_ACTION_TOKEN=
ADPC_MCP_BEARER_TOKEN=
ADPC_APP_ID=
```

### Required

`ADPC_STORE_URL`
: Supabase Edge Function URL.

`ADPC_STORE_TOKEN`
: Shared Vercel ↔ Supabase storage secret.

`ADPC_ENCRYPTION_KEY`
: Long random secret used to encrypt WordPress Application Password credentials before storage.

`ADPC_STATE_SECRET`
: Long random secret used to sign WordPress connection state.

`ADPC_ACTION_TOKEN`
: Bearer used by the Action/OpenAPI API.

### Optional

`ADPC_MCP_BEARER_TOKEN`
: Enables static bearer access for private non-OAuth MCP clients. Leave unset if OAuth-only access is desired.

`ADPC_APP_ID`
: Stable UUID presented to WordPress's Application Password authorization screen.

## 9. Connect a WordPress site

Install and activate the Advertpreneur Connect companion WordPress plugin, then open:

```text
https://<your-advertpreneur-connect-domain>/connect?site_url=https://example.com
```

The flow is:

```text
Advertpreneur Connect
  → WordPress /wp-admin/authorize-application.php
  → user approves
  → WordPress returns Application Password
  → Advertpreneur Connect encrypts it
  → encrypted credential is stored through Supabase
```

## 10. Verify the full stack

Check:

```text
GET /api/health
GET /openapi.json
```

Then test:

1. `list_sites`
2. `site_status`
3. `search_site_content`
4. create/update a draft
5. run SEO/quality/overlap checks
6. attest the exact current content
7. publish

For MCP, connect to:

```text
https://<your-advertpreneur-connect-domain>/api/mcp
```

For OpenAPI/Action clients, use:

```text
https://<your-advertpreneur-connect-domain>/openapi.json
```

with:

```text
Authorization: Bearer <ADPC_ACTION_TOKEN>
```

## Security notes

- Keep Supabase secret/service-role keys server-side only.
- Do not expose `ADPC_STORE_TOKEN`, `ADPC_ACTION_TOKEN`, `ADPC_ENCRYPTION_KEY`, or `ADPC_STATE_SECRET`.
- Keep RLS enabled on `adpc_connected_sites`.
- Do not add public RLS policies unless you intentionally redesign the storage layer.
- Use exact HTTPS OAuth redirect URIs in production.
- Prefer a separate OAuth client per environment.
- Rotate any leaked bearer or encryption secret immediately.
