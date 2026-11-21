<?php
$navItems = [
    ['dashboard', 'Dashboard', 'dashboard', 'index.php', 0],
    ['today', "Today's Appointments", 'clock', 'index.php?action=appointments_today', $navCounts['today'] ?? 0],
    ['appointments', 'Appointments', 'calendar', 'index.php?action=appointments', $navCounts['appointments'] ?? 0],
    ['blogs', 'Blog Management', 'blog', 'index.php?action=blogs', 0],
    ['gallery', 'Gallery Management', 'image', 'index.php?action=gallery', 0],
    ['payments', 'Payments Management', 'wallet', 'index.php?action=payments', 0],
    ['reports', 'Reports', 'chart', 'index.php?action=reports', 0],
    ['categories', 'Category Management', 'tag', 'index.php?action=categories', 0],
    ['account', 'My Account', 'user', 'index.php?action=account', 0],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= h($pageTitle) ?> – <?= h(APP_NAME) ?></title>
  <base href="<?= h(ADMIN_URL) ?>">
  <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="shell">
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <aside class="sidebar" id="sidebar">
      <div class="logo">
        <img src="<?= h(site_url('public/logo.png')) ?>" alt="" onerror="this.style.display='none'">
        <div>
          <strong>Admin Panel</strong>
          <span><?= h(APP_NAME) ?></span>
        </div>
        <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Close menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
      </div>

      <nav class="nav" aria-label="Main">
        <?php foreach ($navItems as [$key, $label, $ico, $href, $badge]): ?>
          <a href="<?= h($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"<?= $active === $key ? ' aria-current="page"' : '' ?>>
            <?= icon($ico) ?>
            <span><?= h($label) ?></span>
            <?php if ($badge > 0): ?><em class="nav-badge"><?= $badge > 99 ? '99+' : (int) $badge ?></em><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="sidebar-bottom">
        <a href="<?= h(site_url('index.html')) ?>" class="back-site" target="_blank" rel="noopener">
          <?= icon('globe') ?><span>Back to Site</span>
        </a>
        <div class="side-user">
          <?= avatar_html($admin, 'avatar') ?>
          <div>
            <strong><?= h($admin['name']) ?></strong>
            <span>Admin</span>
          </div>
        </div>
        <form method="post" action="<?= h(url('action=logout')) ?>">
          <button type="submit" class="btn-logout"><?= icon('logout') ?> Log out</button>
        </form>
      </div>
    </aside>

    <div class="content">
      <header class="topbar">
        <button type="button" class="menu-btn" id="menuBtn" aria-label="Open menu"><?= icon('menu', 22) ?></button>
        <?php if ($active !== 'dashboard'): ?>
          <a href="index.php" class="back-btn" id="backBtn" aria-label="Go back" title="Back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
          </a>
          <script>
            document.getElementById('backBtn').addEventListener('click', function (e) {
              if (document.referrer && document.referrer.indexOf(location.host) !== -1 && history.length > 1) {
                e.preventDefault(); history.back();
              }
            });
          </script>
        <?php endif; ?>
        <div class="topbar-title">
          <h2><?= h($pageTitle) ?></h2>
          <p><?= h($pageSubtitle ?? '') ?></p>
        </div>
        <div class="user-chip">
          <span class="clock" id="clock"></span>
          <span class="divider"></span>
          <strong class="user-name"><?= h($admin['name']) ?></strong>
          <?= avatar_html($admin, 'avatar') ?>
        </div>
      </header>

      <main class="page">
        <?php if (!empty($flashError)): ?>
          <div class="alert alert-error" role="alert"><?= h($flashError) ?></div>
        <?php endif; ?>
        <?php if (!empty($flashSuccess)): ?>
          <div class="alert alert-success" role="status"><?= h($flashSuccess) ?></div>
        <?php endif; ?>
