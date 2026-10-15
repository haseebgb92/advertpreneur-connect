# Advertpreneur Connect — agent compatibility evidence

Last checked: 2026-10-15. **Instruction portability is distinct from authenticated live WordPress access.** Buyers need both an Agensi skill capable agent and Advertpreneur Connect MCP configured through OAuth. Static private server tokens must never be supplied in the marketplace package.

| Agent | SKILL.md portability | Authenticated Advertpreneur Connect tool execution | Classification |
|---|---|---|---|
| ChatGPT (connected plugin) | Agensi skill delivery depends on installed Agensi connector and compatible surface | `list_sites`, `site_status`, `search_site_content`, `seo_status` passed against production | Read-only execution verified; skill delivery and writes per-client unverified |
| Codex CLI 0.162.1 | Supported format | Remote MCP added, OAuth sign-in successful; agent invocation blocked by separate model API HTTP 401 | MCP login verified; agent execution pending |
| Claude Code | Standard agent skill instructions supported | Not exercised | Configuration documented, unverified |
| Cursor | Standard agent skill instructions supported | Not exercised | Configuration documented, unverified |
| GitHub Copilot Agent Mode | Standard agent skill instructions supported | Not exercised | Configuration documented, unverified |
| Gemini CLI | Standard agent skill instructions supported | Not exercised | Unverified |
| Windsurf / OpenClaw / remaining Agensi agents | Varies by supported version | Not exercised | Unverified |

**Scope tested:** Next.js backend 0.7.0 production; real WordPress 7.1.2 staging with plugin 0.7.0 tested content, media and WooCommerce, but these tests were executed through staging REST fixtures, not from every named agent. GitHub Actions and build tests are separate. The two existing production sites still run plugin 0.6.3 pending controlled site-by-site upgrades.

**Known limitations:** Users must authorize each WordPress site. Product information, WordPress publishing permissions and AI-client confirmation screens are not provided by Agensi automatically. The plugin cannot guarantee factual accuracy, search rankings or originality. The standard text-only skill does not generate images or acquire image usage rights.

**Seller claim allowed now:** “Portable SKILL.md workflow, with an OAuth-enabled WordPress MCP server; ChatGPT connected read-only operations confirmed. Codex MCP setup and OAuth confirmed. Additional agent integrations require individual verification.”

**Seller claims NOT yet allowed:** “Fully tested with all Agensi agents,” “zero-config publishing on ChatGPT,” “all customer plugins upgraded,” “independent second-account OAuth isolation tested end-to-end,” or “Agensi approved.”
