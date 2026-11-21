<div class="stat-row stat-row-3">
  <div class="stat-tile"><span class="stat-icon"><?= icon('image', 20) ?></span><span><b><?= (int) $counts['total'] ?></b><small>Total items</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('camera', 20) ?></span><span><b><?= (int) $counts['image'] ?></b><small>Photos</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('video', 20) ?></span><span><b><?= (int) $counts['video'] ?></b><small>Videos</small></span></div>
</div>

<form method="get" action="<?= h(ADMIN_URL . 'index.php') ?>" class="filter-bar gallery-filter">
  <input type="hidden" name="action" value="gallery">
  <select name="type" aria-label="Type" data-autosubmit>
    <option value="">Photos &amp; videos</option>
    <option value="image" <?= $type === 'image' ? 'selected' : '' ?>>Photos only</option>
    <option value="video" <?= $type === 'video' ? 'selected' : '' ?>>Videos only</option>
  </select>
  <select name="category" aria-label="Category" data-autosubmit>
    <option value="">All categories</option>
    <?php foreach ($catMap as $k => $label): ?>
      <option value="<?= h($k) ?>" <?= $category === $k ? 'selected' : '' ?>><?= h($label) ?></option>
    <?php endforeach; ?>
  </select>
  <span class="push-right g-add">
    <button type="button" class="btn btn-primary btn-sm" data-open="photoModal"><?= icon('plus', 15) ?> Add photo</button>
    <button type="button" class="btn btn-primary btn-sm" data-open="videoModal"><?= icon('plus', 15) ?> Add video</button>
  </span>
</form>

<div class="table-card">
  <div class="table-scroll">
    <table class="table gallery-table">
      <thead><tr><th>Preview</th><th>Title</th><th>Type</th><th>Category</th><th>Link</th><th class="t-right">Actions</th></tr></thead>
      <tbody>
      <?php if (!$items): ?>
        <tr><td colspan="6" class="empty">Nothing here yet. Add a photo or a video above.</td></tr>
      <?php endif; ?>
      <?php foreach ($items as $it):
        $isVideo = $it['type'] === 'video';
        $thumb = $isVideo ? 'https://img.youtube.com/vi/' . $it['video_id'] . '/mqdefault.jpg' : site_url((string) $it['file']);
        $link = $isVideo ? 'https://www.youtube.com/watch?v=' . $it['video_id'] : site_url((string) $it['file']);
      ?>
        <tr>
          <td><img class="thumb" src="<?= h($thumb) ?>" alt="" loading="lazy"></td>
          <td><strong><?= h($it['title'] !== '' ? $it['title'] : 'Untitled') ?></strong></td>
          <td><span class="pill <?= $isVideo ? 'st-closed' : 'st-converted' ?>"><?= $isVideo ? 'Video' : 'Photo' ?></span></td>
          <td><?= h($catMap[$it['category']] ?? $it['category']) ?></td>
          <td class="g-link"><a href="<?= h($link) ?>" target="_blank" rel="noopener" title="<?= h($link) ?>"><?= h($link) ?></a></td>
          <td class="t-right nowrap">
            <a class="btn btn-outline btn-sm" href="<?= h(url('action=gallery_edit&id=' . (int) $it['id'])) ?>" aria-label="Edit"><?= icon('edit', 15) ?> Edit</a>
            <form method="post" action="<?= h(url('action=gallery_delete')) ?>" class="inline" data-confirm="Delete this item? This cannot be undone.">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
              <button type="submit" class="btn btn-danger-outline btn-sm" aria-label="Delete"><?= icon('trash', 15) ?></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<dialog class="modal" id="photoModal">
<button type="button" class="modal-x" data-close aria-label="Close">×</button>
<form method="post" action="<?= h(url('action=gallery_upload')) ?>" enctype="multipart/form-data" class="form-card modal-form">
  <?= csrf_field() ?>
  <input type="hidden" name="kind" value="image">
  <h4>Add photos</h4>
  <div class="form-group">
    <label for="images">Upload from computer <span class="muted">(JPG, PNG, WEBP, GIF · max 5 MB each · up to 20)</span></label>
    <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
  </div>
  <div class="form-group">
    <label for="image_urls">…or paste image links <span class="muted">(one per line, e.g. from Google)</span></label>
    <textarea id="image_urls" name="image_urls" rows="3" placeholder="https://example.com/photo.jpg"></textarea>
  </div>
  <div class="form-row-2">
    <div class="form-group">
      <label for="p_category">Category</label>
      <select id="p_category" name="category" required>
        <?php foreach ($catMap as $k => $label): ?><option value="<?= h($k) ?>"><?= h($label) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="p_title">Caption <span class="muted">(optional)</span></label>
      <input type="text" id="p_title" name="title" maxlength="150">
    </div>
  </div>
  <div class="form-actions">
  <button type="button" class="btn btn-outline btn-sm" data-close>Cancel</button><button type="submit" class="btn btn-primary btn-sm">Add photos</button></div>
</form>
</dialog>
<dialog class="modal" id="videoModal">
<button type="button" class="modal-x" data-close aria-label="Close">×</button>
<form method="post" action="<?= h(url('action=gallery_upload')) ?>" class="form-card modal-form">
  <?= csrf_field() ?>
  <input type="hidden" name="kind" value="video">
  <h4>Add a YouTube video</h4>
  <div class="form-group">
    <label for="video_url">YouTube link</label>
    <input type="url" id="video_url" name="video_url" placeholder="https://www.youtube.com/watch?v=..." required>
  </div>
  <div class="form-row-2">
    <div class="form-group">
      <label for="v_category">Category</label>
      <select id="v_category" name="category" required>
        <?php foreach ($catMap as $k => $label): ?><option value="<?= h($k) ?>"><?= h($label) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="v_title">Title <span class="muted">(optional)</span></label>
      <input type="text" id="v_title" name="title" maxlength="150">
    </div>
  </div>
  <div class="form-actions">
  <button type="button" class="btn btn-outline btn-sm" data-close>Cancel</button><button type="submit" class="btn btn-primary btn-sm">Add video</button></div>
</form>
</dialog>
<script>
(function () {
  document.addEventListener('click', function (e) {
    var o = e.target.closest('[data-open]');
    if (o) { document.getElementById(o.getAttribute('data-open')).showModal(); return; }
    if (e.target.closest('[data-close]')) { e.target.closest('dialog').close(); return; }
    if (e.target.tagName === 'DIALOG') e.target.close(); // click on the backdrop
  });
})();
</script>
