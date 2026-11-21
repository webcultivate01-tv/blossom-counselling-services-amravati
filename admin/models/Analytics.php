<?php
/**
 * Read-only aggregates that feed the dashboard charts.
 * Money is always net (payments minus refunds).
 */
class Analytics
{
    private const SIGNED = "(CASE WHEN kind = 'refund' THEN -amount ELSE amount END)";

    private static function db(): PDO
    {
        return Database::getConnection();
    }

    /** Net earnings for a date range [from, to) as float. */
    private static function earned(string $fromExpr, string $toExpr): float
    {
        $s = self::SIGNED;
        return (float) self::db()->query("SELECT COALESCE(SUM($s), 0) FROM payments WHERE paid_at >= $fromExpr AND paid_at < $toExpr")->fetchColumn();
    }

    /** Last 30 days vs the 30 days before that. */
    public static function last30(): array
    {
        $cur = self::earned('CURDATE() - INTERVAL 29 DAY', 'CURDATE() + INTERVAL 1 DAY');
        $prev = self::earned('CURDATE() - INTERVAL 59 DAY', 'CURDATE() - INTERVAL 29 DAY');
        $change = $prev > 0 ? (($cur - $prev) / $prev) * 100 : null;
        return ['amount' => $cur, 'previous' => $prev, 'change' => $change];
    }

    /** Net earnings for each of the last $days days (zero-filled). @return array<int,array{label:string,date:string,value:float}> */
    public static function dailyEarnings(int $days = 30): array
    {
        $s = self::SIGNED;
        $rows = self::db()->query(
            "SELECT DATE(paid_at) d, SUM($s) v FROM payments
             WHERE paid_at >= CURDATE() - INTERVAL " . ($days - 1) . " DAY AND paid_at < CURDATE() + INTERVAL 1 DAY GROUP BY DATE(paid_at)"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        return self::fillDays($rows, $days);
    }

    /** New bookings (appointments created) per day, zero-filled. */
    public static function dailyBookings(int $days = 30): array
    {
        $rows = self::db()->query(
            "SELECT DATE(created_at) d, COUNT(*) v FROM appointments
             WHERE created_at >= CURDATE() - INTERVAL " . ($days - 1) . " DAY AND created_at < CURDATE() + INTERVAL 1 DAY GROUP BY DATE(created_at)"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        return self::fillDays($rows, $days);
    }

    private static function fillDays(array $rows, int $days): array
    {
        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $ts = strtotime("-$i day");
            $d = date('Y-m-d', $ts);
            $out[] = ['label' => date('d M', $ts), 'date' => $d, 'value' => (float) ($rows[$d] ?? 0)];
        }
        return $out;
    }

    /** Net earnings per month for the last $months months (zero-filled). */
    public static function monthlyEarnings(int $months = 12): array
    {
        $s = self::SIGNED;
        $rows = self::db()->query(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') m, SUM($s) v FROM payments
             WHERE paid_at >= DATE_FORMAT(CURDATE() - INTERVAL " . ($months - 1) . " MONTH, '%Y-%m-01') GROUP BY m"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $out = [];
        $first = strtotime(date('Y-m-01'));
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts = strtotime("-$i month", $first);
            $out[] = ['label' => date('M', $ts), 'date' => date('Y-m', $ts), 'value' => (float) ($rows[date('Y-m', $ts)] ?? 0)];
        }
        return $out;
    }

    /** Net earnings grouped by payment mode (lifetime), biggest first, zero modes dropped. */
    public static function byMode(): array
    {
        $s = self::SIGNED;
        $rows = self::db()->query("SELECT COALESCE(NULLIF(mode, ''), 'Other') m, SUM($s) v FROM payments GROUP BY m")->fetchAll(PDO::FETCH_KEY_PAIR);
        return self::sorted($rows);
    }

    /** Net earnings by service (lifetime). */
    public static function byService(): array
    {
        $s = self::SIGNED;
        $rows = self::db()->query(
            "SELECT COALESCE(NULLIF(a.service, ''), 'Other') sv, SUM(" . str_replace(['kind', 'amount'], ['p.kind', 'p.amount'], $s) . ") v
             FROM payments p JOIN appointments a ON a.id = p.appointment_id GROUP BY sv"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        return self::sorted($rows);
    }

    /** Appointment count grouped by a column (service, appointment_type). */
    public static function appointmentsBy(string $col): array
    {
        if (!in_array($col, ['service', 'appointment_type'], true)) {
            return [];
        }
        $rows = self::db()->query("SELECT COALESCE(NULLIF($col, ''), 'Other') k, COUNT(*) v FROM appointments GROUP BY k")->fetchAll(PDO::FETCH_KEY_PAIR);
        return self::sorted($rows);
    }

    /** Bookings per month for the last $months months. */
    public static function monthlyBookings(int $months = 6): array
    {
        $rows = self::db()->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') m, COUNT(*) v FROM appointments
             WHERE created_at >= DATE_FORMAT(CURDATE() - INTERVAL " . ($months - 1) . " MONTH, '%Y-%m-01') GROUP BY m"
        )->fetchAll(PDO::FETCH_KEY_PAIR);
        $out = [];
        $first = strtotime(date('Y-m-01'));
        for ($i = $months - 1; $i >= 0; $i--) {
            $ts = strtotime("-$i month", $first);
            $out[] = ['label' => date('M', $ts), 'value' => (float) ($rows[date('Y-m', $ts)] ?? 0)];
        }
        return $out;
    }

    /** Appointment counts for today / this week / last 30 days and average ticket size. */
    public static function bookingStats(): array
    {
        $r = self::db()->query(
            "SELECT
               SUM(created_at >= CURDATE()) AS today,
               SUM(created_at >= CURDATE() - INTERVAL 6 DAY) AS week,
               SUM(created_at >= CURDATE() - INTERVAL 29 DAY) AS d30,
               SUM(status = 'completed' AND completed_at >= CURDATE() - INTERVAL 29 DAY) AS done30,
               COUNT(DISTINCT NULLIF(phone, '')) AS clients
             FROM appointments"
        )->fetch();
        $paid = (int) self::db()->query("SELECT COUNT(DISTINCT appointment_id) FROM payments WHERE kind = 'payment'")->fetchColumn();
        return [
            'today' => (int) $r['today'], 'week' => (int) $r['week'], 'd30' => (int) $r['d30'],
            'done30' => (int) $r['done30'], 'clients' => (int) $r['clients'], 'paid_appointments' => $paid,
        ];
    }

    private static function sorted(array $rows): array
    {
        $rows = array_filter(array_map('floatval', $rows), static fn ($v) => $v > 0);
        arsort($rows);
        return $rows;
    }
}
