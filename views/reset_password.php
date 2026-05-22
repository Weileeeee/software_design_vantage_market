<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reset Password — VantageMarket</title>
  <link rel="stylesheet" href="/css/auth.css" />
</head>
<body>
<div class="auth-page">
  <div class="auth-card">
    <div class="brand">
      <a href="/" class="brand-logo">
        <div class="brand-icon">🛒</div>
        <span class="brand-name">VantageMarket</span>
      </a>
      <h1 class="auth-title">Set a new password</h1>
      <p class="auth-subtitle">Choose a strong password for your account</p>
    </div>

    <div id="alert-banner" class="alert" role="alert" aria-live="polite"></div>

    <form id="reset-form" class="auth-form" novalidate>
      <!-- Hidden token from URL query string, populated by PHP below -->
      <input type="hidden" name="token" id="token-input" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>" />

      <div class="form-group">
        <label class="form-label" for="password">New password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input id="password" name="password" type="password" class="form-input"
            placeholder="Min 8 chars, uppercase, number, symbol"
            autocomplete="new-password" required />
          <button type="button" class="pw-toggle" id="pw-toggle-1">👁️</button>
        </div>
        <div class="pw-strength">
          <div class="pw-strength-bar"><div class="pw-strength-fill" id="pw-fill"></div></div>
          <span class="pw-strength-label" id="pw-label">Enter a password</span>
        </div>
        <span class="field-error" id="password-error"></span>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm new password</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input id="confirm_password" name="confirm_password" type="password" class="form-input"
            placeholder="Repeat your new password"
            autocomplete="new-password" required />
          <button type="button" class="pw-toggle" id="pw-toggle-2">👁️</button>
        </div>
        <span class="field-error" id="confirm_password-error"></span>
      </div>

      <button type="submit" class="btn-primary" id="reset-btn">
        <span class="btn-text">Reset Password</span>
        <div class="spinner"></div>
      </button>
    </form>

    <div class="auth-footer">
      <a href="/login">← Back to Sign In</a>
    </div>
  </div>
</div>
<script>
(function(){
  const form=document.getElementById('reset-form'),btn=document.getElementById('reset-btn'),
    passEl=document.getElementById('password'),confEl=document.getElementById('confirm_password'),
    banner=document.getElementById('alert-banner'),fill=document.getElementById('pw-fill'),
    lbl=document.getElementById('pw-label');

  // Redirect if no token
  const token=document.getElementById('token-input').value.trim();
  if(!token){
    document.getElementById('alert-banner').className='alert alert-error visible';
    document.getElementById('alert-banner').innerHTML='⚠️ Invalid or missing reset token. <a href="/forgot-password" style="color:inherit;text-decoration:underline">Request a new one</a>.';
    form.style.display='none';
  }

  // Password toggles
  [['pw-toggle-1','password'],['pw-toggle-2','confirm_password']].forEach(([bId,iId])=>{
    document.getElementById(bId).addEventListener('click',()=>{
      const el=document.getElementById(iId),b=document.getElementById(bId);
      const show=el.type==='password';el.type=show?'text':'password';b.textContent=show?'🙈':'👁️';
    });
  });

  // Strength meter
  const LVLS=[{l:'Too short',c:'#f87171',w:'15%'},{l:'Weak',c:'#fb923c',w:'35%'},{l:'Fair',c:'#fbbf24',w:'60%'},{l:'Good',c:'#34d399',w:'80%'},{l:'Strong 💪',c:'#10b981',w:'100%'}];
  function score(pw){if(pw.length<6)return 0;let s=1;if(pw.length>=8)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9]/.test(pw))s++;if(/[\W_]/.test(pw))s++;return Math.min(s,4);}
  passEl.addEventListener('input',()=>{
    const v=passEl.value;if(!v){fill.style.width='0';lbl.textContent='Enter a password';return;}
    const s=LVLS[score(v)];fill.style.width=s.w;fill.style.background=s.c;lbl.textContent=s.l;lbl.style.color=s.c;
  });

  function setErr(id,msg){
    const e=document.getElementById(id+'-error'),i=document.getElementById(id);
    if(msg){e.textContent=msg;e.classList.add('visible');i&&i.classList.add('is-invalid');}
    else{e.textContent='';e.classList.remove('visible');i&&i.classList.remove('is-invalid');}
  }
  function showAlert(t,m){banner.className=`alert alert-${t} visible`;banner.innerHTML=`<span>${t==='error'?'⚠️':'✅'}</span> ${m}`;}
  function setLoading(on){btn.disabled=on;btn.classList.toggle('loading',on);}

  [passEl,confEl].forEach(el=>el.addEventListener('input',()=>setErr(el.id,'')));

  form.addEventListener('submit',async(e)=>{
    e.preventDefault();
    setErr('password','');setErr('confirm_password','');banner.className='alert';
    let ok=true;
    if(!passEl.value){setErr('password','Password is required.');ok=false;}
    if(!confEl.value){setErr('confirm_password','Please confirm your password.');ok=false;}
    if(passEl.value&&confEl.value&&passEl.value!==confEl.value){setErr('confirm_password','Passwords do not match.');ok=false;}
    if(!ok)return;
    setLoading(true);
    try{
      const body=new URLSearchParams(new FormData(form));
      const res=await fetch('/reset-password',{method:'POST',headers:{'Accept':'application/json'},body});
      const data=await res.json();
      if(data.success){
        showAlert('success',data.message??'Password reset! Redirecting to login…');
        setTimeout(()=>{window.location.href=data.redirect??'/login';},1200);
      }else{
        setLoading(false);
        if(data.errors)Object.entries(data.errors).forEach(([f,m])=>setErr(f,Array.isArray(m)?m.join(' '):m));
        else showAlert('error',data.message??'Reset failed. The link may have expired.');
      }
    }catch{setLoading(false);showAlert('error','Network error. Please try again.');}
  });
})();
</script>
</body>
</html>
