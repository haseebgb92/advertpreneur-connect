export async function GET() {
  return Response.json({ ok: true, service: 'Advertpreneur Connect', version: '0.4.0', actions: '/api/action/*', openapi: '/openapi.json', mcp: '/api/mcp' });
}
