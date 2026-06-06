<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In — VantageMarket</title>
  <meta name="description" content="Sign in to your VantageMarket account to shop, track orders, and manage your profile." />
  <link rel="stylesheet" href="/css/auth.css" />
</head>
<body>
<div class="auth-page">
  <div class="auth-card">

    <!-- Brand -->
    <div class="brand">
      <a href="/" class="brand-logo">
        <div class="brand-icon">🛒</div>
        <span class="brand-name">VantageMarket</span>
      </a>
      <h1 class="auth-title">Welcome back</h1>
      <p class="auth-subtitle">Sign in to your account to continue shopping</p>
    </div>

    <!-- Alert banner -->
    <div id="alert-banner" class="alert" role="alert" aria-live="polite"></div>

    <!-- Login form -->
    <form id="login-form" class="auth-form" novalidate>

      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <div class="input-wrap">
          <span class="input-icon">✉️</span>
          <input
            id="email"
            name="email"
            type="email"
            class="form-input"
            placeholder="you@example.com"
            autocomplete="email"
            required
          />
        </div>
        <span class="field-error" id="email-error"></span>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">🔒</span>
          <input
            id="password"
            name="password"
            type="password"
            class="form-input"
            placeholder="Enter your password"
            autocomplete="current-password"
            required
          />
          <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Toggle password visibility">👁️</button>
        </div>
        <span class="field-error" id="password-error"></span>
      </div>

      <div class="check-row">
        <label class="check-label" for="remember_me">
          <input type="checkbox" id="remember_me" name="remember_me" value="1" />
          Keep me signed in
        </label>
        <a href="/forgot-password" class="forgot-link">Forgot password?</a>
      </div>

      <button type="submit" class="btn-primary" id="login-btn">
        <span class="btn-text">Sign In</span>
        <div class="spinner"></div>
      </button>

    </form>

    <div class="auth-footer">
      Don't have an account?
      <a href="/register">Create one</a>
    </div>

  </div>
</div>

<script>
(function () {
  'use strict';

  const form      = document.getElementById('login-form');
  const btn       = document.getElementById('login-btn');
  const emailEl   = document.getElementById('email');
  const passEl    = document.getElementById('password');
  const toggle    = document.getElementById('pw-toggle');
  const banner    = document.getElementById('alert-banner');

  // ── Password visibility toggle ──────────────────────────────
  toggle.addEventListener('click', () => {
    const isText = passEl.type === 'text';
    passEl.type  = isText ? 'password' : 'text';
    toggle.textContent = isText ? '👁️' : '🙈';
  });

  // ── Show / hide inline field error ─────────────────────────
  function setFieldError(id, message) {
    const el    = document.getElementById(id + '-error');
    const input = document.getElementById(id);
    if (message) {
      el.textContent = message;
      el.classList.add('visible');
      input.classList.add('is-invalid');
    } else {
      el.textContent = '';
      el.classList.remove('visible');
      input.classList.remove('is-invalid');
    }
  }

  // ── Show alert banner ───────────────────────────────────────
  function showAlert(type, message) {
    banner.className  = `alert alert-${type} visible`;
    banner.innerHTML  = `<span>${type === 'error' ? '⚠️' : '✅'}</span> ${message}`;
  }

  function clearErrors() {
    ['email', 'password'].forEach(id => setFieldError(id, ''));
    banner.className = 'alert';
    banner.textContent = '';
  }

  // ── Loading state ───────────────────────────────────────────
  function setLoading(on) {
    btn.disabled = on;
    btn.classList.toggle('loading', on);
  }

  // ── Form submit ─────────────────────────────────────────────
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors();

    // Client-side presence check
    let hasError = false;
    if (!emailEl.value.trim()) {
      setFieldError('email', 'Email address is required.');
      hasError = true;
    }
    if (!passEl.value) {
      setFieldError('password', 'Password is required.');
      hasError = true;
    }
    if (hasError) return;

    setLoading(true);

    try {
      const body = new URLSearchParams(new FormData(form));
      const res  = await fetch('/login', {
        method:  'POST',
        headers: { 'Accept': 'application/json' },
        body,
      });

      let data;
      try {
        data = await res.json();
      } catch (_) {
        // Response wasn't JSON — server may not be routing through index.php
        setLoading(false);
        showAlert('error', 'Server error. Please ensure the app is running through index.php.');
        return;
      }

      if (data.success) {
        showAlert('success', data.message ?? 'Login successful!');
        setTimeout(() => {
          window.location.href = data.redirect ?? '/';
        }, 500);
      } else {
        setLoading(false);

        // Map field-level errors from the API
        if (data.errors) {
          Object.entries(data.errors).forEach(([field, msg]) => {
            const message = Array.isArray(msg) ? msg.join(', ') : msg;
            setFieldError(field, message);
          });
        } else {
          // Show specific messages for common HTTP status codes
          if (res.status === 401) {
            showAlert('error', 'Invalid email or password. Please try again.');
          } else if (res.status === 423) {
            showAlert('error', data.message ?? 'Account locked due to too many failed attempts. Please try again later.');
          } else {
            showAlert('error', data.message ?? 'Login failed. Please try again.');
          }
        }
      }

    } catch (err) {
      setLoading(false);
      showAlert('error', 'Unable to connect to the server. Please check your connection and try again.');
    }
  });

  // ── Clear error on typing ───────────────────────────────────
  [emailEl, passEl].forEach(el => {
    el.addEventListener('input', () => setFieldError(el.id, ''));
  });

})();
</script>
</body>
</html>
