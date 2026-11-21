<?php
$pager = ['page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total'], 'per' => 10];
$pagerQuery = 'action=blogs' . ($filters['q'] !== '' ? '&q=' . rawurlencode($filters['q']) : '') . ($filters['status'] !== '' ? '&status=' . rawurlencode($filters['status']) : '');
?>
<div class="stat-row stat-row-3">
  <div class="stat-tile"><span class="stat-icon"><?= icon('blog', 20) ?></span><span><b><?= (int) $counts['total'] ?></b><small>Total articles</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('check', 20) ?></span><span><b><?= (int) $counts['published'] ?></b><small>Published</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('edit', 20) ?></span><span><b><?= (int) $counts['draft'] ?></b><small>Drafts</small></span></div>
</div>

<form method="get" action="<?= h(ADMIN_URL . 'index.php') ?>" class="filter-bar">
  <input type="hidden" name="action" value="blogs">
  <input type="search" name="q" value="<?= h($filters['q']) ?>" placeholder="Search title or category">
  <select name="status" aria-label="Status">
    <option value="">All statuses</option>
    <?php foreach (BlogPost::STATUSES as $k => $label): ?>
      <option value="<?= h($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary btn-sm">Filter</button>
  <a class="btn btn-primary btn-sm push-right" href="<?= h(url('action=blog_form')) ?>"><?= icon('plus', 15) ?> New article</a>
</form>

<div class="table-card">
  <div class="table-scroll">
    <table class="table">
      <thead><tr><th>Image</th><th>Title</th><th>Category</th><th>Status</th><th>Date</th><th class="t-right">Actions</th></tr></thead>
      <tbody>
        <?php if (!$result['rows']): ?>
          <tr><td colspan="6" class="empty">No articles yet. Click “New article” to write the first one.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['rows'] as $r): ?>
          <tr>
            <td><?php if ($r['image']): ?><img class="thumb" src="<?= h(site_url($r['image'])) ?>" alt=""><?php else: ?><span class="thumb thumb-empty"><?= icon('image', 18) ?></span><?php endif; ?></td>
            <td><strong><?= h($r['title']) ?></strong><small class="sub"><?= h($r['read_time']) ?> min read</small></td>
            <td><?= h($r['category']) ?></td>
            <td><span class="pill <?= $r['status'] === 'published' ? 'st-converted' : 'st-closed' ?>"><?= h(BlogPost::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
            <td class="nowrap"><?= h(fmt_date($r['created_at'], 'd M Y')) ?></td>
            <td class="t-right nowrap">
              <a class="btn btn-outline btn-sm" href="<?= h(url('action=blog_form&id=' . (int) $r['id'])) ?>"><?= icon('edit', 15) ?> Edit</a>
              <form method="post" action="<?= h(url('action=blog_delete')) ?>" class="inline" data-confirm="Delete this article? This cannot be undone.">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button type="submit" class="btn btn-danger-outline btn-sm" aria-label="Delete"><?= icon('trash', 15) ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php require __DIR__ . '/../layout/pagination.php'; ?>
</div>
