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

## Exact setup examples by agent

These examples configure **the WordPress MCP transport**. Installation of the Agensi skill is a separate operation. The examples do not pass secrets on a command line. Complete the OAuth sign-in using your chosen agent/client and verify the tool list afterward.

### Codex CLI (configuration + OAuth setup tested on v0.162.1)

```bash
codex mcp add advertpreneur-connect --url https://advertpreneur-connect.vercel.app/api/mcp
codex mcp login advertpreneur-connect
codex mcp list
```

Note: An isolated Codex profile completed MCP OAuth configuration, but actual Codex agent execution was blocked by **model authentication HTTP 401** in that isolated profile. This is not a WordPress connector success certificate; authenticate Codex itself before running the live test.

### Claude Code (documented configuration; live integration untested)

```bash
claude mcp add --transport http --scope user advertpreneur-connect https://advertpreneur-connect.vercel.app/api/mcp
claude mcp list
```

Inside Claude Code use `/mcp` to complete authentication and verify tools. Confirm the companion plugin is installed on your WordPress site. See the Claude Code official MCP documentation for version-specific details.

### Cursor (documented configuration; live integration untested)

Place the following in your Cursor MCP settings (for example `~/.cursor/mcp.json` on Linux/macOS) using the supported Cursor version:

```json
{
  "mcpServers": {
    "advertpreneur-connect": {
      "url": "https://advertpreneur-connect.vercel.app/api/mcp"
    }
  }
}
```

Complete authorization inside Cursor, refresh tools and run a read-only `list_sites` call before writing.

### GitHub Copilot / VS Code Agent Mode (documented configuration; live integration untested)

Create a project-local `.vscode/mcp.json` when applicable:

```json
{
  "servers": {
    "advertpreneur-connect": {
      "type": "http",
      "url": "https://advertpreneur-connect.vercel.app/api/mcp"
    }
  }
}
```

Enable the server in Agent Mode and finish OAuth. Do not commit local tokens or reuse someone else's site access.

### ChatGPT

Connect the **Advertpreneur Connect** plugin/MCP connection through ChatGPT's supported Plugins/Work configuration and complete its OAuth flow. The installed connection has successfully executed read-only WordPress site listing, status, content search and SEO detection. Loading Agensi's skill is a separate Agensi-connection process; do not assume every ChatGPT interface or plan exposes both integrations.

### Gemini CLI, OpenClaw, Windsurf and other Agensi-listed agents

Install the Agensi skill using each supported client’s documented skill mechanism, then separately configure authenticated remote HTTP MCP. Do not guess configuration filenames, enable static shared-bearer access, or advertise a successful end-to-end test until that agent has demonstrably performed the read-only preflight and staging draft workflow.

**Every agent must pass the same acceptance sequence:** OAuth login → list only authorized sites → site status → existing content search → explicit test draft on staging → SEO read-back → quality/overlap → user-approved publish on staging → read-back. Do not run publishing tests on a customer website.

### Source references

- OpenAI Docs MCP agent configuration examples: https://developers.openai.com/learn/docs-mcp
- Agensi cross-agent SKILL.md testing and caveats: https://www.agensi.io/learn/skill-md-cross-agent-compatibility-tested
