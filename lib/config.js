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
  return u.origin;
}

export function appId() {
  return process.env.ADPC_APP_ID || process.env.APP_ID || '53ef64c2-71b5-4cf2-baa7-e416f9cf7afd';
}
