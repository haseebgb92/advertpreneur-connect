import { openApiDocument } from '../../lib/openapi.js';

export const dynamic = 'force-dynamic';

export async function GET(request) {
  const url = new URL(request.url);
  const base = `${url.protocol}//${url.host}`;
  return Response.json(openApiDocument(base), {
    headers: { 'cache-control': 'public, max-age=300' },
  });
}
