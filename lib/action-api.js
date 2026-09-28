import { getSite, listSites } from './db.js';
import { wpRequest } from './wordpress.js';

function response(data, status = 200) {
  return Response.json(data, { status });
}

function authorize(request) {
  const expected = process.env.ADPC_ACTION_TOKEN;
  if (!expected) return { ok: false, status: 503, message: 'Advertpreneur Action API is not configured.' };
  const auth = request.headers.get('authorization') || '';
  if (auth !== `Bearer ${expected}`) return { ok: false, status: 401, message: 'Unauthorized.' };
  return { ok: true };
}

async function body(request) {
  if (request.method === 'GET' || request.method === 'HEAD') return {};
  return request.json().catch(() => ({}));
}

async function connectedSite(siteId) {
  const site = await getSite(siteId);
  if (!site) {
    const error = new Error(`Unknown site_id: ${siteId}`);
    error.status = 404;
    throw error;
  }
  return site;
}

function qs(url, keys) {
  const params = new URLSearchParams();
  for (const key of keys) {
    const value = url.searchParams.get(key);
    if (value !== null && value !== '') params.set(key, value);
  }
  return params.toString();
}

function intId(value, name) {
  const n = Number.parseInt(String(value), 10);
  if (!Number.isInteger(n) || n <= 0) {
    const error = new Error(`${name} must be a positive integer.`);
    error.status = 400;
    throw error;
  }
  return n;
}

export async function handleAction(request, pathParts = []) {
  const auth = authorize(request);
  if (!auth.ok) return response({ ok: false, error: auth.message }, auth.status);

  try {
    const method = request.method.toUpperCase();
    const url = new URL(request.url);
    const parts = (pathParts || []).filter(Boolean);

    if (parts.length === 1 && parts[0] === 'sites' && method === 'GET') {
      return response({ ok: true, sites: await listSites() });
    }

    if (parts[0] !== 'sites' || !parts[1]) return response({ ok: false, error: 'Unknown action route.' }, 404);
    const siteId = parts[1];
    const site = await connectedSite(siteId);

    if (parts.length === 3 && parts[2] === 'status' && method === 'GET') {
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/status'));
    }

    if (parts.length === 3 && parts[2] === 'search' && method === 'GET') {
      const query = qs(url, ['q', 'type', 'limit']);
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/content/search?${query}`));
    }

    if (parts.length === 4 && parts[2] === 'content' && method === 'GET') {
      const id = intId(parts[3], 'content id');
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/content/${id}`));
    }

    if (parts.length === 3 && (parts[2] === 'posts' || parts[2] === 'pages') && method === 'POST') {
      const payload = await body(request);
      payload.type = parts[2] === 'pages' ? 'page' : 'post';
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/drafts', { method: 'POST', body: payload }), 201);
    }

    if (parts.length === 4 && parts[2] === 'content' && method === 'PATCH') {
      const id = intId(parts[3], 'content id');
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/posts/${id}`, { method: 'PATCH', body: await body(request) }));
    }

    if (parts.length === 5 && parts[2] === 'content' && parts[4] === 'publish' && method === 'POST') {
      const id = intId(parts[3], 'content id');
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/posts/${id}/publish`, { method: 'POST', body: {} }));
    }

    if (parts.length === 5 && parts[2] === 'content' && parts[4] === 'featured' && method === 'POST') {
      const id = intId(parts[3], 'content id');
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/posts/${id}/featured`, { method: 'POST', body: await body(request) }));
    }

    if (parts.length === 4 && parts[2] === 'quality' && parts[3] === 'check' && method === 'POST') {
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/quality/check', { method: 'POST', body: await body(request) }));
    }

    if (parts.length === 4 && parts[2] === 'quality' && parts[3] === 'overlap' && method === 'POST') {
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/quality/overlap', { method: 'POST', body: await body(request) }));
    }

    if (parts.length === 4 && parts[2] === 'quality' && parts[3] === 'attest' && method === 'POST') {
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/quality/attest', { method: 'POST', body: await body(request) }));
    }

    if (parts.length === 4 && parts[2] === 'media' && parts[3] === 'base64' && method === 'POST') {
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/media/base64', { method: 'POST', body: await body(request) }), 201);
    }

    if (parts.length === 4 && parts[2] === 'media' && parts[3] === 'import' && method === 'POST') {
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/media/import', { method: 'POST', body: await body(request) }), 201);
    }

    if (parts.length === 3 && parts[2] === 'products' && method === 'GET') {
      const query = qs(url, ['search', 'status', 'limit', 'page']);
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/products${query ? `?${query}` : ''}`));
    }

    if (parts.length === 3 && parts[2] === 'products' && method === 'POST') {
      return response(await wpRequest(site, '/wp-json/advertpreneur-connect/v1/products', { method: 'POST', body: await body(request) }), 201);
    }

    if (parts.length === 4 && parts[2] === 'products' && method === 'GET') {
      const id = intId(parts[3], 'product id');
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/products/${id}`));
    }

    if (parts.length === 4 && parts[2] === 'products' && method === 'PATCH') {
      const id = intId(parts[3], 'product id');
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/products/${id}`, { method: 'PATCH', body: await body(request) }));
    }

    if (parts.length === 5 && parts[2] === 'products' && parts[4] === 'publish' && method === 'POST') {
      const id = intId(parts[3], 'product id');
      return response(await wpRequest(site, `/wp-json/advertpreneur-connect/v1/products/${id}/publish`, { method: 'POST', body: {} }));
    }

    return response({ ok: false, error: 'Unknown action route.' }, 404);
  } catch (error) {
    const status = Number(error?.status) || 500;
    return response({
      ok: false,
      error: error?.message || 'Request failed.',
      wordpress: error?.payload || undefined,
    }, status >= 400 && status <= 599 ? status : 500);
  }
}
