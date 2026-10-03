const siteId = { name: 'site_id', in: 'path', required: true, schema: { type: 'string' }, description: 'Connected site ID returned by listSites.' };
const contentId = { name: 'content_id', in: 'path', required: true, schema: { type: 'integer', minimum: 1 } };
const productId = { name: 'product_id', in: 'path', required: true, schema: { type: 'integer', minimum: 1 } };

const htmlContentFields = {
  title: { type: 'string' },
  slug: { type: 'string' },
  content: { type: 'string', description: 'Complete WordPress-safe HTML body.' },
  excerpt: { type: 'string' },
  category_ids: { type: 'array', items: { type: 'integer' } },
  tag_ids: { type: 'array', items: { type: 'integer' } },
  parent_id: { type: 'integer' },
  menu_order: { type: 'integer' },
  template: { type: 'string' },
};

const productFields = {
  name: { type: 'string' },
  type: { type: 'string', enum: ['simple', 'variable'] },
  slug: { type: 'string' },
  description: { type: 'string', description: 'Full product description as WordPress-safe HTML.' },
  short_description: { type: 'string', description: 'Short product description as WordPress-safe HTML.' },
  sku: { type: 'string' },
  regular_price: { type: 'string' },
  sale_price: { type: 'string' },
  manage_stock: { type: 'boolean' },
  stock_quantity: { type: 'integer' },
  stock_status: { type: 'string', enum: ['instock', 'outofstock', 'onbackorder'] },
  catalog_visibility: { type: 'string', enum: ['visible', 'catalog', 'search', 'hidden'] },
  featured: { type: 'boolean' },
  virtual: { type: 'boolean' },
  category_ids: { type: 'array', items: { type: 'integer' } },
  tag_ids: { type: 'array', items: { type: 'integer' } },
  image_id: { type: 'integer' },
  gallery_image_ids: { type: 'array', items: { type: 'integer' } },
};

const jsonBody = (schema) => ({ required: true, content: { 'application/json': { schema } } });

export function openApiDocument(baseUrl = 'https://advertpreneur-connect.vercel.app') {
  return {
    openapi: '3.1.0',
    info: {
      title: 'Advertpreneur Connect',
      version: '0.6.1',
      description: 'Controlled WordPress actions for posts, pages, SEO, media, internal linking, quality-gated publishing, and WooCommerce products. The API never generates content itself.',
    },
    servers: [{ url: baseUrl }],
    security: [{ bearerAuth: [] }],
    paths: {
      '/api/action/sites': {
        get: { operationId: 'listSites', summary: 'List connected WordPress sites', responses: { '200': { description: 'Connected sites' } } },
      },
      '/api/action/sites/{site_id}/status': {
        get: { operationId: 'getSiteStatus', summary: 'Check site/plugin capabilities', parameters: [siteId], responses: { '200': { description: 'Site status' } } },
      },
      '/api/action/sites/{site_id}/search': {
        get: {
          operationId: 'searchSiteContent', summary: 'Search existing posts, pages and products before creating new content', parameters: [siteId,
            { name: 'q', in: 'query', required: true, schema: { type: 'string' } },
            { name: 'type', in: 'query', required: false, schema: { type: 'string', enum: ['post', 'page', 'product'] } },
            { name: 'limit', in: 'query', required: false, schema: { type: 'integer', minimum: 1, maximum: 50 } },
          ], responses: { '200': { description: 'Matching site content' } },
        },
      },
      '/api/action/sites/{site_id}/content/{content_id}': {
        get: { operationId: 'getContent', summary: 'Read a post, page or product source content', parameters: [siteId, contentId], responses: { '200': { description: 'Content details' } } },
        patch: { operationId: 'updateContent', summary: 'Update an existing post or page', parameters: [siteId, contentId], requestBody: jsonBody({ type: 'object', properties: htmlContentFields }), responses: { '200': { description: 'Updated content' } } },
      },
      '/api/action/sites/{site_id}/posts': {
        post: { operationId: 'createPostDraft', summary: 'Create a blog post draft', parameters: [siteId], requestBody: jsonBody({ type: 'object', required: ['title'], properties: htmlContentFields }), responses: { '201': { description: 'Draft created' } } },
      },
      '/api/action/sites/{site_id}/pages': {
        post: { operationId: 'createPageDraft', summary: 'Create a WordPress page draft', parameters: [siteId], requestBody: jsonBody({ type: 'object', required: ['title'], properties: htmlContentFields }), responses: { '201': { description: 'Page draft created' } } },
      },
      '/api/action/sites/{site_id}/quality/check': {
        post: { operationId: 'runQualityCheck', summary: 'Run deterministic thin/repetition/overlap checks', parameters: [siteId], requestBody: jsonBody({ type: 'object', required: ['post_id'], properties: { post_id: { type: 'integer' } } }), responses: { '200': { description: 'Quality report' } } },
      },
      '/api/action/sites/{site_id}/quality/overlap': {
        post: { operationId: 'checkSiteOverlap', summary: 'Check local-site duplication and cannibalization signals', parameters: [siteId], requestBody: jsonBody({ type: 'object', required: ['post_id'], properties: { post_id: { type: 'integer' } } }), responses: { '200': { description: 'Overlap report' } } },
      },
      '/api/action/sites/{site_id}/quality/attest': {
        post: {
          operationId: 'attestQuality', summary: 'Store quality approval for the exact current content', parameters: [siteId],
          requestBody: jsonBody({ type: 'object', required: ['post_id', 'report'], properties: { post_id: { type: 'integer' }, report: { type: 'object', required: ['passed', 'site_overlap_checked', 'repetition_checked', 'misleading_claims_checked', 'source_reviewed'], properties: {
            passed: { type: 'boolean' }, site_overlap_checked: { type: 'boolean' }, repetition_checked: { type: 'boolean' }, misleading_claims_checked: { type: 'boolean' }, source_reviewed: { type: 'boolean' }, content_sha256: { type: 'string' }, notes: { type: 'string' }, sources: { type: 'array', items: { type: 'string' } },
          } } } }),
          responses: { '200': { description: 'Quality attestation stored' } },
        },
      },
      '/api/action/sites/{site_id}/content/{content_id}/publish': {
        post: { operationId: 'publishContent', summary: 'Publish a quality-approved post or page', parameters: [siteId, contentId], responses: { '200': { description: 'Published' } } },
      },
      '/api/action/sites/{site_id}/content/{content_id}/featured': {
        post: { operationId: 'setFeaturedImage', summary: 'Set featured image for post, page or product', parameters: [siteId, contentId], requestBody: jsonBody({ type: 'object', required: ['media_id'], properties: { media_id: { type: 'integer' } } }), responses: { '200': { description: 'Featured image set' } } },
      },
      '/api/action/sites/{site_id}/media/import': {
        post: { operationId: 'importImageUrl', summary: 'Import a public HTTPS image into WordPress Media Library', parameters: [siteId], requestBody: jsonBody({ type: 'object', required: ['url'], properties: { url: { type: 'string', format: 'uri' }, filename: { type: 'string' }, alt_text: { type: 'string' }, title: { type: 'string' }, post_id: { type: 'integer' } } }), responses: { '201': { description: 'Media created' } } },
      },
      '/api/action/sites/{site_id}/media/base64': {
        post: { operationId: 'uploadImageBase64', summary: 'Upload image bytes as base64 into WordPress Media Library', parameters: [siteId], requestBody: jsonBody({ type: 'object', required: ['data'], properties: { data: { type: 'string', description: 'Base64 image data. Use only when actual image bytes are available.' }, filename: { type: 'string' }, alt_text: { type: 'string' }, title: { type: 'string' }, post_id: { type: 'integer' } } }), responses: { '201': { description: 'Media created' } } },
      },
      '/api/action/sites/{site_id}/products': {
        get: { operationId: 'listProducts', summary: 'List/search WooCommerce products', parameters: [siteId,
          { name: 'search', in: 'query', schema: { type: 'string' } }, { name: 'status', in: 'query', schema: { type: 'string' } }, { name: 'limit', in: 'query', schema: { type: 'integer', minimum: 1, maximum: 50 } }, { name: 'page', in: 'query', schema: { type: 'integer', minimum: 1 } },
        ], responses: { '200': { description: 'Products' } } },
        post: { operationId: 'createProductDraft', summary: 'Create a WooCommerce product draft', parameters: [siteId], requestBody: jsonBody({ type: 'object', required: ['name'], properties: productFields }), responses: { '201': { description: 'Product draft created' } } },
      },
      '/api/action/sites/{site_id}/products/{product_id}': {
        get: { operationId: 'getProduct', summary: 'Read a WooCommerce product', parameters: [siteId, productId], responses: { '200': { description: 'Product' } } },
        patch: { operationId: 'updateProduct', summary: 'Update a WooCommerce product', parameters: [siteId, productId], requestBody: jsonBody({ type: 'object', properties: productFields }), responses: { '200': { description: 'Updated product' } } },
      },
      '/api/action/sites/{site_id}/products/{product_id}/publish': {
        post: { operationId: 'publishProduct', summary: 'Publish a quality-approved WooCommerce product', parameters: [siteId, productId], responses: { '200': { description: 'Published product' } } },
      },
    },
    components: {
      securitySchemes: {
        bearerAuth: { type: 'http', scheme: 'bearer', bearerFormat: 'API key', description: 'Use the ADPC_ACTION_TOKEN value.' },
      },
    },
  };
}
