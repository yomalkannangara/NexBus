<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="/public/css/auth.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <a class="top-link" href="/auth/login">
      <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M15 18l-6-6 6-6"/></svg>
      Back to Login
    </a>
    <h1 class="auth-title">Forgot Password?</h1>
    <p class="auth-sub">Enter your email address and we’ll send you a link to reset your password</p>

    <form method="post" action="/auth/forgot">
      <div class="form-row">
        <label class="label" for="email">Email Address</label>
        <div class="input-with-icon">
          <span class="icon">✉️</span>
          <input class="input" type="email" id="email" name="email" placeholder="Enter your email address" required>
        </div>
      </div>

      <!-- CSRF -->
      <!-- <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>"> -->

      <button class="btn btn-primary" type="submit">Send Reset Link</button>
    </form>

    <p class="helper">Remember your password? <a href="/auth/login">Sign in</a></p>
  </div>
</div>
</body>
</html>
