import { protectedResourceMetadata } from '../../../lib/oauth.js';

export const dynamic = 'force-dynamic';

export async function GET(request) {
  const url = new URL(request.url);
  return Response.json(protectedResourceMetadata(url.origin), {
    headers: { 'cache-control': 'public, max-age=300' },
  });
}
