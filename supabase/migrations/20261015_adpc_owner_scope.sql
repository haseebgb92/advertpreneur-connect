-- Safe additive migration for Advertpreneur Connect v0.7.0.
-- Keep existing private Action API sites under legacy owner.
-- Apply BEFORE deploying the new private storage Edge Function or backend.
alter table public.adpc_connected_sites
  add column if not exists owner_id text not null default 'legacy';

create index if not exists adpc_connected_sites_owner_id_idx
  on public.adpc_connected_sites (owner_id, connected_at desc);

-- Existing RLS remains enabled, no user-facing grants are added.
-- Do not expose the service role key or private credential columns.
