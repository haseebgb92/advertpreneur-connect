# Install and connection

## Agensi delivery
Install or load the `advertpreneur-connect` skill through Agensi in a supported AI agent. A skill being discovered does NOT mean the WordPress bridge is connected.

## Companion backend
Public service documentation: https://advertpreneur-connect.vercel.app/setup
Service MCP endpoint: https://advertpreneur-connect.vercel.app/api/mcp
WordPress companion plugin is required on the target site. Users should connect through WordPress Application Password approval, not share their WordPress login or password.

### Client compatibility: requirements, not a certification
- **ChatGPT**: ChatGPT must have a functional connection to Advertpreneur Connect as an MCP/plugin tool and a method for loading Agensi-delivered instructions. The standard ChatGPT web chat alone does not imply local SKILL.md support.
- **Codex CLI**: Can load SKILL.md; remote MCP must separately be configured/authenticated in its supported client settings.
- **Claude Code**: Can load SKILL.md; remote MCP must separately be configured/authenticated.
- **Cursor**: Supports agent skills in supported versions; MCP configuration and site authentication are separate.
- **GitHub Copilot, Gemini CLI, Windsurf, OpenClaw, Cline, Aider, Zed, Kiro, Manus and others**: instruction delivery depends on supported version/integration; live WordPress actions require each client's working MCP tool access.
- Do not mark an agent "fully tested" until login, tool discovery, draft creation, review and approval-gated publishing have been verified.

### Required preflight
1. User signs in to the bridge using their own permitted authentication method.
2. Discover `list_sites`; check that it shows only sites authorized to the signed-in identity.
3. Select a site, then `site_status` must confirm a compatible companion plugin.
4. Run a *read-only* content search and `seo_status`.
5. If permitted, create an explicitly labelled test draft (never a live post); inspect it in wp-admin.
6. Do not publish a test article to a real site for compatibility testing.

### Troubleshooting
- No MCP tools: check the client's MCP server configuration and OAuth connection.
- 401: sign in using the client, do not paste secrets into the chat.
- Zero sites: connect a site with its authenticated WordPress Application Password approval flow.
- 403: WordPress account lacks permissions or the tenant/site association is not allowed. Do not bypass.
- SEO plugin conflict: pause SEO writes, identify the actual active plugin.
- No usable image: leave draft without image and report.
- Failing quality or stale attestation: revise, recheck, and reattest.
- Publishing fails: preserve the draft; re-read status and report error.
- For other HTTP errors, do not repeat non-idempotent writes blindly.

## Publishing approval enforcement
The WordPress server enforces a content-quality attestation, but not an independent human-approval nonce. The SKILL.md requires the agent to obtain explicit consent; agent hosts should configure high-risk write actions to require a human approval. Do not advertise a server-side human-approval guarantee.
