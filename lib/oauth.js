const SUPABASE_AUTH_BASE = 'https://hwhioqvsxazyxziyrhdg.supabase.co/auth/v1';

function decodeJwtPayload(token) {
  try {
    const parts = String(token || '').split('.');
    if (parts.length !== 3) return null;
    return JSON.parse(Buffer.from(parts[1], 'base64url').toString('utf8'));
  } catch {
    return null;
  }
}

export function protectedResourceMetadata(origin) {
  return {
    resource: `${origin}/api/mcp`,
    authorization_servers: [SUPABASE_AUTH_BASE],
    scopes_supported: ['email'],
    resource_documentation: origin,
  };
}

export function oauthChallenge(origin, error = 'invalid_token', description = 'Sign in to Advertpreneur Connect to continue.') {
  const metadata = `${origin}/.well-known/oauth-protected-resource`;
  return `Bearer resource_metadata="${metadata}", scope="email", error="${error}", error_description="${description.replace(/"/g, '')}"`;
}

export async function verifyMcpAuthorization(request) {
  const auth = request.headers.get('authorization') || '';
  if (!auth.startsWith('Bearer ')) return { ok: false, reason: 'Missing bearer token' };

  const token = auth.slice(7).trim();

  // Keep optional static bearer support for non-OAuth MCP clients.
  const staticToken = process.env.ADPC_MCP_BEARER_TOKEN;
  if (staticToken && token === staticToken) {
    return { ok: true, kind: 'static', subject: 'private-client' };
  }

  const payload = decodeJwtPayload(token);
  if (!payload?.client_id) return { ok: false, reason: 'OAuth client_id claim missing' };
  if (payload.exp && Number(payload.exp) * 1000 <= Date.now()) return { ok: false, reason: 'OAuth token expired' };

  const res = await fetch(`${SUPABASE_AUTH_BASE}/oauth/userinfo`, {
    headers: { authorization: `Bearer ${token}` },
    cache: 'no-store',
  });

  if (!res.ok) return { ok: false, reason: 'Supabase rejected the OAuth token' };
  const user = await res.json().catch(() => null);
  if (!user?.sub) return { ok: false, reason: 'OAuth user identity missing' };

  return {
    ok: true,
    kind: 'oauth',
    subject: user.sub,
    email: user.email || null,
    clientId: payload.client_id,
  };
}
