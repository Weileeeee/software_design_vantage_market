<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Forgot Password — VantageMarket</title>
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
      <h1 class="auth-title">Reset your password</h1>
      <p class="auth-subtitle">Enter your email and we'll send a reset link</p>
    </div>

    <div id="alert-banner" class="alert" role="alert" aria-live="polite"></div>

    <form id="forgot-form" class="auth-form" novalidate>
      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input id="email" name="email" type="email" class="form-input"
            placeholder="you@example.com" autocomplete="email" required />
        </div>
        <span class="field-error" id="email-error"></span>
      </div>
      <button type="submit" class="btn-primary" id="forgot-btn">
        <span class="btn-text">Send Reset Link</span>
        <div class="spinner"></div>
      </button>
    </form>

    <div id="success-state" style="display:none;text-align:center;margin-top:1.5rem;">
      <div style="font-size:3rem;margin-bottom:1rem;">📬</div>
      <h2 style="font-size:1.15rem;font-weight:600;color:var(--text-primary);margin-bottom:.5rem;">Check your inbox</h2>
      <p style="font-size:.875rem;color:var(--text-secondary);line-height:1.6;">
        If that email is registered, a reset link has been sent. Check your spam folder too.
      </p>
      <a href="/login" style="display:inline-block;margin-top:1.5rem;color:var(--accent);font-weight:500;text-decoration:none;">← Back to Sign In</a>
    </div>

    <div class="auth-footer" id="footer-links">
      Remembered it? <a href="/login">Sign in</a>
    </div>
  </div>
</div>
<script>
(function(){
  const form=document.getElementById('forgot-form'),btn=document.getElementById('forgot-btn'),
    emailEl=document.getElementById('email'),banner=document.getElementById('alert-banner'),
    success=document.getElementById('success-state'),footer=document.getElementById('footer-links');

  function setErr(id,msg){
    const e=document.getElementById(id+'-error'),i=document.getElementById(id);
    if(msg){e.textContent=msg;e.classList.add('visible');i&&i.classList.add('is-invalid');}
    else{e.textContent='';e.classList.remove('visible');i&&i.classList.remove('is-invalid');}
  }
  function showAlert(t,m){banner.className=`alert alert-${t} visible`;banner.innerHTML=`<span>${t==='error'?'⚠️':'✅'}</span> ${m}`;}
  function setLoading(on){btn.disabled=on;btn.classList.toggle('loading',on);}

  emailEl.addEventListener('input',()=>setErr('email',''));

  form.addEventListener('submit',async(e)=>{
    e.preventDefault();
    if(!emailEl.value.trim()){setErr('email','Please enter your email address.');return;}
    banner.className='alert';setLoading(true);
    try{
      const res=await fetch('/forgot-password',{method:'POST',headers:{'Accept':'application/json'},body:new URLSearchParams(new FormData(form))});
      const data=await res.json();
      if(data.success){form.style.display='none';footer.style.display='none';success.style.display='block';}
      else{setLoading(false);showAlert('error',data.message??'Something went wrong.');}
    }catch{setLoading(false);showAlert('error','Network error. Please try again.');}
  });
})();
</script>
</body>
</html>
