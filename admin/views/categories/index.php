<div class="cat-tabs" role="tablist">
<?php $first = true; foreach ($groups as $type => $g): ?>
  <button type="button" role="tab" class="cat-tab<?= $first ? ' on' : '' ?>" data-tab="<?= h($type) ?>" aria-selected="<?= $first ? 'true' : 'false' ?>"><?= h($g['label']) ?></button>
<?php $first = false; endforeach; ?>
</div>
<div class="cat-grid">
<?php $first = true; foreach ($groups as $type => $g): ?>
  <section class="table-card cat-card<?= $first ? ' tab-on' : '' ?>" data-panel="<?= h($type) ?>"><?php $first = false; ?>
    <div class="cat-head">
      <h4><?= h($g['label']) ?> categories</h4>
      <p class="muted">
        <?= $type === 'gallery' ? 'Used to group photos and videos in Gallery Management and as filters on the gallery page.' : 'Used when writing articles in Blog Management and as filters on the blog page.' ?>
      </p>
      <form method="post" action="<?= h(url('action=category_create')) ?>" class="cat-add">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="<?= h($type) ?>">
        <input type="text" name="name" maxlength="60" required placeholder="New category name" aria-label="New <?= h(strtolower($g['label'])) ?> category name">
        <button type="submit" class="btn btn-primary btn-sm"><?= icon('plus', 15) ?> Add category</button>
      </form>
    </div>
    <div class="table-scroll">
      <table class="table">
        <thead><tr><th>Name</th><th><?= $type === 'gallery' ? 'Items' : 'Articles' ?></th><th class="t-right">Actions</th></tr></thead>
        <tbody>
        <?php if (!$g['rows']): ?>
          <tr><td colspan="3" class="empty">No categories yet. Add one above.</td></tr>
        <?php endif; ?>
        <?php foreach ($g['rows'] as $c): $used = $g['usage'][(int) $c['id']] ?? 0; ?>
          <tr>
            <td>
              <form method="post" action="<?= h(url('action=category_update')) ?>" class="cat-edit">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <input type="text" name="name" value="<?= h($c['name']) ?>" maxlength="60" required aria-label="Category name">
                <button type="submit" class="btn btn-outline btn-sm"><?= icon('edit', 15) ?> Save</button>
              </form>
            </td>
            <td><span class="cat-count"><?= (int) $used ?></span></td>
            <td class="t-right nowrap">
              <form method="post" action="<?= h(url('action=category_delete')) ?>" class="inline" data-confirm="Delete the category &quot;<?= h($c['name']) ?>&quot;? This cannot be undone.">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button type="submit" class="btn btn-danger-outline btn-sm" aria-label="Delete"><?= icon('trash', 15) ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php endforeach; ?>
</div>
<script>
(function () {
  var tabs = document.querySelectorAll('.cat-tab'), panels = document.querySelectorAll('.cat-card');
  var saved = null;
  try { saved = sessionStorage.getItem('catTab'); } catch (e) {}
  function show(key) {
    tabs.forEach(function (t) { var on = t.dataset.tab === key; t.classList.toggle('on', on); t.setAttribute('aria-selected', on); });
    panels.forEach(function (p) { p.classList.toggle('tab-on', p.dataset.panel === key); });
    try { sessionStorage.setItem('catTab', key); } catch (e) {}
  }
  tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.dataset.tab); }); });
  if (saved && document.querySelector('.cat-tab[data-tab="' + saved + '"]')) show(saved);
})();
</script>
