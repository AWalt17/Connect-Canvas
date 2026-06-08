<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Midwest Art Connect | Login</title>
  <link rel="stylesheet" href="style.css">
  
</head>
<body>

  <header>
    <div class="logo">Midwest Art Connect</div>
    <nav>
      <a href="Index.html">Home</a>
      <a href="#">Portfolios</a>
      <a href="#">Projects</a>
      <a href="#">Businesses</a>
      <a href="login.php">Login</a>
    </nav>
  </header>

  <section class="hero">
    <h1>Login or Create an Account</h1>
    <p>Use your email and password to access your account. New here? Create an artist account with a display name.</p>
  </section>

  <section class="wrap">

    <div class="card">
      <h2>Login</h2>
      <form method="post" autocomplete="on">
        <input type="hidden" name="action" value="login">

        <label>Email</label>
        <input type="email" name="email" placeholder="name@example.com" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="your password" required>

        <button class="btn-primary" type="submit">Login</button>
      </form>

      <?php if (!empty($_SESSION['email'])): ?>
        <div class="logged">
          <b>Logged in as:</b> <?php echo h($_SESSION['display_name'] ?? 'User'); ?>
          <br><small><?php echo h($_SESSION['email']); ?></small>

          <form method="post" style="margin-top:10px;">
            <input type="hidden" name="action" value="logout">
            <button class="btn-secondary" type="submit">Logout</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Create New Account</h2>
      <form method="post" autocomplete="on">
        <input type="hidden" name="action" value="register">

        <label>Email</label>
        <input type="email" name="email" placeholder="name@example.com" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="create a password" required>

        <label>Display Name</label>
        <input type="text" name="display_name" placeholder="Jane Doe" required>

        <button class="btn-primary" type="submit">Create New Account</button>
      </form>

    </div>

  </section>

</body>
</html>