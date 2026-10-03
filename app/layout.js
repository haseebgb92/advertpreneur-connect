export const metadata = {
  title: {
    default: 'Advertpreneur Connect — AI to WordPress Bridge',
    template: '%s · Advertpreneur Connect',
  },
  description: 'A controlled AI-to-WordPress bridge for content, SEO, media and WooCommerce with site-aware checks and quality-gated publishing.',
  keywords: ['WordPress AI', 'WordPress MCP', 'WooCommerce AI', 'AI publishing', 'WordPress automation', 'MCP server', 'SEO automation'],
  openGraph: {
    title: 'Advertpreneur Connect — A safer bridge between AI and WordPress',
    description: 'Controlled WordPress content, SEO, media and WooCommerce tools with quality-gated publishing.',
    type: 'website',
    url: 'https://advertpreneur-connect.vercel.app',
  },
  alternates: { canonical: 'https://advertpreneur-connect.vercel.app' },
};

export default function RootLayout({ children }) {
  return <html lang="en"><body>{children}</body></html>;
}
