<?php $err = $error ?? ''; ?>
<main class="auth-page">
  <h1 class="welcome">Create Account</h1>
  <p class="sub">Sign up as Passenger</p>

  <section class="auth-card">
    <?php if ($err): ?>
      <div class="alert"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <!-- Same classes/CSS/JS pattern as login -->
    <form method="post" action="/register" class="auth-form">
      <!-- Full name -->
      <label class="field-label">Full name</label>
      <div class="field">
        <span class="icon">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/>
          </svg>
        </span>
        <input type="text" name="full_name" placeholder="Enter your full name" required />
      </div>

      <!-- Username (email under the hood, to match login) -->
      <label class="field-label">Username</label>
      <div class="field">
        <span class="icon">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.42 0-8 2.24-8 5v1h16v-1c0-2.76-3.58-5-8-5Z" fill="currentColor"/>
          </svg>
        </span>
        <input type="text" name="email" placeholder="Enter your username (email)" required />
      </div>

      <!-- Phone (optional) -->
      <label class="field-label">Phone (optional)</label>
      <div class="field">
        <span class="icon">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M6.62 10.79a15 15 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24 11.36 11.36 0 0 0 3.56.57 1 1 0 0 1 1 1v3.62a1 1 0 0 1-1 1A17.62 17.62 0 0 1 2 6a1 1 0 0 1 1-1h3.61a1 1 0 0 1 1 1 11.36 11.36 0 0 0 .57 3.56 1 1 0 0 1-.24 1.01Z" fill="currentColor"/>
          </svg>
        </span>
        <input type="text" name="phone" placeholder="07XXXXXXXX" />
      </div>

      <!-- Password -->
      <label class="field-label">Password</label>
      <div class="field">
        <span class="icon">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 8v-3h2v3Zm3-8H10V6a2 2 0 0 1 4 0Z" fill="currentColor"/>
          </svg>
        </span>
        <input id="password" type="password" name="password" placeholder="Create a password" required />
        <button class="pwd-toggle" data-toggle="pwd" aria-label="Show/Hide password" title="Show/Hide password">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z" fill="currentColor"/>
          </svg>
        </button>
      </div>

      <!-- Confirm password (no new JS; normal required field) -->
      <label class="field-label">Confirm Password</label>
      <div class="field">
        <span class="icon">
          <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true">
            <path d="M17 8h-1V6a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-6 8v-3h2v3Zm3-8H10V6a2 2 0 0 1 4 0Z" fill="currentColor"/>
          </svg>
        </span>
        <input type="password" name="confirm_password" placeholder="Re-enter password" required />
      </div>

      <button type="submit" class="btn-primary">Create Account</button>
    </form>
  </section>

  <p class="signup">Already have an account? <a class="link-strong" href="/login">Log in</a></p>
</main>
