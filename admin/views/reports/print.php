<?php
/** Standalone print-ready report (Save as PDF from the print dialog). @var array $report @var string $generated */
$fmt = static function ($v, string $kind): string {
    if ($v === null || $v === '') {
        return '—';
    }
    return $kind === 'money' ? number_format((float) $v, 2) : (string) $v;
};
$wide = count($report['headers']) > 6;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="robots" content="noindex,nofollow">
  <title><?= h($report['title']) ?> – <?= h(APP_NAME) ?></title>
  <style>
    @page { size: A4 <?= $wide ? 'landscape' : 'portrait' ?>; margin: 12mm; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 24px; font: 12px/1.45 "Segoe UI", Arial, "Nirmala UI", sans-serif; color: #1c2033; }
    .bar { display: flex; gap: 10px; align-items: center; margin-bottom: 18px; padding: 10px 14px; background: #eef1fb; border-radius: 10px; }
    .bar button { padding: 8px 16px; border: 0; border-radius: 8px; background: #2f55c9; color: #fff; font-weight: 600; cursor: pointer; }
    .bar span { color: #4a4f66; }
    h1 { margin: 0 0 2px; font-size: 20px; color: #2f55c9; }
    .meta { margin: 0 0 14px; color: #5b6078; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #2f55c9; color: #fff; text-align: left; padding: 7px 8px; font-size: 11.5px; }
    td { padding: 6px 8px; border-bottom: 1px solid #dfe2ee; vertical-align: top; word-break: break-word; }
    tr:nth-child(even) td { background: #f7f8fc; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .num { text-align: right; white-space: nowrap; }
    tfoot td { font-weight: 700; background: #eef1fb; border-top: 2px solid #2f55c9; }
    @media print { .bar { display: none; } body { padding: 0; } th, tfoot td, tr:nth-child(even) td { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
  </style>
</head>
<body>
  <div class="bar">
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
    <span>In the print window, choose <b>Save as PDF</b> as the destination.</span>
  </div>

  <h1><?= h(APP_NAME) ?> – <?= h($report['title']) ?></h1>
  <p class="meta">
    Generated: <?= h($generated) ?> · Records: <?= (int) $report['count'] ?>
    <?php if ($report['filters']): ?><br>Filters: <?= h(implode(' | ', $report['filters'])) ?><?php endif; ?>
  </p>

  <table>
    <thead>
      <tr>
        <?php foreach ($report['headers'] as $i => $hd): ?>
          <th class="<?= in_array($report['kinds'][$i], ['money', 'count'], true) ? 'num' : '' ?>"><?= h($hd) ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$report['rows']): ?>
        <tr><td colspan="<?= count($report['headers']) ?>" style="text-align:center;padding:24px;color:#777">No records match these filters.</td></tr>
      <?php endif; ?>
      <?php foreach ($report['rows'] as $row): ?>
        <tr>
          <?php foreach ($row as $i => $v): ?>
            <td class="<?= in_array($report['kinds'][$i], ['money', 'count'], true) ? 'num' : '' ?>"><?= h($fmt($v, $report['kinds'][$i])) ?></td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <?php if ($report['totals'] && $report['rows']): ?>
    <tfoot>
      <tr>
        <?php foreach ($report['totals'] as $i => $v): ?>
          <td class="<?= $v !== null ? 'num' : '' ?>"><?= $i === 0 && $v === null ? 'Total' : ($v === null ? '' : h($fmt($v, $report['kinds'][$i]))) ?></td>
        <?php endforeach; ?>
      </tr>
    </tfoot>
    <?php endif; ?>
  </table>

  <?php if (!empty($autoPrint)): ?><script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script><?php endif; ?>
</body>
</html>
