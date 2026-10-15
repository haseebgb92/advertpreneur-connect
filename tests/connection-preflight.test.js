import test from 'node:test';
import assert from 'node:assert/strict';
import { probeWordPressPlugin } from '../lib/wordpress.js';

function failure(status, code) { const e = new Error('Site error'); e.status = status; e.payload = { code }; return e; }
const fakeReq = (err) => async (site, path, opts) => {
  assert.equal(site.siteUrl, 'https://lovfah.com');
  assert.equal(path, '/wp-json/advertpreneur-connect/v1/status');
  assert.equal(opts.anonymous, true);
  assert.equal(opts.timeoutMs, 8000);
  assert.equal(opts.method, 'GET');
  if (err) throw err;
  return { ok: true };
};

test('connection preflight accepts authenticated WordPress REST permission denial', async () => {
  const got = await probeWordPressPlugin('https://lovfah.com', fakeReq(failure(401,'rest_forbidden')));
  assert.equal(got.installed, true);
  const editorDenied = await probeWordPressPlugin('https://lovfah.com', fakeReq(failure(403,'rest_forbidden')));
  assert.equal(editorDenied.installed, true);
});

test('connection preflight rejects absent plugin, broken REST, WAF and public status', async () => {
  await assert.rejects(probeWordPressPlugin('https://lovfah.com', fakeReq(failure(404,'rest_no_route'))), /missing or inactive/);
  await assert.rejects(probeWordPressPlugin('https://lovfah.com', fakeReq(failure(500,'internal_error'))), /server error/);
  await assert.rejects(probeWordPressPlugin('https://lovfah.com', fakeReq(failure(403,'waf_block'))), /Site error/);
  await assert.rejects(probeWordPressPlugin('https://lovfah.com', fakeReq(null)), /unexpectedly public/);
});

test('connection preflight fails closed on timeout and DNS resolution errors', async () => {
  await assert.rejects(probeWordPressPlugin('https://lovfah.com', fakeReq(new Error('WordPress request timed out'))), /timed out/);
  await assert.rejects(probeWordPressPlugin('https://lovfah.com', fakeReq(new Error('unsafe DNS'))), /unsafe DNS/);
});
