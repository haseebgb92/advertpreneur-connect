export async function wpRequest(site, path, { method = 'GET', body, headers = {} } = {}) {
  const url = new URL(path, `${site.siteUrl.replace(/\/$/, '')}/`).toString();
  const auth = Buffer.from(`${site.userLogin}:${site.password}`).toString('base64');
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 30000);

  try {
    const res = await fetch(url, {
      method,
      headers: {
        Authorization: `Basic ${auth}`,
        Accept: 'application/json',
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
