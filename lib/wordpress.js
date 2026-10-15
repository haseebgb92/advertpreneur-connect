import https from 'node:https';
import dns from 'node:dns/promises';
import { isIP } from 'node:net';

// Never send an Application Password to local, private, link-local, metadata,
// mapped IPv4, documentation or carrier-grade NAT destinations.
export function isPublicInternetAddress(address) {
  const kind = isIP(String(address));
  if (kind === 4) {
    const oct = address.split('.').map(Number);
    const [a,b,c] = oct;
    if (!a || a === 10 || a === 127 || a >= 224 || a === 169 && b === 254 ||
        a === 172 && b >= 16 && b <= 31 ||
        a === 192 && (b === 168 || b === 0 || b === 0 && c === 0) ||
        a === 100 && b >= 64 && b <= 127 ||
        a === 198 && (b === 18 || b === 19 || b === 51 && c === 100) ||
        a === 203 && b === 0 && c === 113 ||
        a === 192 && b === 0 && c === 2 ||
        a === 192 && b === 88 && c === 99 ||
        a === 198 && b === 51 && c === 100 ||
        a === 255) return false;
    return true;
  }
  if (kind === 6) {
    const ip = address.toLowerCase().split('%')[0];
    // Disallow IPv4-mapped, 6to4, Teredo, and other non-native tunnels.
    if (ip.includes('.') || ip.includes('::ffff:') ||
        ip.startsWith('2002:') || ip.startsWith('2001:0:') ||
        ip.startsWith('2001:db8:')) return false;
    const prefix = Number.parseInt(ip.split(':')[0] || '0',16);
    return prefix >= 0x2000 && prefix < 0x4000;
  }
  return false;
}

// Node 24 uses the `all:true` lookup mode for family auto-selection.
// Always return the correct callback signature for the requested mode.
export function pinnedLookup(addressRecord) {
  return (_hostname, options, callback) => {
    if (options?.all) callback(null, [addressRecord]);
    else callback(null, addressRecord.address, addressRecord.family);
  };
}

export async function wpRequest(site, path, { method = 'GET', body, headers = {}, anonymous = false, timeoutMs = 30000 } = {}) {
  const base = new URL(site.siteUrl);
  if (base.protocol !== 'https:' || base.username || base.password || base.port) {
    throw new Error('WordPress host must use standard HTTPS without embedded credentials');
  }
  const url = new URL(path, base);
  if (url.protocol !== 'https:' || url.origin !== base.origin) throw new Error('Cross-origin WordPress request blocked');
  if (!url.pathname.startsWith('/wp-json/advertpreneur-connect/v1/')) throw new Error('Unexpected WordPress endpoint');
  if (String(method).toUpperCase() === 'GET') url.searchParams.set('_adpc_ts', String(Date.now()));

  const ips = await dns.lookup(url.hostname, { all: true, verbatim: true });
  if (!ips.length || !ips.every(({ address }) => isPublicInternetAddress(address))) {
    throw new Error('WordPress DNS resolved to an unsafe or missing address');
  }
  const selected = ips[0];
  const payload = body === undefined ? undefined : JSON.stringify(body);
  if (!anonymous && (!site.userLogin || !site.password)) throw new Error('WordPress application credential missing');
  const authorization = anonymous ? null : Buffer.from(`${site.userLogin}:${site.password}`).toString('base64');
  const requestHeaders = {
    Accept: 'application/json',
    ...(anonymous ? {} : { Authorization: `Basic ${authorization}` }),
    'Cache-Control': 'no-cache, no-store, max-age=0',
    Pragma: 'no-cache',
    ...(payload === undefined ? {} : { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(payload) }),
    ...headers,
  };
  // The caller must not override authorization, Host, or redirection safeguards.
  if (Object.keys(headers).some((key) => /^(authorization|host|proxy-authorization)$/i.test(key))) {
    throw new Error('Reserved headers cannot be overwritten');
  }
  return await new Promise((resolve, reject) => {
    const req = https.request(url, {
      method: String(method).toUpperCase(), headers: requestHeaders, timeout: timeoutMs,
      lookup: pinnedLookup(selected),
    }, (response) => {
      const parts = [];
      let total = 0;
      response.on('data', (chunk) => {
        total += chunk.length;
        if (total > 10 * 1024 * 1024) {
          req.destroy(new Error('WordPress response size limit exceeded'));
          return;
        }
        parts.push(chunk);
      });
      response.on('error', reject);
      response.on('end', () => {
        const text = Buffer.concat(parts).toString('utf8');
        let parsed;
        try { parsed = text ? JSON.parse(text) : null; }
        catch { parsed = { raw: text.slice(0, 1000) }; }
        const status = response.statusCode || 502;
        if (status < 200 || status >= 300) {
          const err = new Error(parsed?.message || `WordPress request failed (${status})`);
          err.status = status; err.payload = parsed; reject(err); return;
        }
        resolve(parsed);
      });
    });
    req.once('error', reject);
    req.once('timeout', () => req.destroy(new Error('WordPress request timed out')));
    req.end(payload);
  });
}


// Public read-only preflight: no application credentials and no redirects.
// A WordPress REST route returning rest_forbidden indicates the plugin exists
// and enforces login; a 404, web-server 403, HTTP 5xx or timeout is not ready.
export async function probeWordPressPlugin(siteUrl, request = wpRequest) {
  const site = { siteUrl };
  try {
    await request(site, '/wp-json/advertpreneur-connect/v1/status', {
      method: 'GET', anonymous: true, timeoutMs: 8000,
    });
    throw new Error('Advertpreneur Connect status is unexpectedly public; verify plugin permissions before connecting.');
  } catch (error) {
    if ((error.status === 401 || error.status === 403) && error.payload?.code === 'rest_forbidden') {
      return { installed: true, authenticated: false };
    }
    if (error.status === 404 || error.payload?.code === 'rest_no_route') {
      throw new Error('Advertpreneur Connect WordPress companion plugin is missing or inactive. Install and activate it first.');
    }
    if (error.status >= 500) throw new Error('WordPress REST endpoint returned a server error. Repair the site before connecting.');
    throw error;
  }
}
