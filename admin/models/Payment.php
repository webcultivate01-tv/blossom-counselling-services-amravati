<?php
/**
 * Payment ledger. Every payment / refund against an appointment is one row.
 * appointments.payment_amount / payment_mode are kept in sync (net total) so the
 * existing "today" screen and reports keep working.
 */
class Payment
{
    public const KINDS = ['payment' => 'Payment', 'refund' => 'Refund'];

    private const SIGNED = "(CASE WHEN p.kind = 'refund' THEN -p.amount ELSE p.amount END)";

    private static function db(): PDO
    {
        return Database::getConnection();
    }

    /* ---------------- single records ---------------- */

    public static function find(int $id): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function forAppointment(int $appointmentId): array
    {
        $stmt = self::db()->prepare('SELECT * FROM payments WHERE appointment_id = :a ORDER BY paid_at DESC, id DESC');
        $stmt->execute(['a' => $appointmentId]);
        return $stmt->fetchAll();
    }

    public static function add(int $appointmentId, float $amount, string $mode, string $kind, ?string $paidAt, string $reference = '', string $note = ''): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO payments (appointment_id, kind, amount, mode, reference, note, paid_at)
             VALUES (:a, :k, :amt, :m, :r, :n, COALESCE(:p, NOW()))'
        );
        $stmt->execute([
            'a' => $appointmentId, 'k' => $kind, 'amt' => $amount, 'm' => $mode,
            'r' => mb_substr($reference, 0, 100), 'n' => mb_substr($note, 0, 255), 'p' => $paidAt,
        ]);
        $id = (int) self::db()->lastInsertId();
        self::sync($appointmentId);
        return $id;
    }

    public static function update(int $id, float $amount, string $mode, string $kind, ?string $paidAt, string $reference, string $note): void
    {
        $row = self::find($id);
        if (!$row) {
            return;
        }
        $stmt = self::db()->prepare(
            'UPDATE payments SET kind = :k, amount = :amt, mode = :m, reference = :r, note = :n, paid_at = COALESCE(:p, paid_at) WHERE id = :id'
        );
        $stmt->execute([
            'k' => $kind, 'amt' => $amount, 'm' => $mode, 'r' => mb_substr($reference, 0, 100),
            'n' => mb_substr($note, 0, 255), 'p' => $paidAt, 'id' => $id,
        ]);
        self::sync((int) $row['appointment_id']);
    }

    public static function delete(int $id): void
    {
        $row = self::find($id);
        if (!$row) {
            return;
        }
        self::db()->prepare('DELETE FROM payments WHERE id = :id')->execute(['id' => $id]);
        self::sync((int) $row['appointment_id']);
    }

    /** Net amount received for one appointment (payments minus refunds). */
    public static function net(int $appointmentId): float
    {
        $stmt = self::db()->prepare("SELECT COALESCE(SUM(CASE WHEN kind = 'refund' THEN -amount ELSE amount END), 0) FROM payments WHERE appointment_id = :a");
        $stmt->execute(['a' => $appointmentId]);
        return (float) $stmt->fetchColumn();
    }

    /** Copies the ledger total onto the appointment row. */
    public static function sync(int $appointmentId): void
    {
        $db = self::db();
        $stmt = $db->prepare('SELECT COUNT(*) FROM payments WHERE appointment_id = :a');
        $stmt->execute(['a' => $appointmentId]);
        if ((int) $stmt->fetchColumn() === 0) {
            $db->prepare('UPDATE appointments SET payment_amount = NULL, payment_mode = NULL WHERE id = :a')->execute(['a' => $appointmentId]);
            return;
        }
        $mode = $db->prepare("SELECT mode FROM payments WHERE appointment_id = :a AND kind = 'payment' ORDER BY paid_at DESC, id DESC LIMIT 1");
        $mode->execute(['a' => $appointmentId]);
        $db->prepare('UPDATE appointments SET payment_amount = :n, payment_mode = :m WHERE id = :a')
            ->execute(['n' => self::net($appointmentId), 'm' => $mode->fetchColumn() ?: null, 'a' => $appointmentId]);
    }

    /* ---------------- revenue ---------------- */

    /** @return array{today:float,week:float,month:float,lifetime:float,today_count:int,lifetime_count:int} */
    public static function revenue(): array
    {
        $s = self::SIGNED;
        $row = self::db()->query(
            "SELECT
               COALESCE(SUM(CASE WHEN p.paid_at >= CURDATE() AND p.paid_at < CURDATE() + INTERVAL 1 DAY THEN $s END), 0) AS today,
               COALESCE(SUM(CASE WHEN p.paid_at >= CURDATE() - INTERVAL 6 DAY AND p.paid_at < CURDATE() + INTERVAL 1 DAY THEN $s END), 0) AS week,
               COALESCE(SUM(CASE WHEN p.paid_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND p.paid_at < DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 MONTH THEN $s END), 0) AS month,
               COALESCE(SUM($s), 0) AS lifetime,
               COALESCE(SUM(CASE WHEN p.paid_at >= CURDATE() AND p.paid_at < CURDATE() + INTERVAL 1 DAY THEN 1 END), 0) AS today_count,
               COUNT(*) AS lifetime_count
             FROM payments p"
        )->fetch();
        return [
            'today' => (float) $row['today'], 'week' => (float) $row['week'], 'month' => (float) $row['month'],
            'lifetime' => (float) $row['lifetime'], 'today_count' => (int) $row['today_count'], 'lifetime_count' => (int) $row['lifetime_count'],
        ];
    }

    /** Net amount paid by everyone sharing this mobile number (a client's lifetime value). */
    public static function clientLifetime(string $phone): float
    {
        if (trim($phone) === '') {
            return 0.0;
        }
        $stmt = self::db()->prepare(
            "SELECT COALESCE(SUM(CASE WHEN p.kind = 'refund' THEN -p.amount ELSE p.amount END), 0)
             FROM payments p JOIN appointments a ON a.id = p.appointment_id WHERE a.phone = :ph"
        );
        $stmt->execute(['ph' => $phone]);
        return (float) $stmt->fetchColumn();
    }

    /* ---------------- search / day-wise ---------------- */

    public static function filters(array $src): array
    {
        $date = static function ($v): string {
            $v = trim((string) $v);
            $dt = DateTime::createFromFormat('Y-m-d', $v);
            return ($dt && $dt->format('Y-m-d') === $v) ? $v : '';
        };
        $f = [
            'from' => $date($src['from'] ?? ''),
            'to' => $date($src['to'] ?? ''),
            'mode' => pick((string) ($src['mode'] ?? ''), Appointment::PAYMENT_MODES),
            'kind' => pick((string) ($src['kind'] ?? ''), array_keys(self::KINDS)),
            'q' => mb_substr(trim((string) ($src['q'] ?? '')), 0, 100),
        ];
        if ($f['from'] !== '' && $f['to'] !== '' && $f['from'] > $f['to']) {
            [$f['from'], $f['to']] = [$f['to'], $f['from']];
        }
        return $f;
    }

    /** WHERE clause over payments p joined with appointments a. */
    public static function where(array $f, array &$p): string
    {
        $w = [];
        if (($f['from'] ?? '') !== '') {
            $w[] = 'p.paid_at >= :from';
            $p['from'] = $f['from'] . ' 00:00:00';
        }
        if (($f['to'] ?? '') !== '') {
            $w[] = 'p.paid_at < :to';
            $p['to'] = date('Y-m-d', strtotime($f['to'] . ' +1 day')) . ' 00:00:00';
        }
        if (($f['mode'] ?? '') !== '') {
            $w[] = 'p.mode = :mode';
            $p['mode'] = $f['mode'];
        }
        if (($f['kind'] ?? '') !== '') {
            $w[] = 'p.kind = :kind';
            $p['kind'] = $f['kind'];
        }
        if (($f['q'] ?? '') !== '') {
            $like = '%' . addcslashes(trim($f['q']), '%_\\') . '%';
            $w[] = '(a.name LIKE :q1 OR a.phone LIKE :q2 OR a.email LIKE :q3 OR p.reference LIKE :q4)';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }
        return $w ? 'WHERE ' . implode(' AND ', $w) : '';
    }

    /** @return array{rows:array,total:int,pages:int,page:int} */
    public static function search(array $f, int $page, int $perPage = 20): array
    {
        $p = [];
        $where = self::where($f, $p);
        $db = self::db();

        $stmt = $db->prepare("SELECT COUNT(*) FROM payments p JOIN appointments a ON a.id = p.appointment_id $where");
        $stmt->execute($p);
        $total = (int) $stmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = $db->prepare(
            "SELECT p.*, a.name, a.phone, a.email, a.service, a.appointment_type
             FROM payments p JOIN appointments a ON a.id = p.appointment_id
             $where ORDER BY p.paid_at DESC, p.id DESC LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($p);
        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    /** @return array{net:float,paid:float,refunded:float,count:int} for everything matching the filters */
    public static function summary(array $f): array
    {
        $p = [];
        $where = self::where($f, $p);
        $stmt = self::db()->prepare(
            "SELECT COUNT(*) c,
                    COALESCE(SUM(CASE WHEN p.kind = 'payment' THEN p.amount END), 0) paid,
                    COALESCE(SUM(CASE WHEN p.kind = 'refund' THEN p.amount END), 0) refunded
             FROM payments p JOIN appointments a ON a.id = p.appointment_id $where"
        );
        $stmt->execute($p);
        $r = $stmt->fetch();
        return ['net' => (float) $r['paid'] - (float) $r['refunded'], 'paid' => (float) $r['paid'], 'refunded' => (float) $r['refunded'], 'count' => (int) $r['c']];
    }

    /**
     * One row per day: transactions, refunds, per-mode net and the day's net total.
     * @return array<int,array<string,mixed>> keys: day, count, paid, refunded, net, m_<mode>
     */
    public static function daywise(array $f, string $order = 'DESC', ?int $limit = null): array
    {
        $db = self::db();
        $p = [];
        $where = self::where($f, $p);
        $s = self::SIGNED;

        $modeCols = '';
        foreach (Appointment::PAYMENT_MODES as $i => $m) {
            $modeCols .= ", COALESCE(SUM(CASE WHEN p.mode = " . $db->quote($m) . " THEN $s END), 0) AS m_$i";
        }
        $order = $order === 'ASC' ? 'ASC' : 'DESC';
        $stmt = $db->prepare(
            "SELECT DATE(p.paid_at) AS day, COUNT(*) AS cnt,
                    COALESCE(SUM(CASE WHEN p.kind = 'payment' THEN p.amount END), 0) AS paid,
                    COALESCE(SUM(CASE WHEN p.kind = 'refund' THEN p.amount END), 0) AS refunded,
                    COALESCE(SUM($s), 0) AS net $modeCols
             FROM payments p JOIN appointments a ON a.id = p.appointment_id
             $where GROUP BY DATE(p.paid_at) ORDER BY day $order" . ($limit ? ' LIMIT ' . (int) $limit : '')
        );
        $stmt->execute($p);
        return $stmt->fetchAll();
    }
}
