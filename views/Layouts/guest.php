<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>NexBus • Login</title>
  <link rel="stylesheet" href="../assets/css/auth.css" />
</head>
<body class="guest">

  <!-- Top brand bar -->
  <header class="brandbar">
    <div class="brand-wrap">
      <img class="brand-logo" src="../assets/images/logo.png" alt="NexBus Logo" />
      <div class="brand-text">
        <div class="brand-title">NTC Bus Management</div>
        <div class="brand-sub">National Transport Commission</div>
      </div>
    </div>
  </header>

<main id="content" class="active">

      <?php
        require $contentViewFile;
      ?>
    </main>

  <script>
    // toggle password visibility
    const tgl = document.querySelector('[data-toggle="pwd"]');
    if (tgl) {
      tgl.addEventListener('click', function (e) {
        e.preventDefault();
        const input = document.getElementById('password');
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        this.setAttribute('aria-pressed', input.type === 'text');
      });
    }
  </script>
</body>
</html>
