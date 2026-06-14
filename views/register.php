<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account — VantageMarket</title>
  <meta name="description" content="Join VantageMarket to start shopping, track orders, and enjoy member-exclusive benefits." />
  <link rel="stylesheet" href="/css/auth.css" />
</head>
<body>
<div class="auth-page">
  <div class="auth-card" style="max-width:480px;">

    <!-- Brand -->
    <div class="brand">
      <a href="/" class="brand-logo">
        <div class="brand-icon">🛒</div>
        <span class="brand-name">VantageMarket</span>
      </a>
      <h1 class="auth-title">Create an account</h1>
      <p class="auth-subtitle">Join thousands of shoppers on VantageMarket</p>
    </div>

    <!-- Alert banner -->
    <div id="alert-banner" class="alert" role="alert" aria-live="polite"></div>

    <!-- Register form -->
    <form id="register-form" class="auth-form" novalidate>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="first_name">First name</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input id="first_name" name="first_name" type="text" class="form-input"
              placeholder="Alice" autocomplete="given-name" required />
          </div>
          <span class="field-error" id="first_name-error"></span>
        </div>

        <div class="form-group">
          <label class="form-label" for="last_name">Last name</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input id="last_name" name="last_name" type="text" class="form-input"
              placeholder="Tan" autocomplete="family-name" required />
          </div>
          <span class="field-error" id="last_name-error"></span>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input id="email" name="email" type="email" class="form-input"
            placeholder="you@example.com" autocomplete="email" required />
        </div>
        <span class="field-error" id="email-error"></span>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input id="password" name="password" type="password" class="form-input"
            placeholder="Min 8 chars, uppercase, number, symbol"
            autocomplete="new-password" required />
          <button type="button" class="pw-toggle" id="pw-toggle-1">👁️</button>
        </div>
        <div class="pw-strength" id="pw-strength" aria-live="polite">
          <div class="pw-strength-bar">
            <div class="pw-strength-fill" id="pw-strength-fill"></div>
          </div>
          <span class="pw-strength-label" id="pw-strength-label">Enter a password</span>
        </div>
        <span class="field-error" id="password-error"></span>
      </div>

      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm password</label>
        <div class="input-wrap">
          <span class="input-icon">🔑</span>
          <input id="confirm_password" name="confirm_password" type="password" class="form-input"
            placeholder="Repeat your password"
            autocomplete="new-password" required />
          <button type="button" class="pw-toggle" id="pw-toggle-2">👁️</button>
        </div>
        <span class="field-error" id="confirm_password-error"></span>
      </div>

      <div class="terms-row">
        <input type="checkbox" id="terms_accepted" name="terms_accepted" value="1" required />
        <label for="terms_accepted">
          I agree to VantageMarket's
          <a href="/terms" target="_blank">Terms of Service</a> and
          <a href="/privacy" target="_blank">Privacy Policy</a>
        </label>
      </div>
      <span class="field-error" id="terms-error"></span>

      <button type="submit" class="btn-primary" id="register-btn">
        <span class="btn-text">Create Account</span>
        <div class="spinner"></div>
      </button>

    </form>

    <div class="auth-footer">
      Already have an account? <a href="/login">Sign in</a>
    </div>

  </div>
</div>

<script>
(function () {
  'use strict';

  const form      = document.getElementById('register-form');
  const btn       = document.getElementById('register-btn');
  const passEl    = document.getElementById('password');
  const confEl    = document.getElementById('confirm_password');
  const banner    = document.getElementById('alert-banner');
  const fillBar   = document.getElementById('pw-strength-fill');
  const fillLabel = document.getElementById('pw-strength-label');

  // ── Password toggles ────────────────────────────────────────
  [['pw-toggle-1', 'password'], ['pw-toggle-2', 'confirm_password']].forEach(([btnId, inputId]) => {
    document.getElementById(btnId).addEventListener('click', () => {
      const el   = document.getElementById(inputId);
      const btn  = document.getElementById(btnId);
      const show = el.type === 'password';
      el.type     = show ? 'text' : 'password';
      btn.textContent = show ? '🙈' : '👁️';
    });
  });

  // ── Password strength meter ──────────────────────────────────
  const LEVELS = [
    { label: 'Too short',  color: '#f87171', width: '15%' },
    { label: 'Weak',       color: '#fb923c', width: '35%' },
    { label: 'Fair',       color: '#fbbf24', width: '60%' },
    { label: 'Good',       color: '#34d399', width: '80%' },
    { label: 'Strong 💪',  color: '#10b981', width: '100%'},
  ];

  function scorePassword(pw) {
    if (pw.length < 6) return 0;
    let score = 1;
    if (pw.length >= 8)  score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[\W_]/.test(pw)) score++;
    return Math.min(score, 4);
  }

  passEl.addEventListener('input', () => {
    const val   = passEl.value;
    const score = val ? scorePassword(val) : -1;
    if (score < 0) {
      fillBar.style.width = '0';
      fillLabel.textContent = 'Enter a password';
      return;
    }
    const lvl = LEVELS[score];
    fillBar.style.width      = lvl.width;
    fillBar.style.background = lvl.color;
    fillLabel.textContent    = lvl.label;
    fillLabel.style.color    = lvl.color;
  });

  // ── Helpers ─────────────────────────────────────────────────
  function setFieldError(id, message) {
    // Normalize: 'terms' and 'terms_accepted' both map to the same error span
    const normalId = (id === 'terms_accepted') ? 'terms' : id;
    const errId    = normalId + '-error';
    const el       = document.getElementById(errId);
    const input    = document.getElementById(id);
    if (!el) return; // guard — skip unknown fields
    if (message) {
      el.textContent = message;
      el.classList.add('visible');
      if (input) input.classList.add('is-invalid');
    } else {
      el.textContent = '';
      el.classList.remove('visible');
      if (input) input.classList.remove('is-invalid');
    }
  }

  function showAlert(type, message) {
    banner.className = `alert alert-${type} visible`;
    banner.innerHTML = `<span>${type === 'error' ? '⚠️' : '✅'}</span> ${message}`;
  }

  function clearErrors() {
    ['first_name','last_name','email','password','confirm_password','terms_accepted'].forEach(id => setFieldError(id, ''));
    banner.className = 'alert';
  }

  function setLoading(on) {
    btn.disabled = on;
    btn.classList.toggle('loading', on);
  }

  // ── Client-side validation ───────────────────────────────────
  function validateClient() {
    let ok = true;
    const req = ['first_name','last_name','email','password','confirm_password'];
    req.forEach(id => {
      if (!document.getElementById(id).value.trim()) {
        const label = id.replace('_', ' ');
        setFieldError(id, `${label.charAt(0).toUpperCase() + label.slice(1)} is required.`);
        ok = false;
      }
    });
    if (passEl.value && confEl.value && passEl.value !== confEl.value) {
      setFieldError('confirm_password', 'Passwords do not match.');
      ok = false;
    }
    if (!document.getElementById('terms_accepted').checked) {
      setFieldError('terms', 'You must accept the Terms of Service and Privacy Policy.');
      ok = false;
    }
    return ok;
  }

  // ── Submit ───────────────────────────────────────────────────
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();
    if (!validateClient()) return;
    setLoading(true);

    try {
      const body = new URLSearchParams(new FormData(form));
      const res  = await fetch('/register', {
        method:  'POST',
        headers: { 'Accept': 'application/json' },
        body,
      });
      const data = await res.json();

      if (data.success) {
        showAlert('success', data.message ?? 'Account created! Redirecting…');
        setTimeout(() => { window.location.href = data.redirect ?? '/'; }, 800);
      } else {
        setLoading(false);
        if (data.errors) {
          Object.entries(data.errors).forEach(([field, msg]) => {
            setFieldError(field, Array.isArray(msg) ? msg.join(' ') : msg);
          });
        } else {
          showAlert('error', data.message ?? 'Registration failed. Please try again.');
        }
      }
    } catch (err) {
      setLoading(false);
      showAlert('error', 'Unable to connect to the server. Please check your connection and try again.');
    }
  });

  // Clear on input
  form.querySelectorAll('input').forEach(el => {
    el.addEventListener('input', () => setFieldError(el.id, ''));
  });

})();
</script>
</body>
</html>
