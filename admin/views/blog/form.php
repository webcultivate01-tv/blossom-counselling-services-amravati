<?php $isEdit = (int) $post['id'] > 0; ?>
<a class="back-link" href="<?= h(url('action=blogs')) ?>">← All articles</a>

<form method="post" action="<?= h(url('action=blog_save')) ?>" enctype="multipart/form-data" class="card form-card" id="blogForm">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
  <h4><?= $isEdit ? 'Edit article' : 'New article' ?></h4>

  <div class="form-group">
    <label for="title">Title</label>
    <input type="text" id="title" name="title" maxlength="200" required value="<?= h($post['title']) ?>">
  </div>

  <div class="form-group">
    <label for="excerpt">Short description <span class="muted">(shown on the blog card; auto-created if left empty)</span></label>
    <textarea id="excerpt" name="excerpt" rows="2" maxlength="400"><?= h($post['excerpt']) ?></textarea>
  </div>

  <div class="form-row-2">
    <div class="form-group">
      <label for="read_time">Read time (minutes)</label>
      <input type="number" id="read_time" name="read_time" min="1" max="60" value="<?= (int) $post['read_time'] ?>">
    </div>
    <div class="form-group">
      <label for="category">Category</label>
      <?php $blogCats = array_values(Category::map('blog')); if ($post['category'] !== '' && !in_array($post['category'], $blogCats, true)) { $blogCats[] = $post['category']; } ?>
      <select id="category" name="category">
        <?php foreach ($blogCats as $c): ?><option value="<?= h($c) ?>" <?= $post['category'] === $c ? 'selected' : '' ?>><?= h($c) ?></option><?php endforeach; ?>
      </select>
      <small class="muted">Manage the list in <a href="<?= h(url('action=categories')) ?>">Category Management</a>.</small>
    </div>
  </div>

  <div class="form-row-2">
    <div class="form-group">
      <label for="image">Cover image <span class="muted">(JPG, PNG, WEBP or GIF · max 5 MB)</span></label>
      <?php if (!empty($post['image'])): ?>
        <img class="cover-preview" src="<?= h(site_url($post['image'])) ?>" alt="Current cover image">
        <label class="check"><input type="checkbox" name="remove_image" value="1"> Remove current image</label>
      <?php endif; ?>
      <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <label for="image_url" style="margin-top:10px">…or paste an image link</label>
      <input type="text" id="image_url" name="image_url" maxlength="1000" placeholder="https://…" value="<?= preg_match('~^https?://~i', (string) ($post['image'] ?? '')) ? h($post['image']) : '' ?>">
    </div>
    <div class="form-group">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (BlogPost::STATUSES as $k => $label): ?>
          <option value="<?= h($k) ?>" <?= $post['status'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
        <?php endforeach; ?>
      </select>
      <small class="muted">Drafts are not shown on the website.</small>
    </div>
  </div>

  <div class="form-group">
    <label>Article content <span class="muted">(write freely — headings, bold, lists, spacing and line breaks appear on the website exactly as you type them)</span></label>
    <div class="rte" id="rte">
      <div class="rte-bar" role="toolbar" aria-label="Formatting">
        <select class="rte-block" id="rteBlock" aria-label="Text style">
          <option value="p">Normal text</option>
          <option value="h2">Heading</option>
          <option value="h3">Sub-heading</option>
        </select>
        <button type="button" data-cmd="bold" title="Bold (Ctrl+B)"><b>B</b></button>
        <button type="button" data-cmd="italic" title="Italic (Ctrl+I)"><i>I</i></button>
        <button type="button" data-cmd="underline" title="Underline (Ctrl+U)"><u>U</u></button>
        <span class="rte-sep"></span>
        <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
        <button type="button" data-cmd="insertOrderedList" title="Numbered list">1. List</button>
        <button type="button" data-block="blockquote" title="Highlighted quote / note">❝ Quote</button>
        <span class="rte-sep"></span>
        <button type="button" data-cmd="justifyLeft" title="Align left">⟸</button>
        <button type="button" data-cmd="justifyCenter" title="Centre">⇔</button>
        <button type="button" data-cmd="justifyRight" title="Align right">⟹</button>
        <span class="rte-sep"></span>
        <button type="button" id="rteLink" title="Add link">🔗 Link</button>
        <button type="button" data-cmd="unlink" title="Remove link">Unlink</button>
        <button type="button" data-cmd="insertHorizontalRule" title="Divider line">― Line</button>
        <button type="button" data-cmd="removeFormat" title="Clear formatting">Clear</button>
        <span class="rte-sep"></span>
        <button type="button" data-cmd="undo" title="Undo">↶</button>
        <button type="button" data-cmd="redo" title="Redo">↷</button>
      </div>
      <div class="rte-area" id="rteArea" contenteditable="true" spellcheck="true" data-placeholder="Start writing your article here…"><?= clean_blog_html((string) $post['content']) ?></div>
    </div>
    <textarea id="content" name="content" hidden></textarea>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Publish article' ?></button>
    <a class="btn btn-outline" href="<?= h(url('action=blogs')) ?>">Cancel</a>
  </div>
</form>

<script>
(function () {
  var area = document.getElementById('rteArea'), form = document.getElementById('blogForm'),
      bar = document.querySelector('.rte-bar'), blockSel = document.getElementById('rteBlock');
  try { document.execCommand('defaultParagraphSeparator', false, 'p'); document.execCommand('styleWithCSS', false, false); } catch (e) {}
  if (!area.innerHTML.trim()) area.innerHTML = '<p><br></p>';

  function run(cmd, val) { area.focus(); document.execCommand(cmd, false, val || null); sync(); }
  function sync() {
    var b = (document.queryCommandValue('formatBlock') || '').toLowerCase().replace(/[<>]/g, '');
    blockSel.value = (b === 'h2' || b === 'h3') ? b : 'p';
    [].forEach.call(bar.querySelectorAll('[data-cmd]'), function (btn) {
      var c = btn.getAttribute('data-cmd');
      if (/^(bold|italic|underline|insertUnorderedList|insertOrderedList|justify)/.test(c)) {
        var on = false; try { on = document.queryCommandState(c); } catch (e) {}
        btn.classList.toggle('on', on);
      }
    });
  }
  // keep the editor selection when clicking toolbar buttons
  bar.addEventListener('mousedown', function (e) { if (e.target.closest('button')) e.preventDefault(); });
  bar.addEventListener('click', function (e) {
    var btn = e.target.closest('button'); if (!btn) return;
    if (btn.id === 'rteLink') {
      var sel = window.getSelection();
      var url = window.prompt('Link address (https://…)', 'https://');
      if (url && /^(https?:\/\/|mailto:|tel:)/i.test(url.trim())) {
        url = url.trim();
        if (sel && sel.isCollapsed) run('insertHTML', '<a href="' + url.replace(/"/g, '&quot;') + '">' + url.replace(/</g, '&lt;') + '</a>');
        else run('createLink', url);
      }
      return;
    }
    if (btn.getAttribute('data-block')) {
      var cur = (document.queryCommandValue('formatBlock') || '').toLowerCase();
      run('formatBlock', cur === 'blockquote' ? 'p' : 'blockquote');
      return;
    }
    run(btn.getAttribute('data-cmd'));
  });
  blockSel.addEventListener('change', function () { run('formatBlock', blockSel.value); });
  ['keyup', 'mouseup', 'focus'].forEach(function (ev) { area.addEventListener(ev, sync); });

  // Paste as plain text (keeps blank lines) so copied web/Word styling doesn't come along.
  area.addEventListener('paste', function (e) {
    var t = (e.clipboardData || window.clipboardData).getData('text/plain');
    if (t === undefined || t === null) return;
    e.preventDefault();
    var esc = function (x) { return x.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); };
    var html = t.replace(/\r/g, '').split(/\n/).map(function (l) { return '<p>' + (esc(l) || '<br>') + '</p>'; }).join('');
    document.execCommand('insertHTML', false, html);
  });

  form.addEventListener('submit', function () {
    document.getElementById('content').value = area.innerHTML;
  });
})();
</script>
