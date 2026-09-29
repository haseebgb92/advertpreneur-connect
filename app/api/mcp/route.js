import { createMcpHandler } from 'mcp-handler';
import { registerTools } from '../../../lib/mcp-tools.js';
import { oauthChallenge, verifyMcpAuthorization } from '../../../lib/oauth.js';

const mcp = createMcpHandler(
  (server) => {
    registerTools(server);
  },
  {
    serverInfo: { name: 'advertpreneur-connect', version: '0.6.0' },
    instructions: 'WordPress bridge only. Never invent site facts. Search existing site content before proposing new content. Prefer drafts. Before assigning categories or tags, inspect real site terms. Before adding internal links, use suggest_internal_links and only link real published URLs where contextually useful. Detect the active SEO plugin with seo_status before writing SEO metadata; never optimize toward a plugin score at the expense of readable, accurate copy. Before generating or selecting an image for a known WordPress slot, call get_image_requirements and follow the actual placeholder ratio; never invent fixed dimensions when the slot is unknown. Preserve native image quality, do not upscale, and never substitute a dummy or blank image for the requested asset. Never claim an image was uploaded unless WordPress returned a media_id. Publishing requires a current passing quality attestation.',
  },
);

async function handle(request) {
  const url = new URL(request.url);
  const auth = await verifyMcpAuthorization(request);
  if (!auth.ok) {
    return new Response('Unauthorized', {
      status: 401,
      headers: {
        'WWW-Authenticate': oauthChallenge(url.origin, 'invalid_token', auth.reason || 'Sign in to Advertpreneur Connect to continue.'),
      },
    });
  }

  return mcp(request);
}

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 60;

export { handle as GET, handle as POST };
