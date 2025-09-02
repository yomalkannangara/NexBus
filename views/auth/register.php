<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Create Account</title>
  <link rel="stylesheet" href="/public/css/auth.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <h1 class="auth-title">Create Account</h1>
    <p class="auth-sub">Sign up to get started</p>

    <form method="post" action="/auth/register">
      <!-- Username -->
      <div class="form-row">
        <label class="label" for="username">Username</label>
        <div class="input-with-icon">
          <span class="icon">👤</span>
          <input class="input" type="text" id="username" name="username" placeholder="Enter your username" required>
        </div>
      </div>

      <!-- Email -->
      <div class="form-row">
        <label class="label" for="email">Email</label>
        <div class="input-with-icon">
          <span class="icon">✉️</span>
          <input class="input" type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>
      </div>

      <!-- Password -->
      <div class="form-row">
        <label class="label" for="password">Password</label>
        <div class="input-with-icon">
          <span class="icon">🔒</span>
          <input class="input" type="password" id="password" name="password" placeholder="Enter your password" required minlength="6">
          <button class="eye" type="button" data-toggle="#password">👁</button>
        </div>
      </div>

      <!-- Confirm -->
      <div class="form-row">
        <label class="label" for="password2">Confirm Password</label>
        <div class="input-with-icon">
          <span class="icon">🔒</span>
          <input class="input" type="password" id="password2" name="password_confirmation" placeholder="Confirm your password" required>
          <button class="eye" type="button" data-toggle="#password2">👁</button>
        </div>
      </div>

      <!-- CSRF (if PHP) -->
      <!-- <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf ?? '') ?>"> -->

      <button class="btn btn-primary" type="submit">Create Account</button>
    </form>

    <p class="note">
      By signing up, you agree to our
      <a href="/terms">Terms &amp; Conditions</a> and
      <a href="/privacy">Privacy Policy</a>.
    </p>

    <p class="helper">Already have an account? <a href="/auth/login">Sign in</a></p>
  </div>
</div>

<script>
document.querySelectorAll('.eye').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const input = document.querySelector(btn.dataset.toggle);
    input.type = input.type === 'password' ? 'text' : 'password';
  });
});
</script>
</body>
</html>
