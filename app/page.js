export default function Home() {
  return (
    <main style={{fontFamily:'system-ui,-apple-system,sans-serif',maxWidth:820,margin:'72px auto',padding:'0 24px',lineHeight:1.55}}>
      <h1>Advertpreneur Connect</h1>
      <p>Secure WordPress bridge for ChatGPT and other trusted MCP clients.</p>
      <p>The bridge does not generate content. It exposes controlled WordPress actions, direct media upload paths, site-aware content search, and a quality-gated publishing flow.</p>
      <p><strong>MCP:</strong> <code>/api/mcp</code></p>
      <p><strong>Health:</strong> <code>/api/health</code></p>
    </main>
  );
}
