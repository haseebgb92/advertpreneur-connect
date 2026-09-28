import { handleAction } from '../../../../lib/action-api.js';

export const runtime = 'nodejs';
export const dynamic = 'force-dynamic';
export const maxDuration = 60;

async function route(request, context) {
  const { path = [] } = await context.params;
  return handleAction(request, path);
}

export { route as GET, route as POST, route as PATCH };
