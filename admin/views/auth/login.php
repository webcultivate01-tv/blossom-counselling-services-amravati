<?php
$pageTitle = 'Admin Login';
$heading = 'Welcome back';
$subheading = 'Sign in to your admin account';
require __DIR__ . '/_top.php';
?>
      <form method="POST" action="index.php?action=login" novalidate>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($old_email ?? '') ?>"
                 required autofocus autocomplete="username" placeholder="admin@example.com">
        </div>

        <?php $fieldId = 'password'; $fieldName = 'password'; $fieldLabel = 'Password'; include __DIR__ . '/_password_field.php'; ?>

        <div class="form-row">
          <a class="link" href="index.php?action=forgot">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Sign in</button>
      </form>
<?php require __DIR__ . '/_bottom.php'; ?>
