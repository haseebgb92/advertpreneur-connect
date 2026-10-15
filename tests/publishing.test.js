import test from 'node:test';
import assert from 'node:assert/strict';
import { handleAction } from '../lib/action-api.js';
import { isPublicInternetAddress } from '../lib/wordpress.js';

const id = '0123456789abcdef01234567';
process.env.ADPC_ACTION_TOKEN = 'test-private-action-token';

test('draft, checks, attestation and publishing preserve site, order, id and response', async () => {
  const calls = [];
  const adapter = {
    getSite: async (requested, ownerId) => {
      assert.equal(requested, id);
      assert.equal(ownerId, 'legacy');
      return { id, siteUrl: 'https://wp.example', userLogin:'test', password:'fake' };
    },
    listSites: async (ownerId) => {
      assert.equal(ownerId, 'legacy');
      return [{ id }];
    },
    wpRequest: async (site, path, options = {}) => {
      assert.equal(site.siteUrl, 'https://wp.example');
      calls.push({path, method:options.method || 'GET', body:options.body});
      if (path.endsWith('/drafts')) return { post_id: 51, status: 'draft' };
      if (path.endsWith('/quality/check')) return { pass: true };
      if (path.endsWith('/quality/overlap')) return { pass: true };
      if (path.endsWith('/quality/attest')) return { ok: true };
      if (path.endsWith('/51/publish')) return { post_id: 51, status:'publish', url:'https://wp.example/test' };
      throw new Error('Unexpected WordPress endpoint: '+path);
    },
  };
  const request = (path, body) => new Request('https://bridge.example/api/action/' + path, {
    method: 'POST',
    headers: { authorization: 'Bearer test-private-action-token', 'content-type': 'application/json' },
    body: JSON.stringify(body),
  });
  const post = async (path,body) => handleAction(request(path,body),path.split('/'),adapter);
  const draft = await post(`sites/${id}/posts`, {title:'Test draft',content:'<p>Test</p>'});
  assert.equal(draft.status,201);
  assert.equal((await draft.json()).status,'draft');
  for (const action of ['check','overlap','attest']) {
    const result = await post(`sites/${id}/quality/${action}`,{post_id:51});
    assert.equal(result.status,200);
  }
  const response = await post(`sites/${id}/content/51/publish`, {});
  const published = await response.json();
  assert.equal(published.status,'publish');
  assert.equal(published.url,'https://wp.example/test');
  assert.deepEqual(calls.map(c=>c.path), [
    '/wp-json/advertpreneur-connect/v1/drafts',
    '/wp-json/advertpreneur-connect/v1/quality/check',
    '/wp-json/advertpreneur-connect/v1/quality/overlap',
    '/wp-json/advertpreneur-connect/v1/quality/attest',
    '/wp-json/advertpreneur-connect/v1/posts/51/publish',
  ]);
});

test('private Action API rejects missing bearer before touching storage', async () => {
  const res = await handleAction(new Request('https://bridge.example/api/action/sites'),['sites']);
  assert.equal(res.status,401);
});

test('outbound transport blocks cloud metadata, private and local endpoints', () => {
  for (const ip of ['127.0.0.1','10.1.2.3','192.168.1.4','172.20.1.3','169.254.169.254','100.100.100.100','0.0.0.0','198.18.0.1','203.0.113.8','::1','fc00::2','fe80::1','::ffff:127.0.0.1','2001:db8::1','2002:7f00:1::1']) {
    assert.equal(isPublicInternetAddress(ip),false,`Should deny ${ip}`);
  }
  for (const ip of ['1.1.1.1','8.8.8.8','2606:4700:4700::1111']) {
    assert.equal(isPublicInternetAddress(ip),true,`Should allow ${ip}`);
  }
});
