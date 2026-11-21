<?php
/** Expects: $pager = ['page'=>int,'pages'=>int,'total'=>int,'per'=>int], $pagerQuery = base query string for the list (without page). */
if (($pager['pages'] ?? 1) > 1):
    $cur = $pager['page'];
    $last = $pager['pages'];
    $from = ($cur - 1) * $pager['per'] + 1;
    $to = min($pager['total'], $cur * $pager['per']);
    $link = static function (int $p) use ($pagerQuery): string {
        return h(ADMIN_URL . 'index.php?' . $pagerQuery . ($p > 1 ? '&page=' . $p : ''));
    };
    $window = array_unique(array_filter([1, $cur - 1, $cur, $cur + 1, $last], static fn($p) => $p >= 1 && $p <= $last));
    sort($window);
?>
<div class="pager">
  <span class="pager-info">Showing <?= $from ?>–<?= $to ?> of <?= $pager['total'] ?></span>
  <nav class="pager-nav" aria-label="Pagination">
    <?php if ($cur > 1): ?><a href="<?= $link($cur - 1) ?>">‹ Prev</a><?php endif; ?>
    <?php $prev = 0; foreach ($window as $p): ?>
      <?php if ($p - $prev > 1): ?><span class="gap">…</span><?php endif; ?>
      <a href="<?= $link($p) ?>" class="<?= $p === $cur ? 'on' : '' ?>"><?= $p ?></a>
    <?php $prev = $p; endforeach; ?>
    <?php if ($cur < $last): ?><a href="<?= $link($cur + 1) ?>">Next ›</a><?php endif; ?>
  </nav>
</div>
<?php endif; ?>
