<section class="card">
  <h4>Profile Photo</h4>
  <div class="photo-row">
    <?= avatar_html($admin, 'avatar avatar-xl') ?>
    <div>
      <form method="post" action="<?= h(url('action=account_photo')) ?>" enctype="multipart/form-data" class="photo-form">
        <?= csrf_field() ?>
        <label class="btn btn-outline btn-sm file-btn">
          <?= icon('camera', 16) ?> Change photo
          <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-autosubmit hidden>
        </label>
      </form>
      <?php if (!empty($admin['avatar'])): ?>
        <form method="post" action="<?= h(url('action=account_photo')) ?>" class="inline" data-confirm="Remove your profile photo?">
          <?= csrf_field() ?><input type="hidden" name="remove" value="1">
          <button type="submit" class="link-btn">Remove photo</button>
        </form>
      <?php endif; ?>
      <p class="muted small">JPG, PNG or WEBP (max 5 MB)</p>
    </div>
  </div>
</section>

<div class="two-col">
  <form method="post" action="<?= h(url('action=account_update')) ?>" class="card form-card">
    <?= csrf_field() ?>
    <div class="card-title"><span class="stat-icon"><?= icon('user', 20) ?></span><div><h4>Profile Details</h4><p>Update your basic information</p></div></div>
    <div class="form-row-2">
      <div class="form-group"><label for="name">Name</label><input type="text" id="name" name="name" maxlength="100" required value="<?= h($admin['name']) ?>"></div>
      <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" maxlength="150" required value="<?= h($admin['email']) ?>"></div>
    </div>
    <div class="form-actions"><button type="submit" class="btn btn-primary">Save changes</button></div>
  </form>

  <form method="post" action="<?= h(url('action=account_password')) ?>" class="card form-card" autocomplete="off">
    <?= csrf_field() ?>
    <div class="card-title"><span class="stat-icon"><?= icon('lock', 20) ?></span><div><h4>Change Password</h4><p>Set a new password for your account</p></div></div>
    <?php
      $fieldId = 'current_password'; $fieldName = 'current_password'; $fieldLabel = 'Current password';
      $autocomplete = 'current-password'; $placeholder = 'Enter current password'; $minlength = null;
      include __DIR__ . '/../auth/_password_field.php';
    ?>
    <div class="form-row-2">
      <?php
        $fieldId = 'password'; $fieldName = 'password'; $fieldLabel = 'New password';
        $autocomplete = 'new-password'; $placeholder = 'At least 8 characters'; $minlength = 8;
        include __DIR__ . '/../auth/_password_field.php';
        $fieldId = 'password_confirm'; $fieldName = 'password_confirm'; $fieldLabel = 'Confirm new password';
        $autocomplete = 'new-password'; $placeholder = 'Confirm new password'; $minlength = 8;
        include __DIR__ . '/../auth/_password_field.php';
      ?>
    </div>
    <div class="form-actions"><button type="submit" class="btn btn-primary">Update password</button></div>
  </form>
</div>

<script>
document.querySelectorAll('.toggle-pass').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var input = document.getElementById(btn.dataset.target);
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.classList.toggle('is-visible', show);
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
  });
});
</script>
