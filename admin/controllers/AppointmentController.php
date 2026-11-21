<?php

class AppointmentController extends LeadController
{
    protected string $model = Appointment::class;
    protected string $key = 'appointment';
    protected string $plural = 'appointments';
    protected string $title = 'Appointment Management';
    protected string $subtitle = 'Appointment requests booked through the website.';
    protected string $noun = 'appointment';

    protected function cfg(): array
    {
        return parent::cfg() + ['services' => Appointment::SERVICES, 'types' => Appointment::TYPES];
    }

    protected function filters(): array
    {
        $f = parent::filters();
        $type = (string) ($_GET['type'] ?? '');
        $f['type'] = in_array($type, Appointment::TYPES, true) ? $type : '';
        return $f;
    }

    /** Detail page: details, schedule, status, payments and revenue. */
    public function show(): void
    {
        AuthController::requireLogin();

        $row = $this->findOrBack();
        $this->page('leads/show', [
            'pageTitle' => $this->title,
            'pageSubtitle' => $this->subtitle,
            'active' => $this->plural,
            'cfg' => $this->cfg(),
            'row' => $row,
            'payments' => Payment::forAppointment((int) $row['id']),
            'paidNet' => Payment::net((int) $row['id']),
            'clientTotal' => Payment::clientLifetime((string) $row['phone']),
            'revenue' => Payment::revenue(),
            'modes' => Appointment::PAYMENT_MODES,
        ]);
    }

    /** Appointments scheduled for today that are still open. */
    public function today(): void
    {
        AuthController::requireLogin();
        $this->page('appointments/today', [
            'pageTitle' => "Today's Appointments",
            'pageSubtitle' => date('l, d F Y'),
            'active' => 'today',
            'rows' => Appointment::today(),
            'done' => Appointment::completedToday(),
            'tab' => ($_GET['view'] ?? '') === 'completed' ? 'completed' : 'pending',
            'modes' => Appointment::PAYMENT_MODES,
        ]);
    }

    /** Marking completed always records the payment. */
    public function status(): void
    {
        $this->guardPost($this->listUrl());
        if ((string) ($_POST['status'] ?? '') === 'completed') {
            $row = $this->findOrBack('POST');
            if ($row['status'] === 'completed') {
                $this->redirect('index.php?action=appointment&id=' . (int) $row['id']);
            }
            flash_set('error', 'Enter the payment details to complete this appointment.');
            $this->redirect('index.php?action=appointment&id=' . (int) $row['id'] . '#complete');
        }
        parent::status();
    }

    public function complete(): void
    {
        $this->guardPost($this->listUrl());
        $row = $this->findOrBack('POST');
        $back = ($_POST['return'] ?? '') === 'today' ? 'index.php?action=appointments_today' : 'index.php?action=appointment&id=' . (int) $row['id'];

        $amountRaw = trim((string) ($_POST['payment_amount'] ?? ''));
        $mode = (string) ($_POST['payment_mode'] ?? '');
        if ($amountRaw === '' && Payment::net((int) $row['id']) > 0) {
            // Payment(s) already recorded on this appointment: just mark it completed.
            $amountRaw = '0';
            $mode = $mode !== '' ? $mode : 'Cash';
        }
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amountRaw) || !in_array($mode, Appointment::PAYMENT_MODES, true)) {
            flash_set('error', 'Please enter a valid payment amount and mode.');
            $this->redirect($back . (str_contains($back, '&id=') ? '#complete' : ''));
        }
        Appointment::complete((int) $row['id'], (float) $amountRaw, $mode);
        flash_set('success', (float) $amountRaw > 0
            ? 'Appointment completed. Payment of ₹' . number_format((float) $amountRaw, 2) . ' (' . $mode . ') recorded.'
            : 'Appointment marked completed.');
        $this->redirect($back);
    }

    /** Sets or changes the appointment time. Changing an existing time is a reschedule. */
    public function schedule(): void
    {
        $this->guardPost($this->listUrl());
        $row = $this->findOrBack('POST');
        $id = (int) $row['id'];
        $back = 'index.php?action=appointment&id=' . $id;

        $raw = trim((string) ($_POST['scheduled_at'] ?? ''));
        $value = null;
        if ($raw !== '') {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw);
            if (!$dt || $dt->format('Y-m-d\TH:i') !== $raw) {
                flash_set('error', 'Please enter a valid date and time.');
                $this->redirect($back);
            }
            $value = $dt->format('Y-m-d H:i:00');
        }

        Appointment::setSchedule($id, $value);
        if ($value === null) {
            flash_set('success', 'Appointment time cleared.');
            $this->redirect($back);
        }

        $label = date('d M Y, h:i A', strtotime($value));
        $reason = mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 300);
        $old = $row['scheduled_at'] !== null ? (string) $row['scheduled_at'] : null;
        $moved = $old !== null && $old !== $value;

        if ($moved || in_array($row['status'], ['completed', 'cancelled', 'not_interested'], true)) {
            if ($row['status'] !== 'rescheduled') {
                Appointment::setStatus($id, 'rescheduled');
            }
            Appointment::appendNote(
                $id,
                'Rescheduled to ' . $label . ($old !== null ? ' (was ' . date('d M Y, h:i A', strtotime($old)) . ')' : '') . ($reason !== '' ? ' - ' . $reason : '')
            );
            flash_set('success', 'Appointment rescheduled to ' . $label . '.');
        } else {
            if (in_array($row['status'], ['pending', 'contacted'], true)) {
                Appointment::setStatus($id, 'confirmed');
            }
            flash_set('success', 'Appointment scheduled for ' . $label . '.');
        }
        $this->redirect($back);
    }

    public function cancel(): void
    {
        $this->guardPost($this->listUrl());
        $row = $this->findOrBack('POST');
        $id = (int) $row['id'];

        $reason = mb_substr(trim((string) ($_POST['reason'] ?? '')), 0, 300);
        Appointment::setStatus($id, 'cancelled');
        Appointment::appendNote($id, 'Cancelled' . ($reason !== '' ? ' - ' . $reason : ''));

        $received = Payment::net($id);
        $msg = 'Appointment cancelled.';
        if ($received > 0) {
            $msg .= ' ₹' . number_format($received, 2) . ' was received - record a refund below if you are returning it.';
        }
        flash_set('success', $msg);
        $this->redirect('index.php?action=appointment&id=' . $id . ($received > 0 ? '#payments' : ''));
    }

    /** Edit the client / booking details. */
    public function edit(): void
    {
        $this->guardPost($this->listUrl());
        $row = $this->findOrBack('POST');
        $id = (int) $row['id'];
        $back = 'index.php?action=appointment&id=' . $id;

        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 100);
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $service = (string) ($_POST['service'] ?? '');
        $type = (string) ($_POST['appointment_type'] ?? '');
        $pref = trim((string) ($_POST['preferred_date'] ?? ''));

        $error = null;
        if ($name === '') {
            $error = 'Name is required.';
        } elseif (!preg_match('/^[0-9+()\s-]{0,30}$/', $phone)) {
            $error = 'Mobile number can only contain digits, spaces, + - ( ).';
        } elseif ($email !== '' && (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $error = 'Please enter a valid email address.';
        } elseif ($service !== '' && !in_array($service, Appointment::SERVICES, true) && $service !== $row['service']) {
            $error = 'Please choose a valid service.';
        } elseif ($type !== '' && !in_array($type, Appointment::TYPES, true) && $type !== $row['appointment_type']) {
            $error = 'Please choose a valid visit type.';
        } elseif ($pref !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $pref);
            if (!$d || $d->format('Y-m-d') !== $pref) {
                $error = 'Please enter a valid preferred date.';
            }
        }
        if ($error !== null) {
            flash_set('error', $error);
            $this->redirect($back);
        }

        Appointment::updateDetails($id, [
            'name' => $name, 'phone' => $phone, 'email' => $email, 'service' => $service,
            'appointment_type' => $type, 'preferred_date' => $pref,
            'message' => mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 3000),
        ]);
        flash_set('success', 'Appointment details updated.');
        $this->redirect($back);
    }
}
