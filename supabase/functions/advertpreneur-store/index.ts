import { createClient } from "npm:@supabase/supabase-js@2";

// Private server-to-server endpoint: the Vercel bridge verifies MCP identity and
// sends a trustworthy owner_id. Never expose ADPC_STORE_TOKEN to an agent or browser.
const tokenHash = Deno.env.get("ADPC_STORE_TOKEN_SHA256") || "";

function validOwner(value: unknown): value is string {
  return typeof value === "string" &&
    /^(legacy|oauth:[a-zA-Z0-9-]{12,80})$/.test(value);
}
function hex(buffer: ArrayBuffer) {
  return [...new Uint8Array(buffer)].map((v) => v.toString(16).padStart(2, "0")).join("");
}
async function validateToken(value: string | null) {
  if (!value || !/^[a-f0-9]{64}$/.test(tokenHash)) return false;
  const candidate = hex(await crypto.subtle.digest("SHA-256", new TextEncoder().encode(value)));
  let difference = 0;
  for (let n = 0; n < 64; n++) difference |= candidate.charCodeAt(n) ^ tokenHash.charCodeAt(n);
  return difference === 0;
}
function adminClient() {
  const url = Deno.env.get("SUPABASE_URL");
  const keys = Deno.env.get("SUPABASE_SECRET_KEYS");
  const legacyKey = Deno.env.get("SUPABASE_SERVICE_ROLE_KEY");
  let key = legacyKey || "";
  if (keys) {
    try { key = JSON.parse(keys).default || key; } catch { /* fallback to legacy */ }
  }
  if (!url || !key) throw new Error("Private store not configured");
  return createClient(url, key, { auth: { persistSession: false, autoRefreshToken: false } });
}
function error(message: string, status: number) {
  return Response.json({ error: message }, { status });
}

Deno.serve(async (request) => {
  if (request.method !== "POST") return error("Method not allowed", 405);
  if (!(await validateToken(request.headers.get("x-adpc-token")))) return error("Unauthorized", 401);
  try {
    const body = await request.json().catch(() => ({}));
    if (body.action === "health") return Response.json({ ok: true });
    const owner = body.owner_id;
    if (!validOwner(owner)) return error("Authenticated owner_id required", 400);
    const supabase = adminClient();
    const sites = supabase.from("adpc_connected_sites");

    if (body.action === "list") {
      const { data: own, error: ownError } = await sites
        .select("id,site_url,user_login,owner_id,connected_at,updated_at")
        .eq("owner_id", owner).order("connected_at", { ascending: false });
      if (ownError) throw ownError;
      const { data: grants, error: grantError } = await supabase
        .from("adpc_site_access").select("site_id").eq("owner_id", owner);
      if (grantError) throw grantError;
      const ids = (grants || []).map((row) => row.site_id);
      let shared = [];
      if (ids.length) {
        const { data, error: sharedError } = await sites
          .select("id,site_url,user_login,owner_id,connected_at,updated_at")
          .in("id", ids).order("connected_at", { ascending: false });
        if (sharedError) throw sharedError;
        shared = data || [];
      }
      const merged = new Map();
      for (const row of [...(own || []), ...shared]) merged.set(row.id, row);
      return Response.json({ sites: [...merged.values()] });
    }

    if (body.action === "get") {
      const id = String(body.id || "");
      if (!/^[a-f0-9]{24}$/.test(id)) return error("Invalid site id", 400);
      const { data, error: dbError } = await sites
        .select("id,site_url,user_login,owner_id,credential_enc,connected_at,updated_at")
        .eq("id", id).maybeSingle();
      if (dbError) throw dbError;
      if (!data) return Response.json({ site: null });
      if (data.owner_id === owner) return Response.json({ site: data, access_granted: true });
      const { data: grant, error: grantError } = await supabase
        .from("adpc_site_access").select("site_id")
        .eq("owner_id", owner).eq("site_id", id).maybeSingle();
      if (grantError) throw grantError;
      if (!grant) return Response.json({ site: null });
      return Response.json({ site: data, access_granted: true });
    }

    if (body.action === "upsert") {
      const site = body.site || {};
      if (site.owner_id !== owner || !site.id || !site.site_url ||
          !site.user_login || !site.credential_enc) return error("Invalid site ownership or fields", 400);
      const id = String(site.id);
      if (!/^[a-f0-9]{24}$/.test(id)) return error("Invalid site ID", 400);

      // An existing row from a different owner is NEVER overwritten.
      const { data: existing, error: lookupError } = await sites
        .select("owner_id").eq("id", id).maybeSingle();
      if (lookupError) throw lookupError;
      if (existing && existing.owner_id !== owner) return error("Site ID is owned by a different account", 409);
      const { data, error: dbError } = await sites.upsert({
        id, owner_id: owner, site_url: String(site.site_url),
        user_login: String(site.user_login), credential_enc: String(site.credential_enc),
        updated_at: new Date().toISOString(),
      }, { onConflict: "id" })
        .select("id,site_url,user_login,owner_id,connected_at,updated_at").single();
      if (dbError) throw dbError;
      return Response.json({ site: data });
    }

    return error("Unknown action", 400);
  } catch (e) {
    console.error("Private store request failed", e instanceof Error ? e.name : "unknown");
    return error("Storage operation failed", 500);
  }
});
