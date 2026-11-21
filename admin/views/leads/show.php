<?php
$isAppt = $cfg['key'] === 'appointment';
$id = (int) $row['id'];
$nameParts = preg_split('/\s+/', trim($row['name']));
$initials = strtoupper(mb_substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));
$phoneDigits = preg_replace('/[^0-9+]/', '', $row['phone']);
$mailSubject = $isAppt ? 'Your appointment – ' . APP_NAME : 'Re: your inquiry – ' . APP_NAME;
$money = static fn ($v): string => '₹' . number_format((float) $v, 2);
$isOpen = $isAppt && in_array($row['status'], Appointment::OPEN, true);
$payments = $payments ?? [];
?>
<a class="back-link" href="<?= h(url('action=' . $cfg['plural'])) ?>">← All <?= h($cfg['plural']) ?></a>

<?php if ($isAppt): ?>
<div class="stat-row rev-strip">
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($paidNet)) ?></b><small>Received on this appointment</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($revenue['today'])) ?></b><small>Today's earnings</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($revenue['month'])) ?></b><small>This month's earnings</small></span></div>
  <div class="stat-tile"><span class="stat-icon"><?= icon('wallet', 20) ?></span><span><b><?= h($money($revenue['lifetime'])) ?></b><small>Lifetime earnings</small></span></div>
</div>
<?php endif; ?>

<section class="card detail-head">
  <div class="detail-id">
    <div class="avatar avatar-lg"><?= h($initials) ?></div>
    <div>
      <h3><?= h($row['name']) ?> <span class="pill <?= status_class($row['status']) ?>"><?= h($cfg['statuses'][$row['status']] ?? $row['status']) ?></span></h3>
      <p><?= $isAppt ? h($row['service'] ?: 'Appointment') : h($row['subject'] ?: 'General inquiry') ?> · Received <?= h(fmt_date($row['created_at'], 'd M Y, h:i A')) ?></p>
    </div>
  </div>
  <div class="detail-actions">
    <?php if ($phoneDigits !== ''): ?><a class="btn btn-primary btn-sm" href="tel:<?= h($phoneDigits) ?>">Call</a><?php endif; ?>
    <?php if ($row['email'] !== ''): ?><a class="btn btn-outline btn-sm" href="mailto:<?= h($row['email']) ?>?subject=<?= h(rawurlencode($mailSubject)) ?>">Reply</a><?php endif; ?>
    <?php if ($isAppt): ?>
      <a class="btn btn-outline btn-sm" href="#reschedule">Reschedule</a>
      <a class="btn btn-outline btn-sm" href="#payments">Payments</a>
      <?php if ($row['status'] !== 'cancelled'): ?><a class="btn btn-outline btn-sm" href="#cancel">Cancel</a><?php endif; ?>
    <?php endif; ?>
    <form method="post" action="<?= h(url('action=' . $cfg['key'] . '_delete')) ?>" class="inline" data-confirm="Delete this <?= h($cfg['noun']) ?>?<?= $isAppt && $payments ? ' Its ' . count($payments) . ' payment record(s) will be deleted too and earnings totals will drop.' : '' ?> This cannot be undone.">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
      <button type="submit" class="btn btn-outline btn-sm">Delete</button>
    </form>
  </div>

  <div class="status-block">
    <div class="label-caps">Update status</div>
    <form method="post" action="<?= h(url('action=' . $cfg['key'] . '_status')) ?>" class="chips">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <input type="hidden" name="return" value="show">
      <?php foreach ($cfg['statuses'] as $k => $label): ?>
        <button type="submit" name="status" value="<?= h($k) ?>" class="chip <?= $row['status'] === $k ? 'on' : '' ?>"><?= h($label) ?></button>
      <?php endforeach; ?>
    </form>
  </div>
</section>

<div class="detail-grid">
  <div>
    <section class="card">
      <h4>Message</h4>
      <div class="message-box"><?= $row['message'] !== null && trim($row['message']) !== '' ? nl2br(h($row['message'])) : '<em class="muted">No message was provided.</em>' ?></div>
    </section>

    <?php if ($isAppt): ?>
    <section class="card" id="payments">
      <div class="card-title">
        <div>
          <h4>Payments</h4>
          <p>Received <strong><?= h($money($paidNet)) ?></strong> · this client has paid <strong><?= h($money($clientTotal)) ?></strong> in total</p>
        </div>
      </div>

      <?php if (!$payments): ?>
        <p class="muted">No payment recorded yet.</p>
      <?php else: ?>
        <div class="table-scroll">
          <table class="table pay-table">
            <thead><tr><th>Date</th><th>Mode</th><th>Reference / note</th><th class="t-right">Amount</th><th class="t-right">Actions</th></tr></thead>
            <tbody>
              <?php foreach ($payments as $p): $refund = $p['kind'] === 'refund'; $pid = (int) $p['id']; ?>
                <tr>
                  <td class="nowrap"><?= h(fmt_date($p['paid_at'])) ?></td>
                  <td><?= h($p['mode']) ?><?= $refund ? ' <span class="pill st-cancelled">Refund</span>' : '' ?></td>
                  <td><?= h(trim($p['reference'] . ($p['reference'] !== '' && $p['note'] !== '' ? ' · ' : '') . $p['note']) ?: '—') ?></td>
                  <td class="t-right nowrap <?= $refund ? 'money-neg' : 'money-pos' ?>"><?= $refund ? '−' : '' ?><?= h($money($p['amount'])) ?></td>
                  <td class="t-right nowrap">
                    <a class="btn btn-outline btn-sm" href="#pay-edit-<?= $pid ?>" data-open="pay-edit-<?= $pid ?>">Edit</a>
                    <form method="post" action="<?= h(url('action=payment_delete')) ?>" class="inline" data-confirm="Delete this payment entry? Earnings totals will change.">
                      <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= $pid ?>">
                      <button type="submit" class="btn btn-danger-outline btn-sm" aria-label="Delete payment"><?= icon('trash', 15) ?></button>
                    </form>
                  </td>
                </tr>
                <tr class="pay-edit-row" id="pay-edit-<?= $pid ?>" hidden>
                  <td colspan="5">
                    <form method="post" action="<?= h(url('action=payment_update')) ?>" class="f-grid">
                      <?= csrf_field() ?><input type="hidden" name="payment_id" value="<?= $pid ?>">
                      <label class="f-field">Type
                        <select name="kind"><?php foreach (Payment::KINDS as $k => $kl): ?><option value="<?= h($k) ?>" <?= $p['kind'] === $k ? 'selected' : '' ?>><?= h($kl) ?></option><?php endforeach; ?></select>
                      </label>
                      <label class="f-field">Amount (₹)
                        <input type="number" name="amount" min="0.01" max="99999999" step="0.01" value="<?= h($p['amount']) ?>" required>
                      </label>
                      <label class="f-field">Mode
                        <select name="mode"><?php foreach ($modes as $m): ?><option <?= $p['mode'] === $m ? 'selected' : '' ?>><?= h($m) ?></option><?php endforeach; ?></select>
                      </label>
                      <label class="f-field">Date &amp; time
                        <input type="datetime-local" name="paid_at" value="<?= h(date('Y-m-d\TH:i', strtotime($p['paid_at']))) ?>" required>
                      </label>
                      <label class="f-field">Reference / txn id
                        <input type="text" name="reference" maxlength="100" value="<?= h($p['reference']) ?>">
                      </label>
                      <label class="f-field">Note
                        <input type="text" name="note" maxlength="255" value="<?= h($p['note']) ?>">
                      </label>
                      <div class="f-actions"><button type="submit" class="btn btn-primary btn-sm">Save changes</button></div>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <form method="post" action="<?= h(url('action=payment_add')) ?>" class="schedule-form">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <div class="label-caps">Record a payment or refund</div>
        <div class="f-grid">
          <label class="f-field">Type
            <select name="kind"><?php foreach (Payment::KINDS as $k => $kl): ?><option value="<?= h($k) ?>"><?= h($kl) ?></option><?php endforeach; ?></select>
          </label>
          <label class="f-field">Amount (₹)
            <input type="number" name="amount" min="0.01" max="99999999" step="0.01" required>
          </label>
          <label class="f-field">Mode
            <select name="mode"><?php foreach ($modes as $m): ?><option><?= h($m) ?></option><?php endforeach; ?></select>
          </label>
          <label class="f-field">Date &amp; time <small class="muted">(blank = now)</small>
            <input type="datetime-local" name="paid_at">
          </label>
          <label class="f-field">Reference / txn id
            <input type="text" name="reference" maxlength="100" placeholder="UPI ref, cheque no…">
          </label>
          <label class="f-field">Note
            <input type="text" name="note" maxlength="255" placeholder="e.g. advance, balance">
          </label>
          <div class="f-actions"><button type="submit" class="btn btn-primary btn-sm">Save entry</button></div>
        </div>
      </form>
    </section>
    <?php endif; ?>

    <section class="card">
      <h4>Internal notes</h4>
      <form method="post" action="<?= h(url('action=' . $cfg['key'] . '_notes')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
        <textarea name="admin_notes" rows="5" maxlength="3000" placeholder="Only visible to admins — call outcomes, follow-up dates, etc."><?= h($row['admin_notes'] ?? '') ?></textarea>
        <div class="form-actions"><button type="submit" class="btn btn-primary btn-sm">Save notes</button></div>
      </form>
    </section>
  </div>

  <div>
    <section class="card">
      <h4>Contact</h4>
      <dl class="kv">
        <dt>Mobile</dt><dd><?= $phoneDigits !== '' ? '<a class="link" href="tel:' . h($phoneDigits) . '">' . h($row['phone']) . '</a>' : '—' ?></dd>
        <dt>Email</dt><dd><?= $row['email'] !== '' ? '<a class="link" href="mailto:' . h($row['email']) . '">' . h($row['email']) . '</a>' : '—' ?></dd>
      </dl>
    </section>

    <?php if ($isAppt): ?>
      <section class="card">
        <h4>Appointment details</h4>
        <dl class="kv">
          <dt>Service</dt><dd><?= h($row['service'] ?: '—') ?></dd>
          <dt>Type</dt><dd><?= h($row['appointment_type'] ?: '—') ?></dd>
          <dt>Preferred date</dt><dd><?= !empty($row['preferred_date']) ? h(fmt_date($row['preferred_date'], 'd M Y')) : '<span class="muted">Not given</span>' ?></dd>
          <dt>Scheduled for</dt><dd><?= $row['scheduled_at'] ? h(fmt_date($row['scheduled_at'])) : '<span class="muted">Not scheduled yet</span>' ?></dd>
        </dl>

        <details class="fold">
          <summary>Edit client &amp; booking details</summary>
          <form method="post" action="<?= h(url('action=appointment_edit')) ?>" class="f-stack">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <label class="f-field">Name <input type="text" name="name" maxlength="100" value="<?= h($row['name']) ?>" required></label>
            <label class="f-field">Mobile <input type="text" name="phone" maxlength="30" value="<?= h($row['phone']) ?>"></label>
            <label class="f-field">Email <input type="email" name="email" maxlength="150" value="<?= h($row['email']) ?>"></label>
            <label class="f-field">Service
              <select name="service">
                <option value="">—</option>
                <?php $svc = Appointment::SERVICES; if ($row['service'] !== '' && !in_array($row['service'], $svc, true)) { $svc[] = $row['service']; } ?>
                <?php foreach ($svc as $s): ?><option <?= $row['service'] === $s ? 'selected' : '' ?>><?= h($s) ?></option><?php endforeach; ?>
              </select>
            </label>
            <label class="f-field">Visit type
              <select name="appointment_type">
                <option value="">—</option>
                <?php $types = Appointment::TYPES; if ($row['appointment_type'] !== '' && !in_array($row['appointment_type'], $types, true)) { $types[] = $row['appointment_type']; } ?>
                <?php foreach ($types as $t): ?><option <?= $row['appointment_type'] === $t ? 'selected' : '' ?>><?= h($t) ?></option><?php endforeach; ?>
              </select>
            </label>
            <label class="f-field">Preferred date <input type="date" name="preferred_date" value="<?= h($row['preferred_date'] ?? '') ?>"></label>
            <label class="f-field">Client message <textarea name="message" rows="3" maxlength="3000"><?= h($row['message'] ?? '') ?></textarea></label>
            <div class="f-actions"><button type="submit" class="btn btn-primary btn-sm">Save details</button></div>
          </form>
        </details>
      </section>

      <section class="card" id="reschedule">
        <h4><?= $row['scheduled_at'] ? 'Reschedule' : 'Schedule' ?> appointment</h4>
        <form method="post" action="<?= h(url('action=appointment_schedule')) ?>" class="f-stack">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
          <label class="f-field">New date &amp; time
            <input type="datetime-local" id="scheduled_at" name="scheduled_at" value="<?= $row['scheduled_at'] ? h(date('Y-m-d\TH:i', strtotime($row['scheduled_at']))) : '' ?>">
          </label>
          <label class="f-field">Reason <small class="muted">(optional, saved to notes)</small>
            <input type="text" name="reason" maxlength="300" placeholder="e.g. client asked for evening slot">
          </label>
          <p class="muted small">Changing an existing time marks this appointment <strong>Rescheduled</strong>. Leave the time empty to clear it.</p>
          <div class="f-actions"><button type="submit" class="btn btn-primary btn-sm"><?= $row['scheduled_at'] ? 'Reschedule' : 'Save time' ?></button></div>
        </form>
      </section>

      <?php if ($row['status'] !== 'cancelled' && $row['status'] !== 'completed'): ?>
      <section class="card" id="cancel">
        <h4>Cancel appointment</h4>
        <form method="post" action="<?= h(url('action=appointment_cancel')) ?>" class="f-stack" data-confirm="Cancel this appointment?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
          <label class="f-field">Reason <small class="muted">(optional, saved to notes)</small>
            <input type="text" name="reason" maxlength="300" placeholder="e.g. client unavailable">
          </label>
          <div class="f-actions"><button type="submit" class="btn btn-danger-outline btn-sm">Cancel appointment</button></div>
        </form>
      </section>
      <?php endif; ?>

      <section class="card" id="complete">
        <h4>Completion</h4>
        <?php if ($row['status'] === 'completed'): ?>
          <dl class="kv">
            <dt>Status</dt><dd><span class="pill st-completed">Completed</span></dd>
            <dt>Completed on</dt><dd><?= h(fmt_date($row['completed_at'])) ?></dd>
            <dt>Received</dt><dd><?= h($money($paidNet)) ?></dd>
          </dl>
        <?php else: ?>
          <form method="post" action="<?= h(url('action=appointment_complete')) ?>" class="schedule-form" style="margin-top:0;padding-top:0;border-top:0" data-confirm="Mark as completed<?= $paidNet > 0 ? '' : ' and save this payment' ?>?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
            <?php if ($paidNet > 0): ?>
              <p class="muted small">Already received <?= h($money($paidNet)) ?>. Add more below, or leave blank to just mark completed.</p>
            <?php endif; ?>
            <label for="payment_amount">Payment amount (₹)<?= $paidNet > 0 ? ' (optional)' : '' ?></label>
            <input type="number" id="payment_amount" name="payment_amount" min="0" max="99999999" step="0.01" <?= $paidNet > 0 ? '' : 'required' ?>>
            <label for="payment_mode">Payment mode</label>
            <select id="payment_mode" name="payment_mode" required>
              <?php foreach (Appointment::PAYMENT_MODES as $m): ?><option><?= h($m) ?></option><?php endforeach; ?>
            </select>
            <div class="form-actions"><button type="submit" class="btn btn-primary btn-sm">Mark completed</button></div>
          </form>
        <?php endif; ?>
      </section>
    <?php else: ?>
      <section class="card">
        <h4>Inquiry details</h4>
        <dl class="kv">
          <dt>Subject</dt><dd><?= h($row['subject'] ?: '—') ?></dd>
          <dt>Received</dt><dd><?= h(fmt_date($row['created_at'])) ?></dd>
        </dl>
      </section>
    <?php endif; ?>
  </div>
</div>
