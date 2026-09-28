export default function Home() {
  return (
    <main style={{fontFamily:'system-ui,-apple-system,sans-serif',maxWidth:820,margin:'72px auto',padding:'0 24px',lineHeight:1.55}}>
      <h1>Advertpreneur Connect</h1>
      <p>Secure WordPress bridge for trusted AI clients.</p>
      <p>The bridge does not generate content. It exposes controlled WordPress actions for posts, pages, media and WooCommerce products, with site-aware search and a quality-gated publishing flow.</p>
      <p><strong>Custom GPT Actions schema:</strong> <code>/openapi.json</code></p>
      <p><strong>Action API:</strong> <code>/api/action/*</code></p>
      <p><strong>MCP:</strong> <code>/api/mcp</code> (disabled unless separately authenticated)</p>
      <p><strong>Health:</strong> <code>/api/health</code></p>
    </main>
  );
}
