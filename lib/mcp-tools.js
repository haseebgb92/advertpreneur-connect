import { z } from 'zod';
import { createHash } from 'node:crypto';
import { appId, normalizeSiteUrl, publicBaseUrl } from './config.js';
import { signedState } from './crypto.js';
import { getSite, listSites } from './db.js';
import { wpRequest } from './wordpress.js';

const asResult = (value) => ({
  content: [{ type: 'text', text: typeof value === 'string' ? value : JSON.stringify(value, null, 2) }],
  ...(value && typeof value === 'object' ? { structuredContent: value } : {}),
});

async function connectedSite(id) {
  const site = await getSite(id);
  if (!site) throw new Error(`Unknown site_id: ${id}`);
  return site;
}

export function registerTools(server) {
  server.registerTool('connect_site', {
    title: 'Connect WordPress site',
    description: 'Create a one-click WordPress Application Password approval URL. Never asks for the normal WordPress password.',
    inputSchema: z.object({ site_url: z.string().min(3) }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: true },
  }, async ({ site_url }) => {
    const site = normalizeSiteUrl(site_url);
    const state = signedState(site);
    const base = publicBaseUrl();
    const success = `${base}/api/connect/callback?state=${encodeURIComponent(state)}`;
    const reject = `${base}/api/connect/rejected`;
    const approve = new URL('/wp-admin/authorize-application.php', site);
    approve.searchParams.set('app_name', 'Advertpreneur Connect');
    approve.searchParams.set('app_id', appId());
    approve.searchParams.set('success_url', success);
    approve.searchParams.set('reject_url', reject);
    return asResult({ site_url: site, approval_url: approve.toString(), expires_in_minutes: 30 });
  });

  server.registerTool('list_sites', {
    title: 'List connected WordPress sites',
    description: 'List WordPress sites connected to Advertpreneur Connect.',
    inputSchema: z.object({}),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async () => asResult({ sites: await listSites() }));

  server.registerTool('site_status', {
    title: 'Check WordPress bridge status',
    description: 'Verify authentication and Advertpreneur Connect plugin capabilities.',
    inputSchema: z.object({ site_id: z.string() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ site_id }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/status')));

  server.registerTool('search_site_content', {
    title: 'Search existing site content',
    description: 'Search existing posts/pages before proposing or writing a new article. Use this to avoid repetitive topics and search-intent cannibalization.',
    inputSchema: z.object({ site_id: z.string(), query: z.string().min(1), type: z.enum(['post', 'page', 'product']).optional(), limit: z.number().int().min(1).max(50).optional() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ site_id, query, type, limit = 20 }) => {
    const typePart = type ? `&type=${encodeURIComponent(type)}` : '';
    return asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/content/search?q=${encodeURIComponent(query)}&limit=${limit}${typePart}`));
  });

  server.registerTool('get_content', {
    title: 'Read WordPress content',
    description: 'Read the source fields for an existing post, page or product before editing it.',
    inputSchema: z.object({ site_id: z.string(), content_id: z.number().int().positive() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ site_id, content_id }) => asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/content/${content_id}`)));

  server.registerTool('create_draft', {
    title: 'Create WordPress draft',
    description: 'Save content already written by the model/user as a WordPress draft. This tool never generates content.',
    inputSchema: z.object({
      site_id: z.string(), title: z.string().min(1), content: z.string().optional(), excerpt: z.string().optional(), slug: z.string().optional(), type: z.enum(['post', 'page']).optional(), category_ids: z.array(z.number().int()).optional(), tag_ids: z.array(z.number().int()).optional(), parent_id: z.number().int().optional(), menu_order: z.number().int().optional(), template: z.string().optional(),
    }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, ...payload }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/drafts', { method: 'POST', body: payload })));

  server.registerTool('update_post', {
    title: 'Update WordPress draft/content',
    description: 'Update a post or page. Any content change invalidates its earlier quality approval.',
    inputSchema: z.object({ site_id: z.string(), post_id: z.number().int(), title: z.string().optional(), content: z.string().optional(), excerpt: z.string().optional(), slug: z.string().optional(), category_ids: z.array(z.number().int()).optional(), tag_ids: z.array(z.number().int()).optional(), parent_id: z.number().int().optional(), menu_order: z.number().int().optional(), template: z.string().optional() }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, post_id, ...payload }) => asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/posts/${post_id}`, { method: 'PATCH', body: payload })));

  server.registerTool('quality_check', {
    title: 'Run deterministic content checks',
    description: 'Run deterministic checks for thin content, repeated paragraphs/headings, generic padding and other structural problems. This is not a substitute for factual source review.',
    inputSchema: z.object({ site_id: z.string(), post_id: z.number().int() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ site_id, post_id }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/quality/check', { method: 'POST', body: { post_id } })));

  server.registerTool('site_overlap_check', {
    title: 'Check local-site content overlap',
    description: 'Compare the current draft against existing posts/pages on the same WordPress site. Use before quality attestation and publication to reduce repetitive content and search-intent cannibalization.',
    inputSchema: z.object({ site_id: z.string(), post_id: z.number().int() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ site_id, post_id }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/quality/overlap', { method: 'POST', body: { post_id } })));

  server.registerTool('attest_quality', {
    title: 'Attest current content quality',
    description: 'Store a quality approval tied to the exact current content hash. Only call after site-overlap, repetition and potentially misleading factual claims have been checked.',
    inputSchema: z.object({
      site_id: z.string(), post_id: z.number().int(), content_sha256: z.string().optional(), site_overlap_checked: z.boolean(), repetition_checked: z.boolean(), misleading_claims_checked: z.boolean(), source_reviewed: z.boolean(), notes: z.string().optional(), sources: z.array(z.string()).optional(), passed: z.boolean(),
    }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, post_id, ...report }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/quality/attest', { method: 'POST', body: { post_id, report } })));

  server.registerTool('publish_post', {
    title: 'Publish quality-approved post',
    description: 'Publish a post immediately. WordPress blocks this unless the exact current body has a passing quality attestation.',
    inputSchema: z.object({ site_id: z.string(), post_id: z.number().int() }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, post_id }) => asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/posts/${post_id}/publish`, { method: 'POST', body: {} })));

  server.registerTool('upload_image_base64', {
    title: 'Upload image bytes to WordPress',
    description: 'Upload a JPG/PNG/WebP/GIF supplied as base64 bytes into WordPress Media Library. Returns a real media_id and hosted URL. Use only when the client actually has the image bytes.',
    inputSchema: z.object({ site_id: z.string(), data: z.string().min(16), filename: z.string().optional(), alt_text: z.string().optional(), title: z.string().optional(), post_id: z.number().int().optional() }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, ...payload }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/media/base64', { method: 'POST', body: payload })));

  server.registerTool('import_image_url', {
    title: 'Import HTTPS image to WordPress',
    description: 'Download a public HTTPS image and store a local copy in WordPress Media Library. Confirm relevance and usage rights before importing internet images.',
    inputSchema: z.object({ site_id: z.string(), url: z.string().url(), filename: z.string().optional(), alt_text: z.string().optional(), title: z.string().optional(), post_id: z.number().int().optional() }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: true },
  }, async ({ site_id, ...payload }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/media/import', { method: 'POST', body: payload })));

  server.registerTool('set_featured_image', {
    title: 'Set WordPress featured image',
    description: 'Set an existing WordPress media attachment as a post featured image.',
    inputSchema: z.object({ site_id: z.string(), post_id: z.number().int(), media_id: z.number().int() }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, post_id, media_id }) => asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/posts/${post_id}/featured`, { method: 'POST', body: { media_id } })));

  server.registerTool('list_products', {
    title: 'List WooCommerce products',
    description: 'List or search WooCommerce products on a connected site.',
    inputSchema: z.object({ site_id: z.string(), search: z.string().optional(), status: z.string().optional(), limit: z.number().int().min(1).max(50).optional(), page: z.number().int().min(1).optional() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ site_id, search, status, limit = 20, page = 1 }) => {
    const q = new URLSearchParams();
    if (search) q.set('search', search);
    if (status) q.set('status', status);
    q.set('limit', String(limit));
    q.set('page', String(page));
    return asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/products?${q.toString()}`));
  });

  server.registerTool('get_product', {
    title: 'Read WooCommerce product',
    description: 'Read a WooCommerce product including descriptions, price, stock, categories, tags and images.',
    inputSchema: z.object({ site_id: z.string(), product_id: z.number().int().positive() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ site_id, product_id }) => asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/products/${product_id}`)));

  const productFields = {
    name: z.string().optional(),
    type: z.enum(['simple', 'variable']).optional(),
    slug: z.string().optional(),
    description: z.string().optional(),
    short_description: z.string().optional(),
    sku: z.string().optional(),
    regular_price: z.string().optional(),
    sale_price: z.string().optional(),
    manage_stock: z.boolean().optional(),
    stock_quantity: z.number().int().optional(),
    stock_status: z.enum(['instock', 'outofstock', 'onbackorder']).optional(),
    catalog_visibility: z.enum(['visible', 'catalog', 'search', 'hidden']).optional(),
    featured: z.boolean().optional(),
    virtual: z.boolean().optional(),
    category_ids: z.array(z.number().int()).optional(),
    tag_ids: z.array(z.number().int()).optional(),
    image_id: z.number().int().optional(),
    gallery_image_ids: z.array(z.number().int()).optional(),
  };

  server.registerTool('create_product_draft', {
    title: 'Create WooCommerce product draft',
    description: 'Create a WooCommerce product in draft status. Publishing still requires the quality gate.',
    inputSchema: z.object({ site_id: z.string(), ...productFields, name: z.string().min(1) }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, ...payload }) => asResult(await wpRequest(await connectedSite(site_id), '/wp-json/advertpreneur-connect/v1/products', { method: 'POST', body: payload })));

  server.registerTool('update_product', {
    title: 'Update WooCommerce product',
    description: 'Update WooCommerce product content, merchandising fields, stock, categories, tags or images.',
    inputSchema: z.object({ site_id: z.string(), product_id: z.number().int().positive(), ...productFields }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, product_id, ...payload }) => asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/products/${product_id}`, { method: 'PATCH', body: payload })));

  server.registerTool('publish_product', {
    title: 'Publish quality-approved WooCommerce product',
    description: 'Publish a WooCommerce product only after its current product copy passes quality checks and has a current quality attestation.',
    inputSchema: z.object({ site_id: z.string(), product_id: z.number().int().positive() }),
    annotations: { readOnlyHint: false, destructiveHint: false, openWorldHint: false },
  }, async ({ site_id, product_id }) => asResult(await wpRequest(await connectedSite(site_id), `/wp-json/advertpreneur-connect/v1/products/${product_id}/publish`, { method: 'POST', body: {} })));

  server.registerTool('hash_content', {
    title: 'Hash content for quality verification',
    description: 'Compute SHA-256 for supplied normalized content when a caller needs a local comparison. Does not access WordPress.',
    inputSchema: z.object({ content: z.string() }),
    annotations: { readOnlyHint: true, openWorldHint: false },
  }, async ({ content }) => asResult({ sha256: createHash('sha256').update(content.trim().replace(/\s+/g, ' ')).digest('hex') }));
}
