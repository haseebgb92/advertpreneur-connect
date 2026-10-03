export const metadata = {
  title: 'Setup',
  description: 'Deploy Advertpreneur Connect with Vercel, Supabase storage and OAuth, then connect WordPress sites and MCP clients.',
};

const envVars = [
  ['ADPC_STORE_URL', 'Private Supabase Edge Function URL used for connected-site storage.'],
  ['ADPC_STORE_TOKEN', 'Shared secret expected by the private storage Edge Function.'],
  ['ADPC_ENCRYPTION_KEY', 'Long random secret used to encrypt stored WordPress application credentials.'],
  ['ADPC_STATE_SECRET', 'Long random secret used to sign short-lived WordPress connection state.'],
  ['ADPC_ACTION_TOKEN', 'Bearer token used by the Action API / OpenAPI clients.'],
  ['ADPC_MCP_BEARER_TOKEN', 'Optional static MCP bearer. Leave unset when using OAuth-only MCP access.'],
  ['ADPC_APP_ID', 'Stable UUID presented to WordPress Application Password authorization.'],
];

export default function SetupPage() {
  return (
    <main>
      <style>{`
        :root{--ink:#111318;--muted:#68707b;--line:#e6e8ec;--soft:#f7f8fa;--green:#1f8f64}*{box-sizing:border-box}body{margin:0;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;background:#fff}a{color:inherit}.shell{width:min(1040px,calc(100% - 36px));margin:auto}.top{border-bottom:1px solid var(--line);position:sticky;top:0;background:rgba(255,255,255,.9);backdrop-filter:blur(16px);z-index:10}.nav{height:70px;display:flex;align-items:center;justify-content:space-between}.brand{text-decoration:none;font-weight:850}.btn{display:inline-flex;align-items:center;height:40px;padding:0 14px;border:1px solid var(--line);border-radius:10px;text-decoration:none;font-weight:800;font-size:14px}.hero{padding:80px 0 44px}.kicker{text-transform:uppercase;letter-spacing:.1em;font-size:12px;color:#8c929b;font-weight:850}h1{font-size:clamp(44px,7vw,72px);line-height:1;letter-spacing:-.055em;margin:12px 0 20px}.lead{max-width:780px;color:var(--muted);font-size:19px;line-height:1.7}.steps{padding:22px 0 90px}.step{display:grid;grid-template-columns:70px 1fr;gap:22px;padding:32px 0;border-top:1px solid var(--line)}.num{width:48px;height:48px;border-radius:14px;background:var(--ink);color:#fff;display:grid;place-items:center;font-weight:850}.step h2{font-size:30px;letter-spacing:-.035em;margin:0 0 10px}.step p,.step li{color:var(--muted);line-height:1.7}.box{border:1px solid var(--line);background:var(--soft);border-radius:16px;padding:18px;margin:16px 0}.env{display:grid;gap:10px;margin-top:16px}.env div{border:1px solid var(--line);background:#fff;border-radius:12px;padding:14px}.env code{display:block;font-weight:850;margin-bottom:4px}.env span{color:var(--muted);font-size:14px}pre{overflow:auto;background:#101318;color:#eef1f5;border-radius:15px;padding:18px;font:14px/1.7 ui-monospace,SFMono-Regular,Menlo,monospace}code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace}.note{border-left:3px solid var(--green);padding:2px 0 2px 16px;margin:16px 0;color:var(--muted)}footer{border-top:1px solid var(--line);padding:34px 0 50px;color:#7d848d;font-size:13px}@media(max-width:650px){.step{grid-template-columns:1fr}.shell{width:min(100% - 24px,1040px)}}`}</style>

      <header className="top"><div className="shell nav"><a className="brand" href="/">Advertpreneur Connect</a><a className="btn" href="https://github.com/haseebgb92/advertpreneur-connect">GitHub</a></div></header>

      <section className="hero"><div className="shell"><div className="kicker">Deployment & authentication guide</div><h1>Set up the full Advertpreneur Connect stack.</h1><p className="lead">This is the complete path from a fresh deployment to a connected WordPress site and an authenticated MCP or Action client: Vercel, Supabase storage, Supabase OAuth, WordPress Application Passwords and the Advertpreneur Connect endpoints.</p></div></section>

      <section className="steps"><div className="shell">
        <article className="step"><div className="num">1</div><div><h2>Deploy the Next.js service to Vercel</h2><p>Fork or clone the repository, import it into Vercel and deploy the <code>main</code> branch. Advertpreneur Connect automatically uses the Vercel production URL unless <code>ADPC_BASE_URL</code> is explicitly set.</p><pre>{`git clone https://github.com/haseebgb92/advertpreneur-connect.git
cd advertpreneur-connect
npm install
npm run build`}</pre><p>Your production service becomes the public bridge URL, for example <code>https://advertpreneur-connect.vercel.app</code>.</p></div></article>

        <article className="step"><div className="num">2</div><div><h2>Create the private Supabase storage layer</h2><p>Advertpreneur Connect does not store connected WordPress credentials directly in the Vercel filesystem. The service calls a private Supabase Edge Function using <code>ADPC_STORE_URL</code> and <code>ADPC_STORE_TOKEN</code>.</p><div className="box"><strong>Storage contract</strong><p>The Edge Function receives JSON actions such as <code>upsert</code>, <code>list</code> and <code>get</code>, protected by the <code>x-adpc-token</code> header. Stored WordPress application credentials are already encrypted by Advertpreneur Connect before being sent to storage.</p></div><p>Create a Supabase project, a private table for connected sites, and an Edge Function that implements this small storage contract. Keep the function token private and set its URL/token in Vercel.</p></div></article>

        <article className="step"><div className="num">3</div><div><h2>Configure Supabase Auth for MCP OAuth</h2><p>The MCP endpoint supports OAuth using Supabase Auth as its authorization server. Advertpreneur Connect publishes OAuth protected-resource metadata at <code>/.well-known/oauth-protected-resource</code>, pointing compatible MCP clients at the Supabase authorization server.</p><div className="box"><strong>Current OAuth flow</strong><p><code>/api/mcp</code> challenges unauthenticated clients → client discovers the protected-resource metadata → OAuth is handled by Supabase Auth → Advertpreneur Connect validates the bearer token against Supabase <code>/oauth/userinfo</code> before granting MCP access.</p></div><p>The current code requests the <code>email</code> scope. Configure the Supabase OAuth/authorization-server settings for the deployed project and make sure your allowed redirect/client configuration matches the MCP client you intend to use.</p><div className="note">A separate static bearer remains supported through <code>ADPC_MCP_BEARER_TOKEN</code> for private non-OAuth MCP clients. If the variable is absent, static bearer access is not enabled.</div></div></article>

        <article className="step"><div className="num">4</div><div><h2>Add the Vercel environment variables</h2><div className="env">{envVars.map(([k,v])=><div key={k}><code>{k}</code><span>{v}</span></div>)}</div><p>Generate the encryption and state secrets as long random values. Never commit production values to GitHub.</p></div></article>

        <article className="step"><div className="num">5</div><div><h2>Install the WordPress companion plugin</h2><p>The Vercel service talks to the Advertpreneur Connect WordPress plugin through its REST namespace. The plugin provides the actual site-side capabilities for posts, pages, media, SEO, quality checks and WooCommerce.</p><p>Install and activate the companion plugin on every WordPress site you want the bridge to manage.</p></div></article>

        <article className="step"><div className="num">6</div><div><h2>Connect each WordPress site securely</h2><p>Advertpreneur Connect uses WordPress's native Application Password authorization screen. It never asks for the site's normal WordPress password.</p><pre>{`https://advertpreneur-connect.vercel.app/connect?site_url=https://example.com`}</pre><p>The user approves Advertpreneur Connect inside WordPress. WordPress then sends the application credential back to the bridge callback, where it is encrypted before being stored through the Supabase storage service.</p></div></article>

        <article className="step"><div className="num">7</div><div><h2>Connect an Action/OpenAPI client</h2><p>The authenticated Action API uses <code>ADPC_ACTION_TOKEN</code> as a bearer token.</p><pre>{`Schema:
https://advertpreneur-connect.vercel.app/openapi.json

Authorization:
Bearer <ADPC_ACTION_TOKEN>`}</pre><p>This route is useful for clients that consume OpenAPI-style actions instead of MCP.</p></div></article>

        <article className="step"><div className="num">8</div><div><h2>Connect an MCP client</h2><p>Use the deployed MCP URL:</p><pre>{`https://advertpreneur-connect.vercel.app/api/mcp`}</pre><p>OAuth-capable clients can follow the Supabase OAuth discovery flow. Private clients may instead use the optional static MCP bearer when you deliberately configure one.</p></div></article>

        <article className="step"><div className="num">9</div><div><h2>Verify the stack</h2><ul><li>Open <code>/api/health</code> and confirm the deployment is responding.</li><li>Open <code>/openapi.json</code> and confirm it reports the current public API version.</li><li>Connect a test WordPress site and verify it appears in <code>list_sites</code>.</li><li>Run <code>site_status</code> to confirm plugin capabilities.</li><li>Test content search/read before attempting any write.</li><li>Run a draft → quality checks → attestation → publish workflow end to end.</li></ul></div></article>
      </div></section>

      <footer><div className="shell">Advertpreneur Connect setup guide · Vercel + Supabase + WordPress + MCP</div></footer>
    </main>
  );
}
