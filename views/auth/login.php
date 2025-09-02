<?php $err = $error ?? ''; ?>
<main class="auth-page">
  <h1 class="welcome">Welcome Back</h1>
  <p class="sub">Login to continue</p>

  <section class="auth-card">
    <?php if ($err): ?>
      <div class="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="post" action="/login/submit" class="auth-form">
      <!-- Username (use email behind the scenes to match your model) -->
      <label class="field-label">Username</label>
      <div class="field">
        <span class="icon">
          <!-- person icon -->
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/>
          </svg>
        </span>
        <input type="text" name="email" placeholder="Enter your username" required />
      </div>

      <!-- Password -->
      <label class="field-label">Password</label>
      <div class="field">
        <span class="icon">
          <!-- lock icon -->
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 8v-3h2v3Zm3-8H10V6a2 2 0 0 1 4 0Z" fill="currentColor"/>
          </svg>
        </span>
        <input id="password" type="password" name="password" placeholder="Enter your password" required />
        <button class="pwd-toggle" data-toggle="pwd" aria-label="Show/Hide password" title="Show/Hide password">
          <!-- eye icon -->
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z" fill="currentColor"/>
          </svg>
        </button>
      </div>

      <button type="submit" class="btn-primary">Login</button>

      <div class="form-links">
        <a class="link" href="/forgot">Forgot Password?</a>
      </div>
    </form>
  </section>

  <p class="signup">Don’t have an account? <a class="link-strong" href="/register">Sign up</a></p>
</main>
