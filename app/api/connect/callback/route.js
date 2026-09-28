import { normalizeSiteUrl } from '../../../../lib/config.js';
import { siteId, verifyState } from '../../../../lib/crypto.js';
import { upsertSite } from '../../../../lib/db.js';

function page(title, body) {
  return new Response(`<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${title}</title></head><body style="font-family:system-ui,-apple-system,sans-serif;max-width:720px;margin:64px auto;padding:0 20px;color:#171717"><h1>${title}</h1>${body}</body></html>`, { headers: { 'content-type': 'text/html; charset=utf-8' } });
}

export async function GET(request) {
  try {
    const url = new URL(request.url);
    const state = verifyState(url.searchParams.get('state'));
    const returnedSite = normalizeSiteUrl(url.searchParams.get('site_url') || state.siteUrl);
    if (returnedSite !== normalizeSiteUrl(state.siteUrl)) throw new Error('Site mismatch in callback');

    const userLogin = url.searchParams.get('user_login');
    const password = url.searchParams.get('password');
    if (!userLogin || !password) throw new Error('WordPress did not return application credentials');

    const id = siteId(returnedSite);
    await upsertSite({ id, siteUrl: returnedSite, userLogin, password });

    const back = new URL('/wp-admin/admin.php', returnedSite);
    back.searchParams.set('page', 'advertpreneur-connect');
    back.searchParams.set('adpc_connected', '1');
    return Response.redirect(back.toString(), 302);
  } catch (error) {
    return page('Connection failed', `<p>${String(error?.message || error)}</p>`);
  }
}
