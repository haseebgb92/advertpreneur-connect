import { openSealed, seal } from './crypto.js';

function storeConfig() {
  const url = process.env.ADPC_STORE_URL;
  const token = process.env.ADPC_STORE_TOKEN;
  if (!url) throw new Error('ADPC_STORE_URL is required');
  if (!token) throw new Error('ADPC_STORE_TOKEN is required');
  return { url, token };
}

async function storeRequest(action, payload = {}) {
  const { url, token } = storeConfig();
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-adpc-token': token,
    },
    body: JSON.stringify({ action, ...payload }),
    cache: 'no-store',
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data?.error || `Storage request failed (${res.status})`);
  return data;
}

export async function upsertSite({ id, siteUrl, userLogin, password }) {
  const result = await storeRequest('upsert', {
    site: {
      id,
      site_url: siteUrl.replace(/\/$/, ''),
      user_login: userLogin,
      credential_enc: seal(password),
    },
  });
  return result.site;
}

export async function listSites() {
  const result = await storeRequest('list');
  return result.sites || [];
}

export async function getSite(id) {
  const result = await storeRequest('get', { id });
  const row = result.site;
  if (!row) return null;

  return {
    id: row.id,
    siteUrl: row.site_url,
    userLogin: row.user_login,
    password: openSealed(row.credential_enc),
    connectedAt: row.connected_at,
  };
}
