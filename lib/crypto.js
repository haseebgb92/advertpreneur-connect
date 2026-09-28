import { createCipheriv, createDecipheriv, createHash, createHmac, randomBytes, timingSafeEqual } from 'node:crypto';

function secretKey() {
  const raw = process.env.ADPC_ENCRYPTION_KEY || process.env.ENCRYPTION_KEY;
  if (!raw || raw.length < 24) throw new Error('ADPC_ENCRYPTION_KEY is required and must be at least 24 characters');
  return createHash('sha256').update(raw).digest();
}

export function seal(value) {
  const iv = randomBytes(12);
  const cipher = createCipheriv('aes-256-gcm', secretKey(), iv);
  const body = Buffer.concat([cipher.update(value, 'utf8'), cipher.final()]);
  const tag = cipher.getAuthTag();
  return Buffer.concat([iv, tag, body]).toString('base64url');
}

export function openSealed(value) {
  const raw = Buffer.from(value, 'base64url');
  if (raw.length < 29) throw new Error('Invalid encrypted credential');
  const iv = raw.subarray(0, 12);
  const tag = raw.subarray(12, 28);
  const body = raw.subarray(28);
  const decipher = createDecipheriv('aes-256-gcm', secretKey(), iv);
  decipher.setAuthTag(tag);
  return Buffer.concat([decipher.update(body), decipher.final()]).toString('utf8');
}

export function siteId(siteUrl) {
  return createHash('sha256').update(new URL(siteUrl).origin).digest('hex').slice(0, 24);
}

function stateKey() {
  const raw = process.env.ADPC_STATE_SECRET || process.env.STATE_SECRET;
  if (!raw || raw.length < 24) throw new Error('ADPC_STATE_SECRET is required and must be at least 24 characters');
  return raw;
}

export function signedState(siteUrl) {
  const payload = Buffer.from(JSON.stringify({ siteUrl, exp: Date.now() + 30 * 60_000 })).toString('base64url');
  const sig = createHmac('sha256', stateKey()).update(payload).digest('base64url');
  return `${payload}.${sig}`;
}

export function verifyState(state) {
  const [payload, sig] = String(state || '').split('.');
  if (!payload || !sig) throw new Error('Invalid state');
  const expected = createHmac('sha256', stateKey()).update(payload).digest('base64url');
  const a = Buffer.from(sig);
  const b = Buffer.from(expected);
  if (a.length !== b.length || !timingSafeEqual(a, b)) throw new Error('Invalid state signature');
  const parsed = JSON.parse(Buffer.from(payload, 'base64url').toString('utf8'));
  if (!parsed.siteUrl || !parsed.exp || Date.now() > parsed.exp) throw new Error('Connection link expired');
  return parsed;
}
