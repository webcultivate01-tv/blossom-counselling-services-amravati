<?php
$pageTitle = 'Forgot Password';
$heading = 'Forgot your password?';
$subheading = "Enter your admin email and we'll send you a 6-digit code.";
$success = null;
require __DIR__ . '/_top.php';
?>
      <form method="POST" action="index.php?action=forgot" novalidate>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($old_email ?? '') ?>"
                 required autofocus autocomplete="username" placeholder="admin@example.com">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Send code</button>
      </form>
      <p class="auth-switch"><a class="link" href="index.php?action=login">&larr; Back to sign in</a></p>
<?php require __DIR__ . '/_bottom.php'; ?>
