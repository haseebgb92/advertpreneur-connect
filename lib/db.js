import postgres from 'postgres';
import { openSealed, seal } from './crypto.js';

let sqlClient;
let schemaReady;

function sql() {
  const url = process.env.DATABASE_URL || process.env.POSTGRES_URL;
  if (!url) throw new Error('DATABASE_URL is required for persistent site connections');
  if (!sqlClient) sqlClient = postgres(url, { max: 2, idle_timeout: 20, connect_timeout: 15, ssl: 'require' });
  return sqlClient;
}

async function ensureSchema() {
  if (!schemaReady) {
    schemaReady = (async () => {
      const db = sql();
      await db`
        create table if not exists adpc_connected_sites (
          id text primary key,
          site_url text not null unique,
          user_login text not null,
          credential_enc text not null,
          connected_at timestamptz not null default now(),
          updated_at timestamptz not null default now()
        )
      `;
      await db`create index if not exists adpc_connected_sites_url_idx on adpc_connected_sites (site_url)`;
    })();
  }
  return schemaReady;
}

export async function upsertSite({ id, siteUrl, userLogin, password }) {
  await ensureSchema();
  const db = sql();
  const [row] = await db`
    insert into adpc_connected_sites (id, site_url, user_login, credential_enc)
    values (${id}, ${siteUrl.replace(/\/$/, '')}, ${userLogin}, ${seal(password)})
    on conflict (id) do update set
      site_url = excluded.site_url,
      user_login = excluded.user_login,
      credential_enc = excluded.credential_enc,
      updated_at = now()
    returning id, site_url, user_login, connected_at, updated_at
  `;
  return row;
}

export async function listSites() {
  await ensureSchema();
  const db = sql();
  return db`select id, site_url, user_login, connected_at, updated_at from adpc_connected_sites order by connected_at desc`;
}

export async function getSite(id) {
  await ensureSchema();
  const db = sql();
  const [row] = await db`select id, site_url, user_login, credential_enc, connected_at from adpc_connected_sites where id = ${id}`;
  if (!row) return null;
  return {
    id: row.id,
    siteUrl: row.site_url,
    userLogin: row.user_login,
    password: openSealed(row.credential_enc),
    connectedAt: row.connected_at,
  };
}
