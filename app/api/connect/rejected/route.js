export async function GET() {
  return new Response('<!doctype html><html><body style="font-family:system-ui,-apple-system,sans-serif;max-width:720px;margin:64px auto;padding:0 20px"><h1>Connection cancelled</h1><p>No WordPress credential was stored.</p></body></html>', { headers: { 'content-type': 'text/html; charset=utf-8' } });
}
