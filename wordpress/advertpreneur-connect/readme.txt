=== Advertpreneur Connect ===
Contributors: advertpreneur
Tags: rest-api, ai, mcp, content, media
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.7.0
License: GPLv2 or later

A secure bridge between WordPress and trusted AI/MCP clients. The plugin itself does not generate content.

== Features ==
* One-click connection from WordPress admin using native Application Password authorization
* Readiness/status endpoint
* Site-content search
* Draft creation and controlled edits
* Base64 image upload to Media Library
* HTTPS image import to Media Library
* Native WordPress /wp/v2/media multipart remains available
* Slot-aware image requirements from the active theme/block/product context
* Image fit, resolution, and near-blank validation before placement
* Featured image assignment
* Detects AIOSEO, Rank Math, Yoast SEO, and SEOPress
* Generic SEO read/write for meta title, meta description, focus keyword, and canonical URL
* Deterministic SEO preflight for focus-keyword placement, URL, headings, image alt, links, and TOC
* Media metadata update for alt text, title, caption, and description
* Category/tag discovery for correct taxonomy assignment
* Internal-link candidate discovery from real published site content
* Structural quality checks
* Quality attestation bound to a SHA-256 content hash
* Publish endpoint refuses stale/unattested content
* Featured-image assignment preserves a valid content attestation because image placement does not change the attested article body
* Publish endpoint verifies the persisted WordPress status and retries once before reporting success

== Security ==
Use HTTPS and WordPress Application Passwords. Do not expose normal WordPress passwords to external applications.
