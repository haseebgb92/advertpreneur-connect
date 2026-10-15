import test from 'node:test';
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { normalizeSiteUrl } from '../lib/config.js';
import { seal, openSealed, siteId, signedState, verifyState } from '../lib/crypto.js';
import { getSite, listSites, upsertSite } from '../lib/db.js';

const owner1 = 'oauth:1ac62ea0-2e75-418a-80ac-b9136b3d4f43';
const owner2 = 'oauth:2bd73eb1-3f86-529b-91bd-c0247c4e5f54';
process.env.ADPC_ENCRYPTION_KEY = 'test-key-with-high-length-not-real-secret';
process.env.ADPC_STATE_SECRET = 'test-hmac-with-high-length-not-real-secret';
process.env.ADPC_STORE_URL = 'https://example.test/store';
process.env.ADPC_STORE_TOKEN = 'fake-test-token';

test('state is signed, tenant-specific and rejects tampering', () => {
  const signed = signedState('https://wordpress.example', owner1);
  assert.equal(verifyState(signed).ownerId, owner1);
  assert.throws(() => verifyState(signed.slice(0, -1) + 'Z'));
  assert.throws(() => signedState('https://wordpress.example', ''));
  assert.throws(() => signedState('https://wordpress.example', 'bogus'));
});

test('same URL produces independent IDs for OAuth owners; legacy IDs unchanged', () => {
  const url = 'https://wordpress.example';
  const original = createHash('sha256').update(url).digest('hex').slice(0,24);
  assert.equal(siteId(url, 'legacy'), original);
  assert.notEqual(siteId(url, owner1), siteId(url, owner2));
});

test('AES sealed credentials are encrypted and recoverable with key', () => {
  const sealed = seal('test-app-password');
  assert.ok(!sealed.includes('test-app-password'));
  assert.equal(openSealed(sealed), 'test-app-password');
});

test('reject local addresses and embedded credentials', () => {
  for (const url of ['http://my-site.example','https://localhost','https://127.0.0.1','https://site.local','https://u:pass@example.com']) {
    assert.throws(() => normalizeSiteUrl(url));
  }
  assert.equal(normalizeSiteUrl('https://www.example.com/path'), 'https://www.example.com');
});

test('storage calls carry owner; cross-account get is denied even if store returns wrong row', async () => {
  const oldFetch = globalThis.fetch;
  let action;
  globalThis.fetch = async (_url, args) => {
    action = JSON.parse(args.body);
    if (action.action === 'list') return Response.json({ sites: [{id:'abc', owner_id:owner1}] });
    if (action.action === 'get') return Response.json({ site: {
      id:'abc',owner_id:owner2,site_url:'https://wordpress.example',user_login:'test',credential_enc:seal('app-pass')
    } });
    return Response.json({ site: action.site });
  };
  try {
    await listSites(owner1);
    assert.equal(action.owner_id, owner1);
    await assert.rejects(getSite('abc', owner1), /owner mismatch/);
    assert.equal(action.owner_id, owner1);
    await upsertSite({ id: siteId('https://wordpress.example', owner1),ownerId:owner1,
      siteUrl:'https://wordpress.example',userLogin:'test',password:'app-pass' });
    assert.equal(action.site.owner_id, owner1);
    assert.equal(action.owner_id, owner1);
    await assert.rejects(listSites(undefined), /owner is required/);
    await assert.rejects(getSite('abc', undefined), /owner is required/);
  } finally {
    globalThis.fetch = oldFetch;
  }
});
