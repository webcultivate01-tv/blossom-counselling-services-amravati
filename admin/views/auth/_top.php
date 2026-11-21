<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> – <?= htmlspecialchars(APP_NAME) ?></title>
  <base href="<?= htmlspecialchars(ADMIN_URL) ?>">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-body">
  <main class="auth-wrap">
    <div class="auth-card">
      <div class="auth-brand">
        <img src="../public/logo.png" alt="<?= htmlspecialchars(APP_NAME) ?>" onerror="this.style.display='none'">
        <h1><?= htmlspecialchars($heading) ?></h1>
        <p><?= htmlspecialchars($subheading) ?></p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <?php if (!empty($success)): ?>
        <div class="alert alert-success" role="status"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
