import { isIP } from 'node:net';

export function publicBaseUrl() {
  if (process.env.ADPC_BASE_URL) return process.env.ADPC_BASE_URL.replace(/\/$/, '');
  const host = process.env.VERCEL_PROJECT_PRODUCTION_URL || process.env.VERCEL_URL;
  if (host) return `https://${host.replace(/^https?:\/\//, '').replace(/\/$/, '')}`;
  return 'http://localhost:3000';
}

export function normalizeSiteUrl(input) {
  const raw = String(input || '').trim();
  const u = new URL(raw.includes('://') ? raw : `https://${raw}`);
  if (u.protocol !== 'https:') throw new Error('HTTPS WordPress URL required');
  if (u.username || u.password) throw new Error('Credentials are not permitted in a WordPress URL');
  if (u.port) throw new Error('WordPress must use standard HTTPS port 443');
  const host = u.hostname.toLowerCase().replace(/\.$/, '');
  if (isIP(host) || host === 'localhost' || host.endsWith('.localhost') || host.endsWith('.local') || host.endsWith('.internal')) {
    throw new Error('WordPress must have a publicly resolvable HTTPS hostname');
  }
  return u.origin;
}


// WordPress may report its site_url as http:// behind a TLS proxy even when
// the actual browser-facing site is HTTPS. Trust ONLY our signed expected HTTPS
// origin, accepting the callback's HTTP value solely as a same-host assertion.
export function validateWordPressCallbackSite(expectedSignedUrl, reportedSiteUrl) {
  const expected = normalizeSiteUrl(expectedSignedUrl);
  if (!reportedSiteUrl) return expected;
  const reported = new URL(String(reportedSiteUrl));
  const signed = new URL(expected);
  if (!['http:', 'https:'].includes(reported.protocol) ||
      reported.hostname.toLowerCase().replace(/\.$/, '') !== signed.hostname.toLowerCase().replace(/\.$/, '') ||
      reported.port || reported.username || reported.password) {
    throw new Error('Site mismatch in WordPress authorization callback');
  }
  return expected;
}

export function appId() {
  return process.env.ADPC_APP_ID || process.env.APP_ID || '53ef64c2-71b5-4cf2-baa7-e416f9cf7afd';
}
