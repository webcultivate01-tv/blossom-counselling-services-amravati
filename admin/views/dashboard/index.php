<?php
if (!function_exists('dash_money')) {
    function dash_money(float $n): string
    {
        return '₹' . number_format($n, $n == floor($n) ? 0 : 2);
    }

    /** Compact money for chart axes: 1.2K, 3.4L. */
    function dash_short(float $n): string
    {
        $a = abs($n);
        if ($a >= 10000000) return round($n / 10000000, 1) . 'Cr';
        if ($a >= 100000) return round($n / 100000, 1) . 'L';
        if ($a >= 1000) return round($n / 1000, 1) . 'K';
        return (string) round($n);
    }

    /**
     * Donut chart. $items = [label => value]; $colors = [label => hex] or list. $centerTop/$centerSub = text inside the ring.
     */
    function dash_donut(array $items, array $colors, string $centerTop, string $centerSub, bool $money = false): string
    {
        $total = array_sum($items);
        $keys = array_keys($items);
        $svg = '<svg class="donut" viewBox="0 0 42 42" role="img"><circle class="donut-track" cx="21" cy="21" r="15.9155"/>';
        $offset = 25; // start at 12 o'clock
        if ($total > 0) {
            foreach ($items as $label => $v) {
                if ($v <= 0) continue;
                $pct = $v / $total * 100;
                $c = $colors[$label] ?? $colors[array_search($label, $keys, true) % count($colors)] ?? '#999';
                $svg .= '<circle class="donut-seg" cx="21" cy="21" r="15.9155" stroke="' . h($c) . '" stroke-dasharray="' . round($pct, 3) . ' ' . round(100 - $pct, 3) . '" stroke-dashoffset="' . round($offset, 3) . '"><title>'
                    . h($label) . ': ' . h($money ? dash_money((float) $v) : (string) $v) . ' (' . round($pct) . '%)</title></circle>';
                $offset -= $pct;
            }
        }
        $svg .= '<text x="21" y="21.5" class="donut-top">' . h($centerTop) . '</text><text x="21" y="26.2" class="donut-sub">' . h($centerSub) . '</text></svg>';
        return $svg;
    }

    function dash_legend(array $items, array $colors, bool $money = false): string
    {
        $total = array_sum($items);
        $keys = array_keys($items);
        $out = '<ul class="legend">';
        foreach ($items as $label => $v) {
            $c = $colors[$label] ?? $colors[array_search($label, $keys, true) % count($colors)] ?? '#999';
            $out .= '<li><i style="background:' . h($c) . '"></i><span>' . h($label) . '</span><b>'
                . h($money ? dash_money((float) $v) : (string) $v) . '</b><em>' . ($total > 0 ? round($v / $total * 100) : 0) . '%</em></li>';
        }
        return $out . '</ul>';
    }

    /** Small ring gauge (percentage). */
    function dash_ring(float $pct, string $color, string $label, string $sub): string
    {
        $pct = max(0, min(100, $pct));
        return '<div class="ring-card"><svg class="donut ring" viewBox="0 0 42 42"><circle class="donut-track" cx="21" cy="21" r="15.9155"/>'
            . '<circle class="donut-seg" cx="21" cy="21" r="15.9155" stroke="' . h($color) . '" stroke-dasharray="' . round($pct, 2) . ' ' . round(100 - $pct, 2) . '" stroke-dashoffset="25" stroke-linecap="round"/>'
            . '<text x="21" y="23.6" class="donut-top ring-num">' . round($pct) . '%</text></svg>'
            . '<div><b>' . h($label) . '</b><small>' . h($sub) . '</small></div></div>';
    }

    /** Area + line chart over a daily series. */
    function dash_area(array $series, string $color, bool $money): string
    {
        $n = count($series);
        $max = max(1, max(array_column($series, 'value')));
        $W = 600; $H = 180; $pad = 6;
        $pts = [];
        $meta = [];
        foreach ($series as $i => $p) {
            $x = $n > 1 ? $i / ($n - 1) * $W : 0;
            $y = $H - $pad - ($p['value'] / $max) * ($H - $pad * 2);
            $pts[] = round($x, 1) . ',' . round($y, 1);
            $meta[] = ['l' => $p['label'], 'v' => $money ? dash_money($p['value']) : (string) (int) $p['value'], 'x' => round($x / $W * 100, 2), 'y' => round($y / $H * 100, 2)];
        }
        $line = 'M' . implode(' L', $pts);
        $area = $line . " L$W,$H L0,$H Z";
        $id = 'g' . substr(md5($color . $max), 0, 6);
        $svg = '<div class="chart-wrap"><div class="y-axis"><span>' . ($money ? '₹' : '') . dash_short($max) . '</span><span>' . ($money ? '₹' : '') . dash_short($max / 2) . '</span><span>0</span></div>'
            . '<div class="area-box" data-points="' . h(json_encode($meta)) . '" style="--c:' . $color . '"><svg viewBox="0 0 ' . $W . ' ' . $H . '" preserveAspectRatio="none" class="area-svg">'
            . '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="' . $color . '" stop-opacity=".35"/><stop offset="100%" stop-color="' . $color . '" stop-opacity=".02"/></linearGradient></defs>'
            . '<line x1="0" x2="' . $W . '" y1="' . ($H - $pad) . '" y2="' . ($H - $pad) . '" class="grid"/>'
            . '<line x1="0" x2="' . $W . '" y1="' . ($H / 2) . '" y2="' . ($H / 2) . '" class="grid"/>'
            . '<line x1="0" x2="' . $W . '" y1="' . $pad . '" y2="' . $pad . '" class="grid"/>'
            . '<path d="' . $area . '" fill="url(#' . $id . ')"/><path d="' . $line . '" fill="none" stroke="' . $color . '" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linejoin="round"/></svg>'
            . '<div class="hv-line"></div><div class="hv-dot"></div><div class="hv-tip"></div>';
        $svg .= '</div></div><div class="x-axis"><span>' . h($series[0]['label']) . '</span><span>' . h($series[(int) floor($n / 2)]['label']) . '</span><span>' . h($series[$n - 1]['label']) . '</span></div>';
        return $svg;
    }

    /** Vertical bar chart (CSS). */
    function dash_bars(array $series, string $color, bool $money): string
    {
        $max = max(1, max(array_column($series, 'value')));
        $out = '<div class="bars">';
        foreach ($series as $p) {
            $pct = $p['value'] / $max * 100;
            $tip = $p['label'] . ': ' . ($money ? dash_money($p['value']) : (int) $p['value']);
            $out .= '<div class="bar-col" data-tip="' . h($tip) . '"><em>' . ($p['value'] > 0 ? ($money ? dash_short($p['value']) : (int) $p['value']) : '') . '</em>'
                . '<span class="bar" style="height:' . round(max($pct, $p['value'] > 0 ? 3 : 0), 1) . '%;background:' . $color . '"></span><small>' . h($p['label']) . '</small></div>';
        }
        return $out . '</div>';
    }

    /** Horizontal ranked bars. */
    function dash_hbars(array $items, array $colors, bool $money): string
    {
        if (!$items) return '<p class="empty">No data yet.</p>';
        $max = max($items);
        $out = '<ul class="hbars">';
        $i = 0;
        foreach ($items as $label => $v) {
            $c = $colors[$i++ % count($colors)];
            $out .= '<li><div class="hb-top"><span>' . h($label) . '</span><b>' . h($money ? dash_money((float) $v) : (string) (int) $v) . '</b></div>'
                . '<div class="hb-track"><span style="width:' . round($v / $max * 100, 1) . '%;background:' . $c . '"></span></div></li>';
        }
        return $out . '</ul>';
    }
}

$PAL = ['#6f73d2'];

// ---- derived numbers ----
$apptTotal = (int) $appointmentCounts['total'];
$completed = (int) $appointmentCounts['completed'];
$cancelledAll = (int) $appointmentCounts['cancelled'] + (int) $appointmentCounts['not_interested'];
$openCount = 0;
foreach (Appointment::OPEN as $s) $openCount += (int) $appointmentCounts[$s];
$completionRate = $apptTotal ? $completed / $apptTotal * 100 : 0;
$cancelRate = $apptTotal ? $cancelledAll / $apptTotal * 100 : 0;
$paidRate = $completed ? min(100, $bookingStats['paid_appointments'] / $completed * 100) : 0;
$avgTicket = $bookingStats['paid_appointments'] ? $revenue['lifetime'] / $bookingStats['paid_appointments'] : 0;
$change = $last30['change'];

$statusColors = [
    'pending' => '#f0b35a', 'contacted' => '#3fb0a3', 'confirmed' => '#6f73d2', 'rescheduled' => '#a5a8e8',
    'completed' => '#34a874', 'cancelled' => '#e0707a', 'not_interested' => '#94a3b8',
];
$apptStatus = [];
foreach (Appointment::STATUSES as $k => $label) {
    if (!empty($appointmentCounts[$k])) $apptStatus[$label] = (int) $appointmentCounts[$k];
}
$apptStatusColors = [];
foreach (Appointment::STATUSES as $k => $label) $apptStatusColors[$label] = $statusColors[$k];

$modeColors = ['Cash' => '#34a874', 'UPI' => '#6f73d2', 'Card' => '#3fb0a3', 'Bank Transfer' => '#f0b35a', 'Other' => '#94a3b8'];
$typeColors = ['Home Visit' => '#6f73d2', 'School & College Visit' => '#3fb0a3', 'Office Visit' => '#f0b35a', 'Centre Visit' => '#a5a8e8', 'Other' => '#94a3b8'];
?>
<h1 class="page-greeting">Welcome back, <?= h($admin['name']) ?></h1>

<!-- ===== Row 1: counts ===== -->
<div class="count-row">
  <?php
  $cards = [
      ['calendar', 'Total Appointments', $appointmentCounts['total'], 'index.php?action=appointments'],
      ['clock', "Today's Appointments", $todayCount, 'index.php?action=appointments_today'],
      ['clock', 'Pending Appointments', $appointmentCounts['pending'], 'index.php?action=appointments&status=pending'],
      ['calendar', 'Completed', $appointmentCounts['completed'], 'index.php?action=appointments&status=completed'],
      ['blog', 'Published Articles', $blogCounts['published'], 'index.php?action=blogs'],
      ['image', 'Gallery Items', $galleryCounts['total'], 'index.php?action=gallery'],
  ];
  foreach ($cards as [$ico, $label, $num, $href]): ?>
    <a class="count-tile" href="<?= h($href) ?>">
      <span class="count-icon"><?= icon($ico, 22) ?></span>
      <b><?= (int) $num ?></b>
      <small><?= h($label) ?></small>
    </a>
  <?php endforeach; ?>
</div>

<!-- ===== Row 2: earnings ===== -->
<div class="kpi-grid">
  <a class="kpi" href="<?= h(url('action=payments&from=' . date('Y-m-d') . '&to=' . date('Y-m-d'))) ?>">
    <small>Today's earnings</small><b><?= h(dash_money($revenue['today'])) ?></b>
    <span><?= (int) $revenue['today_count'] ?> payment<?= $revenue['today_count'] == 1 ? '' : 's' ?> · <?= (int) $todayCount ?> appointment<?= $todayCount == 1 ? '' : 's' ?> due</span>
  </a>
  <a class="kpi" href="<?= h(url('action=payments&from=' . date('Y-m-d', strtotime('-6 day')) . '&to=' . date('Y-m-d'))) ?>">
    <small>Last 7 days</small><b><?= h(dash_money($revenue['week'])) ?></b>
    <span><?= (int) $bookingStats['week'] ?> new bookings</span>
  </a>
  <a class="kpi" href="<?= h(url('action=payments&from=' . date('Y-m-d', strtotime('-29 day')) . '&to=' . date('Y-m-d'))) ?>">
    <small>Last 30 days</small><b><?= h(dash_money($last30['amount'])) ?></b>
    <span>
      <?php if ($change === null): ?>
        <?= $last30['amount'] > 0 ? 'First earnings period' : 'No earnings yet' ?>
      <?php else: ?>
        <strong class="<?= $change >= 0 ? 'up' : 'down' ?>"><?= $change >= 0 ? '▲' : '▼' ?> <?= abs(round($change)) ?>%</strong> vs previous 30 days
      <?php endif; ?>
    </span>
  </a>
  <a class="kpi" href="<?= h(url('action=payments')) ?>">
    <small>Lifetime earnings</small><b><?= h(dash_money($revenue['lifetime'])) ?></b>
    <span><?= (int) $revenue['lifetime_count'] ?> transactions · avg <?= h(dash_money($avgTicket)) ?>/client</span>
  </a>
</div>

<!-- ===== Daily earnings + payment modes ===== -->
<div class="grid-2-1">
  <section class="panel chart-panel">
    <div class="panel-head">Earnings — last 30 days <a class="link" href="<?= h(url('action=reports&type=daywise')) ?>">Day-wise report</a></div>
    <div class="chart-body"><?= dash_area($dailyEarnings, '#6f73d2', true) ?></div>
  </section>
  <section class="panel chart-panel">
    <div class="panel-head">Earnings by payment mode</div>
    <div class="chart-body donut-body">
      <?php if (!$byMode): ?><p class="empty">No payments yet.</p><?php else: ?>
        <?= dash_donut($byMode, $modeColors, dash_short($revenue['lifetime']), 'Lifetime', true) ?>
        <?= dash_legend($byMode, $modeColors, true) ?>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- ===== Monthly earnings + bookings last 30 days ===== -->
<div class="two-col">
  <section class="panel chart-panel">
    <div class="panel-head">Monthly earnings — last 12 months</div>
    <div class="chart-body"><?= dash_bars($monthlyEarnings, '#6f73d2', true) ?></div>
  </section>
  <section class="panel chart-panel">
    <div class="panel-head">New bookings — last 30 days <span class="head-note"><?= (int) $bookingStats['d30'] ?> total</span></div>
    <div class="chart-body"><?= dash_area($dailyBookings, '#3fb0a3', false) ?></div>
  </section>
</div>

<!-- ===== Rings: business health ===== -->
<div class="ring-row">
  <?= dash_ring($completionRate, '#34a874', 'Completion rate', $completed . ' of ' . $apptTotal . ' appointments done') ?>
  <?= dash_ring($apptTotal ? $openCount / $apptTotal * 100 : 0, '#f0b35a', 'Open / upcoming', $openCount . ' still need action') ?>
  <?= dash_ring($cancelRate, '#e0707a', 'Cancelled / not interested', $cancelledAll . ' lost appointments') ?>
  <?= dash_ring($paidRate, '#6f73d2', 'Payment collected', $bookingStats['paid_appointments'] . ' of ' . $completed . ' completed paid') ?>
</div>

<!-- ===== Appointment status / visit type ===== -->
<div class="two-col">
  <section class="panel chart-panel">
    <div class="panel-head">Appointments by status <a class="link" href="<?= h(url('action=appointments')) ?>">View</a></div>
    <div class="chart-body donut-body">
      <?php if (!$apptStatus): ?><p class="empty">No appointments yet.</p><?php else: ?>
        <?= dash_donut($apptStatus, $apptStatusColors, (string) $apptTotal, 'Bookings') ?>
        <?= dash_legend($apptStatus, $apptStatusColors) ?>
      <?php endif; ?>
    </div>
  </section>
  <section class="panel chart-panel">
    <div class="panel-head">Visit type</div>
    <div class="chart-body donut-body">
      <?php if (!$apptByType): ?><p class="empty">No appointments yet.</p><?php else: ?>
        <?= dash_donut($apptByType, $typeColors, (string) array_sum($apptByType), 'Bookings') ?>
        <?= dash_legend($apptByType, $typeColors) ?>
      <?php endif; ?>
    </div>
  </section>
</div>

<!-- ===== Services ===== -->
<div class="two-col">
  <section class="panel chart-panel">
    <div class="panel-head">Most booked services</div>
    <div class="chart-body"><?= dash_hbars($apptByService, $PAL, false) ?></div>
  </section>
  <section class="panel chart-panel">
    <div class="panel-head">Earnings by service</div>
    <div class="chart-body"><?= dash_hbars($byService, ['#3fb0a3'], true) ?></div>
  </section>
</div>

<div>
  <section class="panel">
    <div class="panel-head">Recent appointments <a class="link" href="<?= h(url('action=appointments')) ?>">View all</a></div>
    <?php if (!$recentAppointments): ?>
      <p class="empty">No appointments yet.</p>
    <?php else: ?>
      <ul class="recent">
        <?php foreach ($recentAppointments as $r): ?>
          <li>
            <a href="<?= h(url('action=appointment&id=' . (int) $r['id'])) ?>">
              <div><strong><?= h($r['name']) ?></strong><small><?= h($r['service']) ?> · <?= h(fmt_date($r['created_at'], 'd M Y')) ?></small></div>
              <span class="pill <?= status_class($r['status']) ?>"><?= h(Appointment::STATUSES[$r['status']] ?? $r['status']) ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
</div>

<script>
(function () {
  document.querySelectorAll('.area-box[data-points]').forEach(function (box) {
    var pts = JSON.parse(box.getAttribute('data-points'));
    var line = box.querySelector('.hv-line'), dot = box.querySelector('.hv-dot'), tip = box.querySelector('.hv-tip');
    function show(clientX) {
      var r = box.getBoundingClientRect();
      var f = Math.min(1, Math.max(0, (clientX - r.left) / r.width));
      var p = pts[Math.round(f * (pts.length - 1))];
      line.style.left = dot.style.left = p.x + '%';
      dot.style.top = p.y + '%';
      tip.innerHTML = '<small>' + p.l + '</small><b>' + p.v + '</b>';
      tip.style.left = p.x + '%';
      tip.style.top = p.y + '%';
      tip.className = 'hv-tip' + (p.x > 70 ? ' left' : '') + (p.y < 28 ? ' below' : '');
      box.classList.add('active');
    }
    box.addEventListener('mousemove', function (e) { show(e.clientX); });
    box.addEventListener('touchstart', function (e) { show(e.touches[0].clientX); }, { passive: true });
    box.addEventListener('touchmove', function (e) { show(e.touches[0].clientX); }, { passive: true });
    box.addEventListener('mouseleave', function () { box.classList.remove('active'); });
  });
})();
</script>
