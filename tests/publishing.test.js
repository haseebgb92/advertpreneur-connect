import test from 'node:test';
import assert from 'node:assert/strict';
import { seal } from '../lib/crypto.js';
import { handleAction } from '../lib/action-api.js';

const id = '0123456789abcdef01234567';
process.env.ADPC_ACTION_TOKEN = 'test-private-action-token';
process.env.ADPC_STORE_TOKEN = 'test-storage-token';
process.env.ADPC_STORE_URL = 'https://storage.example.test/private';
process.env.ADPC_ENCRYPTION_KEY = 'not-a-real-production-key-only-for-test-2345';

test('authenticated draft → quality → overlap → attestation routes preserve site and WordPress response', async () => {
  const oldFetch = globalThis.fetch;
  const calls = [];
  globalThis.fetch = async (url, options = {}) => {
    const u = new URL(url);
    const requestBody = options.body ? JSON.parse(options.body) : {};
    calls.push({ url: u.pathname, method: options.method || 'GET', requestBody });
    if (u.hostname === 'storage.example.test') return Response.json({
      site: { id, owner_id: 'legacy', site_url: 'https://wp.example', user_login: 'editor', credential_enc: seal('fake-app-password') },
    });
    assert.equal(u.hostname, 'wp.example');
    assert.match(options.headers.Authorization, /^Basic /);
    if (u.pathname.endsWith('/drafts')) return Response.json({ id: 51, status: 'draft' });
    if (u.pathname.endsWith('/quality/check')) return Response.json({ passed: true });
    if (u.pathname.endsWith('/quality/overlap')) return Response.json({ passed: true });
    if (u.pathname.endsWith('/quality/attest')) return Response.json({ approved: true });
    if (u.pathname.endsWith('/51/publish')) return Response.json({ id: 51, status: 'publish', link: 'https://wp.example/test' });
    return Response.json({ error: 'not found' }, { status: 404 });
  };
  const request = (path, body) => new Request('https://bridge.example/api/action/' + path, {
    method: 'POST', headers: { authorization: 'Bearer test-private-action-token', 'content-type': 'application/json' },
    body: JSON.stringify(body),
  });
  try {
    const draft = await handleAction(request(`sites/${id}/posts`, { title: 'Test draft', content: '<p>Content</p>' }), ['sites', id, 'posts']);
    assert.equal((await draft.json()).status, 'draft');
    for (const action of ['check','overlap','attest']) {
      const resp = await handleAction(request(`sites/${id}/quality/${action}`, { post_id: 51 }), ['sites',id,'quality',action]);
      assert.equal(resp.status, 200);
    }
    const published = await handleAction(request(`sites/${id}/content/51/publish`, {}), ['sites',id,'content','51','publish']);
    const data = await published.json();
    assert.equal(data.status, 'publish');
    assert.equal(data.link, 'https://wp.example/test');
    assert.deepEqual(calls.filter(c => c.url.startsWith('/wp-json/')).map(c=>c.url), [
      '/wp-json/advertpreneur-connect/v1/drafts',
      '/wp-json/advertpreneur-connect/v1/quality/check',
      '/wp-json/advertpreneur-connect/v1/quality/overlap',
      '/wp-json/advertpreneur-connect/v1/quality/attest',
      '/wp-json/advertpreneur-connect/v1/posts/51/publish',
    ]);
  } finally { globalThis.fetch = oldFetch; }
});

test('Action API refuses requests without bearer authentication', async () => {
  const response = await handleAction(new Request('https://bridge.example/api/action/sites', { method:'GET' }), ['sites']);
  assert.equal(response.status, 401);
  const json = await response.json();
  assert.equal(json.ok,false);
});
