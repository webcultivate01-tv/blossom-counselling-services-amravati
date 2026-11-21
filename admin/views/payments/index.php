<?php
/** @var array $filters @var array $result @var array $summary @var array $revenue @var array $modes @var int $perPage */
$money = static fn ($v): string => '₹' . number_format((float) $v, 2);
$active = array_filter(['from' => $filters['from'], 'to' => $filters['to'], 'mode' => $filters['mode'], 'kind' => $filters['kind'], 'q' => $filters['q']], static fn ($v) => $v !== '');
$hasFilter = (bool) $active;
$pager = ['page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total'], 'per' => $perPage];
$pagerQuery = 'action=payments' . ($active ? '&' . http_build_query($active) : '');
$start = ($result['page'] - 1) * $perPage;

$exportBase = array_filter(['from' => $filters['from'], 'to' => $filters['to'], 'mode' => $filters['mode'], 'q' => $filters['q']], static fn ($v) => $v !== '');
$exportUrl = static fn (string $type, string $format): string => url('action=report_export&type=' . $type . '&format=' . $format . '&preview=1' . ($exportBase ? '&' . http_build_query($exportBase) : ''));

$d = static fn (string $s): string => date('Y-m-d', strtotime($s));
$quick = [
    'Today' => [$d('today'), $d('today')],
    'Yesterday' => [$d('yesterday'), $d('yesterday')],
    'Last 7 days' => [$d('-6 days'), $d('today')],
    'This month' => [date('Y-m-01'), $d('today')],
    'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
];
$quickHref = static fn (array $r): string => url('action=payments&' . http_build_query(['from' => $r[0], 'to' => $r[1]] + array_diff_key($active, ['from' => 1, 'to' => 1])));
?>
<div class="stat-row">
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($revenue['today'])) ?></b><small>Today's earnings</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($revenue['week'])) ?></b><small>Last 7 days</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($revenue['month'])) ?></b><small>This month</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($revenue['lifetime'])) ?></b><small>Lifetime earnings</small></span></div>
</div>

<form method="get" action="<?= h(ADMIN_URL . 'index.php') ?>" class="card report-form pay-filter">
  <input type="hidden" name="action" value="payments">
  <div class="report-grid">
    <div class="form-group"><label for="pQ">Search</label><input type="search" id="pQ" name="q" value="<?= h($filters['q']) ?>" placeholder="Type client name or mobile…" autocomplete="off"></div>
    <div class="form-group"><label for="pFrom">From</label><input type="date" id="pFrom" name="from" value="<?= h($filters['from']) ?>"></div>
    <div class="form-group"><label for="pTo">To</label><input type="date" id="pTo" name="to" value="<?= h($filters['to']) ?>"></div>
    <div class="form-group">
      <label for="pMode">Payment mode</label>
      <select id="pMode" name="mode">
        <option value="">All modes</option>
        <?php foreach ($modes as $m): ?><option <?= $filters['mode'] === $m ? 'selected' : '' ?>><?= h($m) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="pKind">Type</label>
      <select id="pKind" name="kind">
        <option value="">Payments &amp; refunds</option>
        <?php foreach (Payment::KINDS as $k => $label): ?><option value="<?= h($k) ?>" <?= $filters['kind'] === $k ? 'selected' : '' ?>><?= h($label) ?>s</option><?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="report-actions">
    <button type="submit" class="btn btn-primary"><?= icon('search', 16) ?> Apply filters</button>
    <?php if ($hasFilter): ?><a class="btn btn-outline" href="<?= h(url('action=payments')) ?>">Reset</a><?php endif; ?>
  </div>
  <div class="quick-dates">
    <span class="muted small">Quick range:</span>
    <?php foreach ($quick as $label => $r): ?>
      <a class="chip chip-sm" href="<?= h($quickHref($r)) ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </div>

</form>

<div class="sum-strip">
  <span><b><?= (int) $summary['count'] ?></b> entr<?= $summary['count'] === 1 ? 'y' : 'ies' ?><?= $hasFilter ? ' match' : ' in total' ?></span>
  <span>Received <b class="money-pos"><?= h($money($summary['paid'])) ?></b></span>
  <span>Refunded <b class="money-neg"><?= h($money($summary['refunded'])) ?></b></span>
  <span>Net <b><?= h($money($summary['net'])) ?></b></span>
</div>

<div class="table-card">
  <div class="panel-head"><span>All payments</span><span class="muted small">Newest first</span></div>
  <div class="table-scroll">
    <table class="table">
      <thead>
        <tr><th>No.</th><th>Name</th><th>Date</th><th>Mobile</th><th>Service</th><th>Mode</th><th class="t-right">Amount</th></tr>
      </thead>
      <tbody>
        <?php if (!$result['rows']): ?><tr><td colspan="7" class="empty">No payments found<?= $hasFilter ? ' for these filters' : ' yet' ?>.</td></tr><?php endif; ?>
        <?php foreach ($result['rows'] as $i => $r): $refund = $r['kind'] === 'refund'; ?>
          <tr>
            <td><?= $start + $i + 1 ?></td>
            <td><strong><?= h($r['name']) ?></strong></td>
            <td class="nowrap"><?= h(fmt_date($r['paid_at'])) ?></td>
            <td class="nowrap"><?= h($r['phone'] ?: '—') ?></td>
            <td><?= h($r['service'] ?: '—') ?></td>
            <td class="nowrap"><?= h($r['mode']) ?><?= $refund ? ' <span class="pill st-cancelled">Refund</span>' : '' ?></td>
            <td class="t-right nowrap <?= $refund ? 'money-neg' : 'money-pos' ?>"><strong><?= $refund ? '−' : '' ?><?= h($money($r['amount'])) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php require __DIR__ . '/../layout/pagination.php'; ?>
</div>

<script>
(function () {
  var f = document.querySelector('.pay-filter'), q = document.getElementById('pQ'), t;
  if (!f || !q) return;
  if (q.value) { q.focus(); q.setSelectionRange(q.value.length, q.value.length); }
  q.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { f.submit(); }, 450); });
  f.querySelectorAll('select, input[type=date]').forEach(function (el) { el.addEventListener('change', function () { f.submit(); }); });
})();
</script>
