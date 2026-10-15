# Advertpreneur Connect — tool map and acceptance conditions

**Source:** existing `haseebgb92/advertpreneur-connect` MCP implementation reviewed October 15, 2026. Discovery in the target client is authoritative: names may be namespaced and deployments may differ.

| Purpose | MCP tool | Expected proof |
|---|---|---|
| Connect | `connect_site` | Returns a WordPress approval URL; no password exposed |
| List allowed sites | `list_sites` | Only current user's allowed sites |
| Preflight | `site_status` | Authenticated plugin status and capabilities |
| Avoid duplicates | `search_site_content` | Real matches from selected site |
| Existing item | `get_content` | Matching ID, current status and body |
| SEO plugin | `seo_status` | Exactly one supported plugin before SEO writes |
| Read SEO | `get_seo` | Site-backed SEO metadata |
| Write SEO | `update_seo` | Acknowledgement + read-back |
| Terms | `list_terms` | Real category/tag IDs |
| Links | `suggest_internal_links` | Only published internal links |
| SEO audit | `seo_audit` | Result with actual findings |
| Draft | `create_draft` | Real ID + draft status |
| Edit | `update_post` | Correct existing ID + read-back |
| Layout | `get_image_requirements` | Site/slot-specific dimensions or probe required |
| Image bytes | `upload_image_base64` | WordPress-created media ID; ≤4,000,000 base64 chars (~3 MB source) on Vercel |
| HTTPS image | `import_image_url` | Authorized licensed image + media ID |
| Featured image | `set_featured_image` | Actual attachment persisted |
| Quality | `quality_check` | Passing structure/repetition checks |
| Overlap | `site_overlap_check` | Existing-site similarity evaluated |
| Attestation | `attest_quality` | Current passing attestation; no fake evidence |
| Publish | `publish_post` | Explicit human approval via agent UI required; WordPress server enforces content quality, not a separate human-approval nonce; published status verified |
| Woo catalog | `list_products`, `get_product`, `create_product_draft`, `update_product`, `publish_product` | WooCommerce capability + current approval |

MCP is one possible transport. A separate Action API also exists but uses a privileged server token; the Agensi skill must NOT request or distribute that global token.

## Test matrix
Read-only test, isolated draft test, SEO test, media test, quality-gated publish *on a controlled staging site only*, and cross-account site isolation are independently required before marking an agent production compatible.

Known source limitation: the 2026-10-15 public backend branch did not yet enforce tenant ownership. Do not offer the service to arbitrary public subscribers until tenant isolation and full integration tests are proven.
