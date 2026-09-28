export async function GET() {
  return Response.json({ ok: true, service: 'Advertpreneur Connect', version: '0.5.0', oauth: '/.well-known/oauth-protected-resource', actions: '/api/action/*', openapi: '/openapi.json', mcp: '/api/mcp' });
}
