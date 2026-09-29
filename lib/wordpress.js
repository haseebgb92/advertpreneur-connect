export async function wpRequest(site, path, { method = 'GET', body, headers = {} } = {}) {
  const url = new URL(path, `${site.siteUrl.replace(/\/$/, '')}/`);
  if (String(method).toUpperCase() === 'GET') {
    // WordPress/CDN layers sometimes cache authenticated REST GETs. Every read
    // must reflect the state written moments earlier, so use a cache buster.
    url.searchParams.set('_adpc_ts', String(Date.now()));
  }
  const auth = Buffer.from(`${site.userLogin}:${site.password}`).toString('base64');
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 30000);

  try {
    const res = await fetch(url.toString(), {
      method,
      headers: {
        Authorization: `Basic ${auth}`,
        Accept: 'application/json',
        'Cache-Control': 'no-cache, no-store, max-age=0',
        Pragma: 'no-cache',
        ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
        ...headers,
      },
      body: body !== undefined ? JSON.stringify(body) : undefined,
      redirect: 'follow',
      cache: 'no-store',
      signal: controller.signal,
    });

    const raw = await res.text();
    let payload;
    try { payload = raw ? JSON.parse(raw) : null; } catch { payload = { raw }; }
    if (!res.ok) {
      const err = new Error(payload?.message || `WordPress request failed (${res.status})`);
      err.status = res.status;
      err.payload = payload;
      throw err;
    }
    return payload;
  } finally {
    clearTimeout(timeout);
  }
}
