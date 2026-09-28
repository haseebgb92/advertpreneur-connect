import { createMcpHandler } from 'mcp-handler';
import { registerTools } from '../../../lib/mcp-tools.js';

const mcp = createMcpHandler(
  (server) => {
    registerTools(server);
  },
  {
    serverInfo: { name: 'advertpreneur-connect', version: '0.2.0' },
    instructions: 'WordPress bridge only. Never invent site facts. Search existing site content before proposing a new article. Prefer drafts. Never claim an image was uploaded unless WordPress returned a media_id. Publishing requires a current passing quality attestation.',
  },
);

function authorized(request) {
  const expected = process.env.MCP_BEARER_TOKEN;
  if (!expected) return true;
  return request.headers.get('authorization') === `Bearer ${expected}`;
}

async function handle(request) {
  if (!authorized(request)) return new Response('Unauthorized', { status: 401 });
  return mcp(request);
}

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 60;

export { handle as GET, handle as POST };
