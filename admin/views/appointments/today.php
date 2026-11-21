<?php
/** @var array $rows @var array $modes */
?>
<?php $collected = array_sum(array_map(static fn($r) => (float) $r['payment_amount'], $done)); ?>
<div class="stat-row">
  <div class="stat-tile"><span class="stat-icon"><?= icon('calendar', 20) ?></span><span><b><?= count($rows) + count($done) ?></b><small>Total today</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('clock', 20) ?></span><span><b><?= count($rows) ?></b><small>Pending today</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('check', 20) ?></span><span><b><?= count($done) ?></b><small>Completed today</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('check', 20) ?></span><span><b>₹<?= h(number_format($collected, 2)) ?></b><small>Collected today</small></span></div>
</div>

<nav class="status-tabs" aria-label="Today view">
  <a href="<?= h(url('action=appointments_today')) ?>" class="status-tab <?= $tab === 'pending' ? 'on' : '' ?>">Pending <span class="tab-count"><?= count($rows) ?></span></a>
  <a href="<?= h(url('action=appointments_today&view=completed')) ?>" class="status-tab <?= $tab === 'completed' ? 'on' : '' ?>">Completed <span class="tab-count"><?= count($done) ?></span></a>
</nav>

<?php if ($tab === 'completed'): ?>
<div class="table-card">
  <div class="table-scroll">
    <table class="table">
      <thead>
        <tr><th>No</th><th>Completed at</th><th>Name</th><th>Number</th><th>Service</th><th>Type</th><th class="t-right">Payment</th></tr>
      </thead>
      <tbody>
        <?php if (!$done): ?>
          <tr><td colspan="7" class="empty">No appointments completed today yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($done as $i => $r): ?>
          <tr>
            <td class="nowrap"><?= $i + 1 ?></td>
            <td class="nowrap"><?= h(fmt_date($r['completed_at'], 'h:i A')) ?></td>
            <td><a class="link" href="<?= h(url('action=appointment&id=' . (int) $r['id'])) ?>"><strong><?= h($r['name']) ?></strong></a></td>
            <td><?= h($r['phone']) ?></td>
            <td><?= h($r['service']) ?></td>
            <td><?= h($r['appointment_type'] ?: '—') ?></td>
            <td class="t-right nowrap">₹<?= h(number_format((float) $r['payment_amount'], 2)) ?> · <?= h($r['payment_mode'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php else: ?>
<div class="table-card">
  <div class="table-scroll">
    <table class="table">
      <thead>
        <tr><th>No</th><th>Time</th><th>Name</th><th>Number</th><th>Service</th><th>Type</th><th>Status</th><th class="t-right">Complete &amp; payment</th></tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="8" class="empty">No appointments scheduled for today.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $i => $r): ?>
          <tr>
            <td class="nowrap"><?= $i + 1 ?></td>
            <td class="nowrap"><strong><?= h(fmt_date($r['scheduled_at'], 'h:i A')) ?></strong></td>
            <td><a class="link" href="<?= h(url('action=appointment&id=' . (int) $r['id'])) ?>"><strong><?= h($r['name']) ?></strong></a></td>
            <td><a class="link" href="tel:<?= h(preg_replace('/[^0-9+]/', '', $r['phone'])) ?>"><?= h($r['phone']) ?></a></td>
            <td><?= h($r['service']) ?></td>
            <td><?= h($r['appointment_type'] ?: '—') ?></td>
            <td class="nowrap"><span class="pill <?= status_class($r['status']) ?>"><?= h(Appointment::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
            <td class="t-right nowrap">
              <form method="post" action="<?= h(url('action=appointment_complete')) ?>" class="pay-form" data-confirm="Mark as completed and save this payment?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <input type="hidden" name="return" value="today">
                <input type="number" name="payment_amount" min="0" max="99999999" step="0.01" required placeholder="₹ Amount" aria-label="Payment amount">
                <select name="payment_mode" required aria-label="Payment mode">
                  <?php foreach ($modes as $m): ?><option><?= h($m) ?></option><?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Complete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
