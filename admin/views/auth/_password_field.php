<?php /* expects $fieldId, $fieldName, $fieldLabel, optional $autocomplete, $placeholder, $minlength */ ?>
<div class="form-group">
  <label for="<?= $fieldId ?>"><?= htmlspecialchars($fieldLabel) ?></label>
  <div class="input-wrap">
    <input type="password" id="<?= $fieldId ?>" name="<?= $fieldName ?>" required
           autocomplete="<?= $autocomplete ?? 'current-password' ?>" placeholder="<?= htmlspecialchars($placeholder ?? '••••••••') ?>"<?= isset($minlength) ? ' minlength="' . (int)$minlength . '"' : '' ?>>
    <button type="button" class="toggle-pass" data-target="<?= $fieldId ?>" aria-label="Show password" aria-pressed="false">
      <svg class="eye-on" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
      <svg class="eye-off" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-7 0-11-7-11-7a19.8 19.8 0 0 1 5.06-5.94M9.9 4.24A10.9 10.9 0 0 1 12 5c7 0 11 7 11 7a19.9 19.9 0 0 1-3.17 4.19M14.12 14.12a3 3 0 1 1-4.24-4.24M1 1l22 22"/></svg>
    </button>
  </div>
</div>
