export async function GET() {
  return Response.json({ ok: true, service: 'Advertpreneur Connect', version: '0.2.0', mcp: '/api/mcp' });
}
