import { createMcpHandler } from 'mcp-handler';
import { registerTools } from '../../../lib/mcp-tools.js';

const mcp = createMcpHandler(
  (server) => {
    registerTools(server);
  },
  {
    serverInfo: { name: 'advertpreneur-connect', version: '0.4.0' },
    instructions: 'WordPress bridge only. Never invent site facts. Search existing site content before proposing new content. Prefer drafts. Never claim an image was uploaded unless WordPress returned a media_id. Publishing requires a current passing quality attestation.',
  },
);

function authorized(request) {
  const expected = process.env.ADPC_MCP_BEARER_TOKEN;
  if (!expected) return { ok: false, status: 503, message: 'MCP endpoint is disabled until ADPC_MCP_BEARER_TOKEN is configured.' };
  if (request.headers.get('authorization') !== `Bearer ${expected}`) return { ok: false, status: 401, message: 'Unauthorized' };
  return { ok: true };
}

async function handle(request) {
  const auth = authorized(request);
  if (!auth.ok) return new Response(auth.message, { status: auth.status });
  return mcp(request);
}

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 60;

export { handle as GET, handle as POST };
