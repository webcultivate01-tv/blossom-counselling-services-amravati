<?php
$isAppt = $cfg['key'] === 'appointment';
$statTiles = $isAppt
    ? [['calendar', 'Total', $counts['total']], ['clock', 'Pending', $counts['pending']], ['check', 'Confirmed', $counts['confirmed']], ['check', 'Completed', $counts['completed']], ['refresh', 'Rescheduled', $counts['rescheduled']], ['x', 'Not Interested', $counts['not_interested']]]
    : [['inbox', 'Total Inquiries', $counts['total']], ['plus', 'New', $counts['new']], ['clock', 'In Progress', $counts['in_progress']], ['refresh', 'Follow-up Req.', $counts['follow_up']], ['check', 'Converted', $counts['converted']], ['lock', 'Closed', $counts['closed']]];
$pager = ['page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total'], 'per' => 10];
$base = 'action=' . $cfg['plural'];
$qsNoPage = ltrim((string) preg_replace('/(^|&)page=\d+/', '', $listQuery), '&');
$pagerQuery = $base . ($qsNoPage !== '' ? '&' . $qsNoPage : '');
?>
<div class="stat-row<?= $isAppt ? ' stat-row-3' : '' ?>">
  <?php foreach ($statTiles as [$ico, $label, $num]): ?>
    <div class="stat-tile"><span class="stat-icon"><?= icon($ico, 20) ?></span><span><b><?= (int) $num ?></b><small><?= h($label) ?></small></span></div>
  <?php endforeach; ?>
</div>

<?php if ($isAppt):
  $tabParams = array_filter(['q' => $filters['q'], 'service' => $filters['service'], 'type' => $filters['type'], 'sort' => $filters['sort'] === 'old' ? 'old' : '']);
  $tabHref = fn(string $st) => url('action=appointments' . ($st !== '' ? '&status=' . $st : '') . ($tabParams ? '&' . http_build_query($tabParams) : ''));
?>
<nav class="status-tabs" aria-label="Appointment status">
  <?php foreach ($cfg['statuses'] as $k => $label): ?>
    <a href="<?= h($tabHref($k)) ?>" class="status-tab <?= $filters['status'] === $k ? 'on' : '' ?>"><?= h($label) ?> <span class="tab-count"><?= (int) ($counts[$k] ?? 0) ?></span></a>
  <?php endforeach; ?>
  <a href="<?= h($tabHref('')) ?>" class="status-tab <?= $filters['status'] === '' ? 'on' : '' ?>">All Appointments <span class="tab-count"><?= (int) $counts['total'] ?></span></a>
</nav>
<?php endif; ?>

<form method="get" action="<?= h(ADMIN_URL . 'index.php') ?>" class="filter-bar">
  <input type="hidden" name="action" value="<?= h($cfg['plural']) ?>">
  <input type="search" name="q" data-autosearch value="<?= h($filters['q']) ?>" placeholder="Search name, mobile, email or message">
  <?php if ($isAppt): ?>
    <input type="hidden" name="status" value="<?= h($filters['status']) ?>">
  <?php else: ?>
  <select name="status" aria-label="Status" data-autosubmit>
    <option value="">All statuses</option>
    <?php foreach ($cfg['statuses'] as $k => $label): ?>
      <option value="<?= h($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>
  <?php if ($isAppt): ?>
    <select name="type" aria-label="Type" data-autosubmit>
      <option value="">All types</option>
      <?php foreach ($cfg['types'] as $t): ?>
        <option value="<?= h($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="service" aria-label="Service" data-autosubmit>
      <option value="">All services</option>
      <?php foreach ($cfg['services'] as $s): ?>
        <option value="<?= h($s) ?>" <?= $filters['service'] === $s ? 'selected' : '' ?>><?= h($s) ?></option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>
  <select name="sort" aria-label="Sort" data-autosubmit>
    <option value="new" <?= $filters['sort'] === 'new' ? 'selected' : '' ?>>Newest first</option>
    <option value="old" <?= $filters['sort'] === 'old' ? 'selected' : '' ?>>Oldest first</option>
  </select>
  <noscript><button type="submit" class="btn btn-primary btn-sm">Filter</button></noscript>
  <?php if ($filters['q'] !== '' || $filters['status'] !== '' || $filters['service'] !== '' || ($filters['type'] ?? '') !== '' || $filters['sort'] !== 'new'): ?>
    <a class="btn btn-outline btn-sm" href="<?= h(url('action=' . $cfg['plural'])) ?>">Reset</a>
  <?php endif; ?>
</form>

<div class="table-card">
  <div class="table-scroll">
    <table class="table">
      <thead>
        <tr>
          <th>No</th><th>Name</th><th>Number</th>
          <th><?= $isAppt ? 'Service' : 'Email' ?></th>
          <?php if ($isAppt): ?><th>Type</th><?php endif; ?>
          <th>Received Date</th>
          <th>Status</th><th class="t-right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$result['rows']): ?>
          <tr><td colspan="<?= $isAppt ? 8 : 7 ?>" class="empty">No <?= h($cfg['plural']) ?> found.</td></tr>
        <?php endif; ?>
        <?php foreach ($result['rows'] as $i => $r): ?>
          <tr>
            <td><?= ($result['page'] - 1) * 10 + $i + 1 ?></td>
            <td><strong><?= h($r['name']) ?></strong></td>
            <td><?= h($r['phone']) ?></td>
            <td><?= h($isAppt ? $r['service'] : ($r['email'] ?: '—')) ?></td>
            <?php if ($isAppt): ?><td><?= h($r['appointment_type'] ?: '—') ?></td><?php endif; ?>
            <td class="nowrap"><?= h(fmt_date($r['created_at'], 'd M Y, h:i A')) ?></td>
            <td>
              <form method="post" action="<?= h(url('action=' . $cfg['key'] . '_status')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="qs" value="<?= h($listQuery) ?>">
                <select name="status" class="status-select <?= status_class($r['status']) ?>" data-autosubmit aria-label="Change status">
                  <?php foreach ($cfg['statuses'] as $k => $label): ?>
                    <option value="<?= h($k) ?>" <?= $r['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td class="t-right nowrap">
              <a class="btn btn-primary btn-sm" href="<?= h(url('action=' . $cfg['key'] . '&id=' . (int) $r['id'])) ?>">View</a>
              <form method="post" action="<?= h(url('action=' . $cfg['key'] . '_delete')) ?>" class="inline" data-confirm="Delete this <?= h($cfg['noun']) ?>? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="qs" value="<?= h($listQuery) ?>">
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
