'use client';

import { useEffect, useMemo, useState } from 'react';
import { createClient } from '@supabase/supabase-js';

const SUPABASE_URL = 'https://hwhioqvsxazyxziyrhdg.supabase.co';
const SUPABASE_PUBLISHABLE_KEY = 'sb_publishable_nukcv3u9TU4TGx24cilYIQ_wYJAQqjv';

export default function OAuthConsentPage() {
  const supabase = useMemo(() => createClient(SUPABASE_URL, SUPABASE_PUBLISHABLE_KEY), []);
  const [authorizationId, setAuthorizationId] = useState('');
  const [session, setSession] = useState(null);
  const [details, setDetails] = useState(null);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [message, setMessage] = useState('Loading…');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    const id = new URLSearchParams(window.location.search).get('authorization_id') || '';
    setAuthorizationId(id);

    supabase.auth.getSession().then(({ data }) => setSession(data.session || null));
    const { data: listener } = supabase.auth.onAuthStateChange((_event, nextSession) => {
      setSession(nextSession || null);
    });
    return () => listener.subscription.unsubscribe();
  }, [supabase]);

  useEffect(() => {
    if (!authorizationId) {
      setMessage('Missing authorization request.');
      return;
    }
    if (!session) {
      setMessage('Sign in to approve ChatGPT access.');
      return;
    }

    let cancelled = false;
    (async () => {
      setMessage('Loading authorization request…');
      const { data, error } = await supabase.auth.oauth.getAuthorizationDetails(authorizationId);
      if (cancelled) return;
      if (error) {
        setMessage(error.message);
        return;
      }
      setDetails(data);
      setMessage('');
    })();

    return () => { cancelled = true; };
  }, [authorizationId, session, supabase]);

  async function signIn(event) {
    event.preventDefault();
    setBusy(true);
    setMessage('');
    const { error } = await supabase.auth.signInWithPassword({ email, password });
    setBusy(false);
    if (error) setMessage(error.message);
  }

  async function signUp() {
    setBusy(true);
    setMessage('');
    const { data, error } = await supabase.auth.signUp({ email, password });
    setBusy(false);
    if (error) return setMessage(error.message);
    if (!data.session) setMessage('Account created. Check your email to confirm it, then return here and sign in.');
  }

  async function decide(approve) {
    setBusy(true);
    setMessage('');
    const fn = approve
      ? supabase.auth.oauth.approveAuthorization.bind(supabase.auth.oauth)
      : supabase.auth.oauth.denyAuthorization.bind(supabase.auth.oauth);
    const { data, error } = await fn(authorizationId);
    setBusy(false);
    if (error) return setMessage(error.message);
    if (data?.redirect_url) window.location.assign(data.redirect_url);
    else setMessage('Supabase did not return a redirect URL.');
  }

  const scopes = typeof details?.scope === 'string' ? details.scope.split(/\s+/).filter(Boolean) : [];

  return (
    <main style={{fontFamily:'system-ui,-apple-system,sans-serif',maxWidth:560,margin:'70px auto',padding:'0 22px',lineHeight:1.5,color:'#171717'}}>
      <div style={{border:'1px solid #e5e5e5',borderRadius:18,padding:28,boxShadow:'0 8px 30px rgba(0,0,0,.06)'}}>
        <div style={{fontSize:13,fontWeight:700,letterSpacing:'.08em',textTransform:'uppercase',color:'#666'}}>Advertpreneur Connect</div>
        <h1 style={{fontSize:30,margin:'8px 0 8px'}}>Connect ChatGPT</h1>
        <p style={{color:'#555',marginTop:0}}>Authorize ChatGPT to use the WordPress sites you connected to Advertpreneur Connect.</p>

        {!session ? (
          <form onSubmit={signIn} style={{display:'grid',gap:12,marginTop:24}}>
            <label>Email<input required type="email" value={email} onChange={e=>setEmail(e.target.value)} style={{display:'block',width:'100%',boxSizing:'border-box',marginTop:6,padding:12,border:'1px solid #ccc',borderRadius:10}} /></label>
            <label>Password<input required minLength={8} type="password" value={password} onChange={e=>setPassword(e.target.value)} style={{display:'block',width:'100%',boxSizing:'border-box',marginTop:6,padding:12,border:'1px solid #ccc',borderRadius:10}} /></label>
            <button disabled={busy} type="submit" style={{padding:12,border:0,borderRadius:10,background:'#171717',color:'#fff',fontWeight:700,cursor:'pointer'}}>Sign in</button>
            <button disabled={busy} type="button" onClick={signUp} style={{padding:12,border:'1px solid #ccc',borderRadius:10,background:'#fff',fontWeight:700,cursor:'pointer'}}>Create account</button>
          </form>
        ) : details ? (
          <div style={{marginTop:24}}>
            <div style={{padding:16,borderRadius:12,background:'#f7f7f7'}}>
              <div><strong>Client:</strong> {details.client?.name || details.client_name || 'ChatGPT'}</div>
              <div style={{marginTop:6}}><strong>Signed in:</strong> {session.user?.email || 'Authenticated user'}</div>
              <div style={{marginTop:6}}><strong>Requested access:</strong> {scopes.length ? scopes.join(', ') : 'email'}</div>
            </div>
            <p style={{color:'#555'}}>Approval lets this MCP client invoke the WordPress tools exposed by Advertpreneur Connect. Publishing remains protected by the connector's quality gate.</p>
            <div style={{display:'flex',gap:10}}>
              <button disabled={busy} onClick={()=>decide(true)} style={{flex:1,padding:12,border:0,borderRadius:10,background:'#171717',color:'#fff',fontWeight:700,cursor:'pointer'}}>Approve</button>
              <button disabled={busy} onClick={()=>decide(false)} style={{flex:1,padding:12,border:'1px solid #ccc',borderRadius:10,background:'#fff',fontWeight:700,cursor:'pointer'}}>Deny</button>
            </div>
            <button onClick={()=>supabase.auth.signOut()} style={{marginTop:16,border:0,background:'transparent',padding:0,textDecoration:'underline',cursor:'pointer'}}>Sign out</button>
          </div>
        ) : null}

        {message ? <p style={{marginTop:18,color:'#8a3b12'}}>{message}</p> : null}
      </div>
    </main>
  );
}
