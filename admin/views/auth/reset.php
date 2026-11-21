<?php
$pageTitle = 'Reset Password';
$heading = 'Reset your password';
$subheading = 'Enter the 6-digit code sent to ' . $email . ' and choose a new password.';
require __DIR__ . '/_top.php';
?>
      <form method="POST" action="index.php?action=reset" novalidate>
        <div class="form-group">
          <label for="code">6-digit code</label>
          <input type="text" id="code" name="code" class="code-input" inputmode="numeric" pattern="[0-9]{6}"
                 maxlength="6" required autofocus autocomplete="one-time-code" placeholder="000000">
        </div>

        <?php $fieldId = 'password'; $fieldName = 'password'; $fieldLabel = 'New password (min 8 characters)'; $autocomplete = 'new-password'; include __DIR__ . '/_password_field.php'; ?>
        <?php $fieldId = 'password_confirm'; $fieldName = 'password_confirm'; $fieldLabel = 'Confirm new password'; $autocomplete = 'new-password'; include __DIR__ . '/_password_field.php'; ?>

        <button type="submit" class="btn btn-primary btn-block">Update password</button>
      </form>
      <p class="auth-switch">
        <a class="link" href="index.php?action=forgot">Resend code</a> &middot;
        <a class="link" href="index.php?action=login">Back to sign in</a>
      </p>
<?php require __DIR__ . '/_bottom.php'; ?>
