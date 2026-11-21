<?php $isVideo = $item['type'] === 'video'; ?>
<a class="back-link" href="<?= h(url('action=gallery')) ?>">← Back to gallery</a>

<form method="post" action="<?= h(url('action=gallery_update')) ?>" enctype="multipart/form-data" class="card form-card">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
  <h4><?= $isVideo ? 'Edit video' : 'Edit photo' ?></h4>

  <?php if ($isVideo): ?>
    <img class="cover-preview" src="https://img.youtube.com/vi/<?= h($item['video_id']) ?>/mqdefault.jpg" alt="">
    <div class="form-group">
      <label for="video_url">YouTube link</label>
      <input type="url" id="video_url" name="video_url" required value="https://www.youtube.com/watch?v=<?= h($item['video_id']) ?>">
    </div>
  <?php else: ?>
    <img class="cover-preview" src="<?= h(site_url($item['file'])) ?>" alt="<?= h($item['title']) ?>">
    <div class="form-group">
      <label for="image_url">Image link</label>
      <input type="text" id="image_url" name="image_url" maxlength="1000" placeholder="https://…" value="<?= preg_match('~^https?://~i', (string) $item['file']) ? h($item['file']) : '' ?>">
      <small class="muted">Paste a new link, or choose a file below to replace the image. Leave both as they are to keep the current image.</small>
    </div>
    <div class="form-group">
      <label for="image">Replace with a file <span class="muted">(JPG, PNG, WEBP or GIF · max 5 MB)</span></label>
      <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
    </div>
  <?php endif; ?>

  <div class="form-row-2">
    <div class="form-group">
      <label for="category">Category</label>
      <select id="category" name="category" required>
        <?php foreach ($catMap as $k => $label): ?>
          <option value="<?= h($k) ?>" <?= $item['category'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="title"><?= $isVideo ? 'Title' : 'Caption' ?> <span class="muted">(optional)</span></label>
      <input type="text" id="title" name="title" maxlength="150" value="<?= h($item['title']) ?>">
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Save changes</button>
    <a class="btn btn-outline" href="<?= h(url('action=gallery')) ?>">Cancel</a>
  </div>
</form>
