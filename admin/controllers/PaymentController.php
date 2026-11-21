<?php

/**
 * Payments management: the all-payments ledger (date-wise, who paid) and
 * add / edit / delete of individual payments and refunds on an appointment.
 */
class PaymentController extends Controller
{
    private const PER_PAGE = 20;
    private const DAYS_SHOWN = 31;

    public function index(): void
    {
        AuthController::requireLogin();

        $filters = Payment::filters($_GET);
        $result = Payment::search($filters, max(1, (int) ($_GET['page'] ?? 1)), self::PER_PAGE);

        $this->page('payments/index', [
            'pageTitle' => 'Payments Management',
            'pageSubtitle' => 'Every payment received, by date and by client.',
            'active' => 'payments',
            'filters' => $filters,
            'summary' => Payment::summary($filters),
            'revenue' => Payment::revenue(),
            'modes' => Appointment::PAYMENT_MODES,
            'result' => $result,
            'perPage' => self::PER_PAGE,
        ]);
    }

    public function add(): void
    {
        $this->guardPost('index.php?action=appointments');
        $appt = Appointment::find($this->intParam('id', 'POST'));
        if (!$appt) {
            flash_set('error', 'Appointment not found.');
            $this->redirect('index.php?action=appointments');
        }
        $back = 'index.php?action=appointment&id=' . (int) $appt['id'] . '#payments';

        $data = $this->parse();
        if (is_string($data)) {
            flash_set('error', $data);
            $this->redirect($back);
        }
        if ($data['kind'] === 'refund' && $data['amount'] > Payment::net((int) $appt['id'])) {
            flash_set('error', 'A refund cannot be more than the ₹' . number_format(Payment::net((int) $appt['id']), 2) . ' received for this appointment.');
            $this->redirect($back);
        }

        Payment::add((int) $appt['id'], $data['amount'], $data['mode'], $data['kind'], $data['paid_at'], $data['reference'], $data['note']);
        flash_set('success', ($data['kind'] === 'refund' ? 'Refund' : 'Payment') . ' of ₹' . number_format($data['amount'], 2) . ' (' . $data['mode'] . ') recorded.');
        $this->redirect($back);
    }

    public function update(): void
    {
        $this->guardPost('index.php?action=payments');
        $payment = Payment::find($this->intParam('payment_id', 'POST'));
        if (!$payment) {
            flash_set('error', 'Payment not found.');
            $this->redirect('index.php?action=payments');
        }
        $back = 'index.php?action=appointment&id=' . (int) $payment['appointment_id'] . '#payments';

        $data = $this->parse();
        if (is_string($data)) {
            flash_set('error', $data);
            $this->redirect($back);
        }
        Payment::update((int) $payment['id'], $data['amount'], $data['mode'], $data['kind'], $data['paid_at'], $data['reference'], $data['note']);
        flash_set('success', 'Payment updated.');
        $this->redirect($back);
    }

    public function delete(): void
    {
        $this->guardPost('index.php?action=payments');
        $payment = Payment::find($this->intParam('payment_id', 'POST'));
        if (!$payment) {
            flash_set('error', 'Payment not found.');
            $this->redirect('index.php?action=payments');
        }
        Payment::delete((int) $payment['id']);
        flash_set('success', 'Payment entry deleted.');
        $this->redirect('index.php?action=appointment&id=' . (int) $payment['appointment_id'] . '#payments');
    }

    /** @return array{amount:float,mode:string,kind:string,paid_at:?string,reference:string,note:string}|string error message */
    private function parse(): array|string
    {
        $amountRaw = trim((string) ($_POST['amount'] ?? ''));
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amountRaw) || (float) $amountRaw <= 0) {
            return 'Please enter a valid amount greater than zero.';
        }
        $mode = (string) ($_POST['mode'] ?? '');
        if (!in_array($mode, Appointment::PAYMENT_MODES, true)) {
            return 'Please choose a payment mode.';
        }
        $kind = pick((string) ($_POST['kind'] ?? ''), array_keys(Payment::KINDS), 'payment');

        $paidAt = null;
        $raw = trim((string) ($_POST['paid_at'] ?? ''));
        if ($raw !== '') {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $raw);
            if (!$dt || $dt->format('Y-m-d\TH:i') !== $raw) {
                return 'Please enter a valid payment date and time.';
            }
            $paidAt = $dt->format('Y-m-d H:i:00');
        }
        return [
            'amount' => (float) $amountRaw,
            'mode' => $mode,
            'kind' => $kind,
            'paid_at' => $paidAt,
            'reference' => trim((string) ($_POST['reference'] ?? '')),
            'note' => trim((string) ($_POST['note'] ?? '')),
        ];
    }
}
