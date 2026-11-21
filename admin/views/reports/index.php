<?php
/** @var string $type @var array $filters @var array $cols @var ?array $preview @var int $previewLimit */
$colMeta = Report::COLUMNS[$type];
$statuses = Report::statuses($type);
$dateFields = Report::DATE_FIELDS[$type];
$isInq = $type === 'inquiries';
$isMoney = in_array($type, Report::MONEY_TYPES, true);
$blurbs = [
    'appointments' => 'One row per appointment — includes completed ones with their payment details.',
    'clients' => 'One row per client (matched by mobile number). Shows how many appointments they booked and what they paid.',
    'inquiries' => 'Messages received through the contact form.',
    'payments' => 'Every payment and refund received: who paid, when, how much and by which mode. Refunds count as negative.',
    'daywise' => 'One row per day: transactions, per-mode collections and net earnings. Use the date range for a week, month or year.',
];
$quick = [
    'Today' => [date('Y-m-d'), date('Y-m-d')],
    'This month' => [date('Y-m-01'), date('Y-m-d')],
    'Last month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))],
    'This year' => [date('Y-01-01'), date('Y-m-d')],
];
?>
<nav class="status-tabs" aria-label="Report type">
  <?php foreach (Report::TYPES as $k => $label): ?>
    <a href="<?= h(url('action=reports&type=' . $k)) ?>" class="status-tab <?= $type === $k ? 'on' : '' ?>"><?= h($label) ?> report</a>
  <?php endforeach; ?>
</nav>

<form method="get" action="<?= h(ADMIN_URL . 'index.php') ?>" id="reportForm" class="card report-form">
  <input type="hidden" name="action" id="rAction" value="reports">
  <input type="hidden" name="type" value="<?= h($type) ?>">
  <input type="hidden" name="preview" value="1">
  <input type="hidden" name="format" id="rFormat" value="xlsx" disabled>

  <div class="card-title">
    <div>
      <h4><?= h(Report::TYPES[$type]) ?> report</h4>
      <p><?= h($blurbs[$type]) ?></p>
    </div>
  </div>

  <div class="report-grid">
    <div class="form-group">
      <label for="rField">Date based on</label>
      <select id="rField" name="field">
        <?php foreach ($dateFields as $k => [$label]): ?>
          <option value="<?= h($k) ?>" <?= $filters['field'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="rFrom">From</label>
      <input type="date" id="rFrom" name="from" value="<?= h($filters['from']) ?>">
    </div>
    <div class="form-group">
      <label for="rTo">To</label>
      <input type="date" id="rTo" name="to" value="<?= h($filters['to']) ?>">
    </div>
    <?php if (!$isMoney): ?>
    <div class="form-group">
      <label for="rStatus"><?= $type === 'clients' ? 'Appointment status' : 'Status' ?></label>
      <select id="rStatus" name="status">
        <option value="">All</option>
        <?php foreach ($statuses as $k => $label): ?>
          <option value="<?= h($k) ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php else: ?>
    <div class="form-group">
      <label for="rMode">Payment mode</label>
      <select id="rMode" name="mode">
        <option value="">All modes</option>
        <?php foreach (Appointment::PAYMENT_MODES as $m): ?>
          <option <?= $filters['mode'] === $m ? 'selected' : '' ?>><?= h($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <?php if (!$isInq && !$isMoney): ?>
    <div class="form-group">
      <label for="rService">Service</label>
      <select id="rService" name="service">
        <option value="">All services</option>
        <?php foreach (Appointment::SERVICES as $s): ?>
          <option <?= $filters['service'] === $s ? 'selected' : '' ?>><?= h($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="rVisit">Visit type</label>
      <select id="rVisit" name="visit">
        <option value="">All types</option>
        <?php foreach (Appointment::TYPES as $t): ?>
          <option <?= $filters['visit'] === $t ? 'selected' : '' ?>><?= h($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group">
      <label for="rQ">Search</label>
      <input type="search" id="rQ" name="q" value="<?= h($filters['q']) ?>" placeholder="<?= $isMoney ? 'Payer name, mobile or reference' : 'Name, mobile or email' ?>">
    </div>
    <div class="form-group">
      <label for="rSort">Sort by</label>
      <select id="rSort" name="sort">
        <?php foreach (Report::SORTS[$type] as $k => $label): ?>
          <option value="<?= h($k) ?>" <?= $filters['sort'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="quick-dates">
    <span class="muted small">Quick range:</span>
    <?php foreach ($quick as $label => [$a, $b]): ?>
      <button type="button" class="chip chip-sm" data-range="<?= h($a . '|' . $b) ?>"><?= h($label) ?></button>
    <?php endforeach; ?>
    <button type="button" class="chip chip-sm" data-range="|">Clear dates</button>
  </div>

  <div class="report-cols">
    <div class="report-cols-head">
      <div class="label-caps">Columns to include</div>
      <div>
        <button type="button" class="link-btn" data-cols="all">Select all</button> ·
        <button type="button" class="link-btn" data-cols="none">Clear</button> ·
        <button type="button" class="link-btn" data-cols="default">Default</button>
      </div>
    </div>
    <div class="col-grid" id="colGrid" data-default="<?= h(implode(',', Report::DEFAULT_COLUMNS[$type])) ?>">
      <?php foreach ($colMeta as $k => [$label]): ?>
        <label class="col-check"><input type="checkbox" name="cols[]" value="<?= h($k) ?>" <?= in_array($k, $cols, true) ? 'checked' : '' ?>> <span><?= h($label) ?></span></label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="report-actions">
    <button type="submit" class="btn btn-outline" data-act="reports"><?= icon('eye', 16) ?> Preview</button>
    <button type="submit" class="btn btn-primary" data-act="report_export" data-format="xlsx"><?= icon('blog', 16) ?> Download Excel</button>
    <button type="submit" class="btn btn-primary" data-act="report_export" data-format="pdf" data-newtab="1"><?= icon('blog', 16) ?> Download PDF</button>
    <a class="btn btn-outline" href="<?= h(url('action=reports&type=' . $type)) ?>">Reset</a>
  </div>
</form>

<?php if ($preview !== null): ?>
<div class="table-card">
  <div class="panel-head">
    <span>Preview</span>
    <span class="muted small"><?= (int) $preview['count'] ?> record<?= $preview['count'] === 1 ? '' : 's' ?> found<?= $preview['count'] > $previewLimit ? ' · showing first ' . (int) $previewLimit : '' ?></span>
  </div>
  <div class="table-scroll">
    <table class="table">
      <thead>
        <tr>
          <?php foreach ($preview['headers'] as $i => $hd): ?>
            <th class="<?= in_array($preview['kinds'][$i], ['money', 'count'], true) ? 't-right' : '' ?>"><?= h($hd) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (!$preview['rows']): ?>
          <tr><td colspan="<?= count($preview['headers']) ?>" class="empty">No records match these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($preview['rows'] as $row): ?>
          <tr>
            <?php foreach ($row as $i => $v): $kind = $preview['kinds'][$i]; ?>
              <td class="<?= in_array($kind, ['money', 'count'], true) ? 't-right nowrap' : '' ?>">
                <?php if ($v === null || $v === ''): ?>—
                <?php elseif ($kind === 'money'): ?>₹<?= h(number_format((float) $v, 2)) ?>
                <?php elseif ($kind === 'status'): ?><span class="pill"><?= h($v) ?></span>
                <?php else: ?><?= h(mb_strlen((string) $v) > 80 ? mb_substr((string) $v, 0, 80) . '…' : $v) ?>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <?php if ($preview['totals'] && $preview['rows']): ?>
      <tfoot>
        <tr class="total-row">
          <?php foreach ($preview['totals'] as $i => $v): ?>
            <td class="<?= $v !== null ? 't-right nowrap' : '' ?>"><?= $i === 0 && $v === null ? 'Total (all records)' : ($v === null ? '' : ($preview['kinds'][$i] === 'money' ? '₹' . h(number_format($v, 2)) : (int) $v)) ?></td>
          <?php endforeach; ?>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
(function () {
  var form = document.getElementById('reportForm');
  var act = document.getElementById('rAction'), fmt = document.getElementById('rFormat');
  var preview = form.querySelector('[name=preview]');

  form.querySelectorAll('[data-act]').forEach(function (b) {
    b.addEventListener('click', function () {
      act.value = b.getAttribute('data-act');
      var f = b.getAttribute('data-format');
      fmt.disabled = !f; if (f) fmt.value = f;
      preview.disabled = act.value !== 'reports';
      form.target = b.getAttribute('data-newtab') ? '_blank' : '';
    });
  });

  form.addEventListener('submit', function (e) {
    if (!form.querySelector('#colGrid input:checked')) {
      e.preventDefault();
      alert('Select at least one column.');
    }
  });

  form.querySelectorAll('[data-range]').forEach(function (b) {
    b.addEventListener('click', function () {
      var p = b.getAttribute('data-range').split('|');
      form.querySelector('[name=from]').value = p[0];
      form.querySelector('[name=to]').value = p[1];
    });
  });

  var grid = document.getElementById('colGrid');
  form.querySelectorAll('[data-cols]').forEach(function (b) {
    b.addEventListener('click', function () {
      var mode = b.getAttribute('data-cols'), def = grid.getAttribute('data-default').split(',');
      grid.querySelectorAll('input').forEach(function (i) {
        i.checked = mode === 'all' ? true : mode === 'none' ? false : def.indexOf(i.value) > -1;
      });
    });
  });
})();
</script>
