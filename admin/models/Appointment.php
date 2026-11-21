<?php

class Appointment extends LeadModel
{
    protected const TABLE = 'appointments';
    public const DEFAULT_STATUS = 'pending';

    public const STATUSES = [
        'pending' => 'Pending',
        'contacted' => 'Contacted',
        'confirmed' => 'Confirmed',
        'rescheduled' => 'Rescheduled',
        'not_interested' => 'Not Interested',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /** Must match the options on the website's booking form. */
    public const SERVICES = [
        'Children Counseling',
        'Marriage Counseling',
        'Senior Citizen Counseling',
        'Personal Counseling',
        'Relationship Counseling',
        'Financial Counseling',
        'Stress and Anxiety Management',
        'Depression Support',
    ];

    public const TYPES = [
        'Home Visit',
        'School & College Visit',
        'Office Visit',
        'Centre Visit',
    ];

    public const PAYMENT_MODES = ['Cash', 'UPI', 'Card', 'Bank Transfer', 'Other'];

    /** Statuses that still need work (everything except the finished ones). */
    public const OPEN = ['pending', 'contacted', 'confirmed', 'rescheduled'];

    /**
     * Appointments scheduled for today that are not finished yet. A rescheduled
     * appointment shows up here on its new date because scheduled_at is replaced.
     */
    public static function today(): array
    {
        $stmt = self::db()->query(
            "SELECT * FROM appointments
             WHERE DATE(scheduled_at) = CURDATE() AND status IN ('pending','contacted','confirmed','rescheduled')
             ORDER BY scheduled_at ASC, id ASC"
        );
        return $stmt->fetchAll();
    }

    /** Appointments completed today (by completion time), newest first. */
    public static function completedToday(): array
    {
        return self::db()->query(
            "SELECT * FROM appointments WHERE status = 'completed' AND DATE(completed_at) = CURDATE() ORDER BY completed_at DESC, id DESC"
        )->fetchAll();
    }

    public static function todayCount(): int
    {
        return (int) self::db()->query(
            "SELECT COUNT(*) FROM appointments WHERE DATE(scheduled_at) = CURDATE() AND status IN ('pending','contacted','confirmed','rescheduled')"
        )->fetchColumn();
    }

    /** Marks the appointment completed and records the payment in the ledger (a ₹0 completion records none). */
    public static function complete(int $id, float $amount, string $mode): void
    {
        self::db()->prepare("UPDATE appointments SET status = 'completed', completed_at = NOW() WHERE id = :id")->execute(['id' => $id]);
        if ($amount > 0) {
            Payment::add($id, $amount, $mode, 'payment', null, '', 'Collected on completion');
        }
    }

    /** Edits the client / booking details. */
    public static function updateDetails(int $id, array $d): void
    {
        $stmt = self::db()->prepare(
            'UPDATE appointments SET name = :name, phone = :phone, email = :email, service = :service,
                    appointment_type = :type, preferred_date = :pref, message = :message WHERE id = :id'
        );
        $stmt->execute([
            'name' => $d['name'], 'phone' => $d['phone'], 'email' => $d['email'], 'service' => $d['service'],
            'type' => $d['appointment_type'], 'pref' => $d['preferred_date'] ?: null, 'message' => $d['message'], 'id' => $id,
        ]);
    }

    /** Adds a dated line to the internal notes (call outcomes, cancel / reschedule reasons). */
    public static function appendNote(int $id, string $line): void
    {
        $stmt = self::db()->prepare("UPDATE appointments SET admin_notes = CONCAT_WS('\n', NULLIF(admin_notes, ''), :n) WHERE id = :id");
        $stmt->execute(['n' => '[' . date('d M Y, h:i A') . '] ' . $line, 'id' => $id]);
    }

    protected static function extraWhere(array $filters, array &$params): array
    {
        $cond = [];
        $service = $filters['service'] ?? '';
        if ($service !== '' && in_array($service, self::SERVICES, true)) {
            $cond[] = 'service = :service';
            $params['service'] = $service;
        }
        $type = $filters['type'] ?? '';
        if ($type !== '' && in_array($type, self::TYPES, true)) {
            $cond[] = 'appointment_type = :atype';
            $params['atype'] = $type;
        }
        return $cond;
    }

    public static function create(array $d): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO appointments (name, phone, email, service, appointment_type, message, preferred_date, status, ip)
             VALUES (:name, :phone, :email, :service, :type, :message, :pref, :status, :ip)'
        );
        $stmt->execute([
            'name' => $d['name'],
            'phone' => $d['phone'] ?? '',
            'email' => $d['email'] ?? '',
            'service' => $d['service'] ?? '',
            'type' => $d['appointment_type'] ?? '',
            'message' => $d['message'] ?? '',
            'pref' => ($d['preferred_date'] ?? '') !== '' ? $d['preferred_date'] : null,
            'status' => self::DEFAULT_STATUS,
            'ip' => $d['ip'] ?? '',
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function setSchedule(int $id, ?string $datetime): void
    {
        $stmt = self::db()->prepare('UPDATE appointments SET scheduled_at = :d WHERE id = :id');
        $stmt->execute(['d' => $datetime, 'id' => $id]);
    }
}
