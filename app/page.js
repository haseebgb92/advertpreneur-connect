const featureCards = [
  { n: '01', title: 'WordPress content', text: 'Read, search, create and update posts and pages through controlled WordPress actions.' },
  { n: '02', title: 'SEO-aware publishing', text: 'Work with Yoast, Rank Math, AIOSEO or SEOPress metadata, internal links and deterministic SEO preflight checks.' },
  { n: '03', title: 'Quality gates', text: 'Thin-content, repetition and site-overlap checks feed an attestation tied to the exact content before publishing.' },
  { n: '04', title: 'Real media handling', text: 'Inspect destination image requirements, upload actual image bytes, import approved URLs and set validated featured images.' },
  { n: '05', title: 'WooCommerce', text: 'Read, create, update and publish products with pricing, stock, categories, tags and images.' },
  { n: '06', title: 'Multi-site bridge', text: 'Connect multiple WordPress sites through native Application Password authorization without asking for normal WordPress passwords.' },
];

const toolGroups = [
  ['Content', 'Search site content, read source content, create drafts, update posts/pages and publish after approval.'],
  ['SEO', 'Detect SEO plugins, read/write metadata, suggest internal links and run SEO audits.'],
  ['Quality', 'Run deterministic quality checks, site-overlap checks and content-hash-bound quality attestations.'],
  ['Media', 'Inspect image slots, upload image bytes, import HTTPS images, update media metadata and set featured images.'],
  ['Commerce', 'List, read, draft, update and quality-gate WooCommerce products.'],
];

export default function Home() {
  return (
    <main className="site">
      <style>{`
        :root{--ink:#111318;--muted:#6a717d;--line:#e7e9ed;--soft:#f7f8fa;--green:#1f8f64;--shadow:0 28px 90px rgba(16,19,24,.08)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:#fff;color:var(--ink);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;-webkit-font-smoothing:antialiased}
        a{color:inherit}.shell{width:min(1160px,calc(100% - 36px));margin:auto}.top{position:sticky;top:0;z-index:20;background:rgba(255,255,255,.88);backdrop-filter:blur(18px);border-bottom:1px solid var(--line)}
        .nav{height:72px;display:flex;align-items:center;justify-content:space-between;gap:20px}.brand{display:flex;align-items:center;gap:10px;text-decoration:none;font-weight:850;letter-spacing:-.02em}.mark{width:35px;height:35px;border:1px solid var(--ink);border-radius:10px;display:grid;place-items:center;font-weight:900;font-size:14px}
        .links{display:flex;gap:24px;color:var(--muted);font-size:14px}.links a{text-decoration:none}.actions{display:flex;gap:10px;flex-wrap:wrap}.btn{display:inline-flex;align-items:center;justify-content:center;height:44px;padding:0 17px;border:1px solid var(--line);border-radius:12px;background:#fff;text-decoration:none;font-weight:800;font-size:14px}.btn.dark{background:var(--ink);border-color:var(--ink);color:#fff}
        .hero{padding:96px 0 54px;text-align:center}.pill{display:inline-flex;align-items:center;gap:8px;border:1px solid var(--line);border-radius:999px;padding:8px 12px;background:#fbfcfd;color:#59606b;font-size:12px;font-weight:800}.pill:before{content:"";width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 0 5px rgba(31,143,100,.10)}
        h1{max-width:980px;margin:22px auto;font-size:clamp(48px,7vw,82px);line-height:.99;letter-spacing:-.06em}.hero p{max-width:820px;margin:0 auto;color:var(--muted);font-size:20px;line-height:1.6}.hero .actions{justify-content:center;margin-top:30px}.meta{display:flex;justify-content:center;gap:20px;flex-wrap:wrap;color:#8d939c;font-size:13px;margin-top:20px}
        .flowWrap{padding:20px 0 82px}.flow{display:grid;grid-template-columns:1fr auto 1fr auto 1fr;gap:12px;align-items:center;border:1px solid var(--line);border-radius:24px;background:var(--soft);padding:22px;box-shadow:var(--shadow)}.flowCard{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}.flowCard b{display:block;margin-bottom:5px}.flowCard span{color:var(--muted);font-size:13px}.arrow{color:#a1a6ae;font-weight:900}
        .section{padding:94px 0}.soft{background:var(--soft);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}.head{display:grid;grid-template-columns:1fr .85fr;gap:50px;align-items:end;margin-bottom:40px}.eyebrow{text-transform:uppercase;letter-spacing:.1em;font-size:12px;font-weight:850;color:#9197a0;margin-bottom:12px}h2{margin:0;font-size:clamp(36px,5vw,58px);line-height:1.05;letter-spacing:-.048em}.head p{margin:0;color:var(--muted);font-size:17px;line-height:1.65}
        .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.card{min-height:250px;border:1px solid var(--line);border-radius:20px;padding:24px;background:#fff;display:flex;flex-direction:column;justify-content:space-between}.num{width:42px;height:42px;border:1px solid var(--line);border-radius:12px;display:grid;place-items:center;font-size:12px;font-weight:850;background:#fafbfc}.card h3{font-size:21px;letter-spacing:-.025em;margin:0 0 8px}.card p{color:var(--muted);margin:0;line-height:1.6}
        .quality{display:grid;grid-template-columns:.9fr 1.1fr;gap:56px;align-items:center}.quality p{color:var(--muted);font-size:17px}.pipeline{border:1px solid var(--line);border-radius:22px;background:#fff;padding:22px;box-shadow:var(--shadow)}.step{display:flex;gap:16px;align-items:flex-start;padding:15px 4px;border-bottom:1px solid var(--line)}.step:last-child{border-bottom:0}.step b{width:31px;height:31px;border-radius:50%;background:var(--ink);color:#fff;display:grid;place-items:center;flex:0 0 31px;font-size:12px}.step strong{display:block}.step span{display:block;color:var(--muted);font-size:13px;margin-top:3px}
        .tools{display:grid;grid-template-columns:repeat(5,1fr);gap:12px}.tool{border:1px solid var(--line);border-radius:17px;background:#fff;padding:20px}.tool h3{font-size:17px;margin:0 0 7px}.tool p{font-size:13px;color:var(--muted);margin:0}
        .secure{display:grid;grid-template-columns:1fr 1fr;gap:18px}.secureCard{border:1px solid var(--line);border-radius:22px;padding:28px;background:#fff}.secureCard.green{background:#eef6f2;border-color:#d8e7df}.secureCard h3{font-size:26px;letter-spacing:-.03em;margin:0 0 10px}.secureCard p{color:var(--muted);margin:0}
        .dev{display:grid;grid-template-columns:1.08fr .92fr;gap:18px}.code{border-radius:20px;background:#101318;color:#edf1f6;padding:24px}.codebar{display:flex;justify-content:space-between;color:#939aa4;font-size:12px;margin-bottom:18px}pre{margin:0;white-space:pre-wrap;font:14px/1.7 ui-monospace,SFMono-Regular,Menlo,monospace}.devCard{border:1px solid var(--line);border-radius:20px;padding:26px;background:#fff}.devCard h3{font-size:27px;margin:0 0 8px}.devCard p{color:var(--muted)}
        .final{padding:110px 0}.finalBox{border-radius:30px;background:var(--ink);color:#fff;text-align:center;padding:64px}.finalBox p{max-width:650px;color:#c5cad2;margin:14px auto 26px;font-size:17px}.finalBox .actions{justify-content:center}.finalBox .btn{background:#fff;color:#111;border-color:#fff}
        footer{border-top:1px solid var(--line);padding:34px 0 48px;color:#7d848d;font-size:13px}.foot{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}.footlinks{display:flex;gap:18px}
        @media(max-width:920px){.links{display:none}.head,.quality,.dev{grid-template-columns:1fr}.grid{grid-template-columns:1fr 1fr}.tools{grid-template-columns:1fr 1fr}.flow{grid-template-columns:1fr}.arrow{text-align:center}}
        @media(max-width:620px){.shell{width:min(100% - 24px,1160px)}.nav .actions .btn:first-child{display:none}.grid,.tools,.secure{grid-template-columns:1fr}h1{font-size:48px}.hero p{font-size:17px}.section{padding:72px 0}.finalBox{padding:44px 22px}}
      `}</style>

      <header className="top">
        <div className="shell nav">
          <a className="brand" href="/"><span className="mark">AC</span>Advertpreneur Connect</a>
          <nav className="links"><a href="#features">Capabilities</a><a href="#quality">Quality</a><a href="#security">Security</a><a href="#developers">Developers</a><a href="/setup">Setup</a></nav>
          <div className="actions"><a className="btn" href="https://github.com/haseebgb92/advertpreneur-connect">GitHub</a><a className="btn" href="/setup">Setup</a><a className="btn dark" href="/openapi.json">OpenAPI</a></div>
        </div>
      </header>

      <section className="hero">
        <div className="shell">
          <div className="pill">WordPress + WooCommerce · MCP + Actions · v0.6.1</div>
          <h1>A safer bridge between AI and WordPress.</h1>
          <p>Advertpreneur Connect gives trusted AI clients controlled access to WordPress content, SEO, media and WooCommerce — with site-aware checks and quality-gated publishing instead of blind automation.</p>
          <div className="actions"><a className="btn dark" href="/setup">Setup Advertpreneur Connect</a><a className="btn" href="https://github.com/haseebgb92/advertpreneur-connect">View on GitHub</a></div>
          <div className="meta"><span>WordPress</span><span>WooCommerce</span><span>MCP</span><span>Vercel</span><span>Supabase</span><span>SEO-aware</span></div>
        </div>
      </section>

      <section className="flowWrap">
        <div className="shell"><div className="flow">
          <div className="flowCard"><b>AI client</b><span>ChatGPT, compatible MCP client or action caller</span></div><div className="arrow">→</div>
          <div className="flowCard"><b>Advertpreneur Connect</b><span>Authentication, routing, quality gates and tool contracts</span></div><div className="arrow">→</div>
          <div className="flowCard"><b>Your WordPress site</b><span>Native REST endpoints through the companion WordPress plugin</span></div>
        </div></div>
      </section>

      <section className="section soft" id="features">
        <div className="shell">
          <div className="head"><div><div className="eyebrow">Controlled publishing surface</div><h2>More than “AI can post to WordPress.”</h2></div><p>The bridge exposes the pieces an AI workflow actually needs to work responsibly: existing-site search, real taxonomy and SEO state, validated images, commerce data and a publication gate tied to the exact content being approved.</p></div>
          <div className="grid">{featureCards.map((f)=><article className="card" key={f.n}><div className="num">{f.n}</div><div><h3>{f.title}</h3><p>{f.text}</p></div></article>)}</div>
        </div>
      </section>

      <section className="section" id="quality">
        <div className="shell quality">
          <div><div className="eyebrow">Quality before publish</div><h2>Publishing is a workflow, not a single API call.</h2><p>Content mutations invalidate earlier approval. Advertpreneur Connect can search the site before drafting, inspect SEO, detect structural problems and overlap, then store a quality attestation tied to the current content before WordPress allows publication.</p></div>
          <div className="pipeline">
            <div className="step"><b>1</b><div><strong>Search the existing site</strong><span>Reduce repetitive topics and search-intent cannibalization.</span></div></div>
            <div className="step"><b>2</b><div><strong>Build or update the draft</strong><span>Use real categories, tags, internal links, images and SEO metadata.</span></div></div>
            <div className="step"><b>3</b><div><strong>Run deterministic checks</strong><span>SEO preflight, repetition/thin-content checks and site-overlap analysis.</span></div></div>
            <div className="step"><b>4</b><div><strong>Attest the exact content</strong><span>Approval is tied to the current normalized body/hash.</span></div></div>
            <div className="step"><b>5</b><div><strong>Publish</strong><span>WordPress blocks publish when the current content does not have a passing attestation.</span></div></div>
          </div>
        </div>
      </section>

      <section className="section soft">
        <div className="shell">
          <div className="head"><div><div className="eyebrow">Tool surface</div><h2>Built around real WordPress work.</h2></div><p>The MCP layer and Action API cover the operational pieces that matter for content and commerce workflows, without making the WordPress plugin itself generate copy.</p></div>
          <div className="tools">{toolGroups.map(([t,p])=><div className="tool" key={t}><h3>{t}</h3><p>{p}</p></div>)}</div>
        </div>
      </section>

      <section className="section" id="security">
        <div className="shell">
          <div className="head"><div><div className="eyebrow">Security model</div><h2>Connect with WordPress-native credentials, not passwords in chat.</h2></div><p>Site authorization uses WordPress Application Passwords. The user’s normal WordPress password is never requested by Advertpreneur Connect.</p></div>
          <div className="secure"><div className="secureCard green"><h3>One-click WordPress authorization</h3><p>Advertpreneur Connect creates the native WordPress application-authorization URL and receives a scoped application credential after approval.</p></div><div className="secureCard"><h3>Encrypted service storage</h3><p>Credentials are encrypted by the Vercel service and persisted through the private storage layer. MCP is disabled unless its own bearer token is explicitly configured.</p></div></div>
        </div>
      </section>

      <section className="section soft" id="developers">
        <div className="shell">
          <div className="head"><div><div className="eyebrow">Developers</div><h2>Run it on Vercel. Bring your own WordPress sites.</h2></div><p>The service is a Next.js app backed by Vercel and Supabase. Its OpenAPI document is available directly from the deployment, while the MCP endpoint remains independently authenticated.</p></div>
          <div className="dev"><div className="code"><div className="codebar"><span>Local development</span><span>bash</span></div><pre>{`git clone https://github.com/haseebgb92/advertpreneur-connect.git
cd advertpreneur-connect
npm install
npm run dev`}</pre></div><div className="devCard"><div className="eyebrow">Live endpoints</div><h3>Inspect the public contract.</h3><p><strong>OpenAPI:</strong> <code>/openapi.json</code><br/><strong>Actions:</strong> <code>/api/action/*</code><br/><strong>MCP:</strong> <code>/api/mcp</code><br/><strong>Health:</strong> <code>/api/health</code></p><a className="btn dark" href="/openapi.json">Open API schema</a></div></div>
        </div>
      </section>

      <section className="final"><div className="shell"><div className="finalBox"><h2>Give AI useful WordPress access without turning publishing into a blind write operation.</h2><p>Advertpreneur Connect keeps content, SEO, images, commerce and quality checks in one controlled bridge.</p><div className="actions"><a className="btn" href="https://github.com/haseebgb92/advertpreneur-connect">View source</a><a className="btn" href="/openapi.json">OpenAPI</a></div></div></div></section>

      <footer><div className="shell foot"><div>Advertpreneur Connect · v0.6.1 · Built by Advertpreneur</div><div className="footlinks"><a href="https://github.com/haseebgb92/advertpreneur-connect">GitHub</a><a href="/openapi.json">OpenAPI</a><a href="/api/health">Health</a></div></div></footer>
    </main>
  );
}
