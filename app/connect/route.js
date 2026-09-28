import { appId, normalizeSiteUrl, publicBaseUrl } from '../../lib/config.js';
import { signedState } from '../../lib/crypto.js';

export async function GET(request) {
  try {
    const incoming = new URL(request.url);
    const site = normalizeSiteUrl(incoming.searchParams.get('site_url'));
    const state = signedState(site);
    const base = publicBaseUrl();

    const approve = new URL('/wp-admin/authorize-application.php', site);
    approve.searchParams.set('app_name', 'Advertpreneur Connect');
    approve.searchParams.set('app_id', appId());
    approve.searchParams.set('success_url', `${base}/api/connect/callback?state=${encodeURIComponent(state)}`);
    approve.searchParams.set('reject_url', `${base}/api/connect/rejected`);

    return Response.redirect(approve.toString(), 302);
  } catch (error) {
    return new Response(`Unable to start connection: ${String(error?.message || error)}`, { status: 400 });
  }
}
