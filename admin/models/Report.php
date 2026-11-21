<?php
/**
 * Report definitions + data builder.
 *
 * Every report is a list of selectable columns (key => [label, kind]) and a set of
 * filters. build() returns plain headers/rows so the same data feeds the on-screen
 * preview, the Excel file and the printable (PDF) page.
 *
 * Cell kinds: text | id | date | datetime | status | money | count
 *   - money/count cells are numbers (summed in the totals row), the rest are strings.
 */
class Report
{
    public const TYPES = [
        'appointments' => 'Appointments',
        'clients' => 'Clients',
        'inquiries' => 'Inquiries',
        'payments' => 'Payments',
        'daywise' => 'Daily earnings',
    ];

    /** Report types that read the payments ledger instead of appointments / inquiries. */
    public const MONEY_TYPES = ['payments', 'daywise'];

    public const COLUMNS = [
        'appointments' => [
            'id' => ['No', 'id'],
            'name' => ['Client name', 'text'],
            'phone' => ['Mobile', 'text'],
            'email' => ['Email', 'text'],
            'service' => ['Service', 'text'],
            'appointment_type' => ['Visit type', 'text'],
            'status' => ['Status', 'status'],
            'preferred_date' => ['Preferred date', 'date'],
            'scheduled_at' => ['Scheduled for', 'datetime'],
            'completed_at' => ['Completed on', 'datetime'],
            'payment_amount' => ['Payment (₹)', 'money'],
            'payment_mode' => ['Payment mode', 'text'],
            'message' => ['Client message', 'text'],
            'admin_notes' => ['Admin notes', 'text'],
            'created_at' => ['Booked on', 'datetime'],
        ],
        'clients' => [
            'name' => ['Client name', 'text'],
            'phone' => ['Mobile', 'text'],
            'email' => ['Email', 'text'],
            'total' => ['Total appointments', 'count'],
            'completed' => ['Completed', 'count'],
            'open' => ['Open / upcoming', 'count'],
            'cancelled' => ['Cancelled / not interested', 'count'],
            'services' => ['Services taken', 'text'],
            'types' => ['Visit types', 'text'],
            'first_booking' => ['First booking', 'datetime'],
            'last_booking' => ['Last booking', 'datetime'],
            'last_visit' => ['Last completed visit', 'datetime'],
            'total_paid' => ['Total paid (₹)', 'money'],
        ],
        'inquiries' => [
            'id' => ['No', 'id'],
            'name' => ['Name', 'text'],
            'phone' => ['Mobile', 'text'],
            'email' => ['Email', 'text'],
            'subject' => ['Subject', 'text'],
            'message' => ['Message', 'text'],
            'status' => ['Status', 'status'],
            'admin_notes' => ['Admin notes', 'text'],
            'created_at' => ['Received on', 'datetime'],
        ],
        'payments' => [
            'id' => ['Receipt no', 'id'],
            'paid_at' => ['Paid on', 'datetime'],
            'name' => ['Paid by', 'text'],
            'phone' => ['Mobile', 'text'],
            'email' => ['Email', 'text'],
            'appointment_id' => ['Appointment no', 'id'],
            'service' => ['Service', 'text'],
            'kind' => ['Type', 'text'],
            'mode' => ['Payment mode', 'text'],
            'reference' => ['Reference / txn id', 'text'],
            'note' => ['Note', 'text'],
            'amount' => ['Amount (₹)', 'money'],
        ],
        'daywise' => [
            'day' => ['Date', 'date'],
            'cnt' => ['Transactions', 'count'],
            'paid' => ['Received (₹)', 'money'],
            'refunded' => ['Refunded (₹)', 'money'],
            'm_0' => ['Cash (₹)', 'money'],
            'm_1' => ['UPI (₹)', 'money'],
            'm_2' => ['Card (₹)', 'money'],
            'm_3' => ['Bank Transfer (₹)', 'money'],
            'm_4' => ['Other (₹)', 'money'],
            'net' => ['Net earnings (₹)', 'money'],
        ],
    ];

    public const DEFAULT_COLUMNS = [
        'appointments' => ['name', 'phone', 'service', 'appointment_type', 'status', 'scheduled_at', 'payment_amount', 'created_at'],
        'clients' => ['name', 'phone', 'email', 'total', 'completed', 'total_paid', 'last_booking'],
        'inquiries' => ['name', 'phone', 'email', 'subject', 'status', 'created_at'],
        'payments' => ['id', 'paid_at', 'name', 'phone', 'service', 'kind', 'mode', 'amount'],
        'daywise' => ['day', 'cnt', 'm_0', 'm_1', 'm_2', 'm_3', 'm_4', 'net'],
    ];

    /** Date field options per report: key => [label, column]. */
    public const DATE_FIELDS = [
        'appointments' => ['created_at' => ['Booked on', 'created_at'], 'scheduled_at' => ['Appointment date', 'scheduled_at'], 'completed_at' => ['Completed on', 'completed_at']],
        'clients' => ['created_at' => ['Booked on', 'created_at'], 'scheduled_at' => ['Appointment date', 'scheduled_at'], 'completed_at' => ['Completed on', 'completed_at']],
        'inquiries' => ['created_at' => ['Received on', 'created_at']],
        'payments' => ['paid_at' => ['Payment date', 'paid_at']],
        'daywise' => ['paid_at' => ['Payment date', 'paid_at']],
    ];

    public const SORTS = [
        'appointments' => ['new' => 'Newest first', 'old' => 'Oldest first', 'name' => 'Name A–Z', 'paid' => 'Highest payment'],
        'clients' => ['new' => 'Latest booking first', 'old' => 'Oldest booking first', 'name' => 'Name A–Z', 'visits' => 'Most appointments', 'paid' => 'Highest paid'],
        'inquiries' => ['new' => 'Newest first', 'old' => 'Oldest first', 'name' => 'Name A–Z'],
        'payments' => ['new' => 'Newest first', 'old' => 'Oldest first', 'name' => 'Name A–Z', 'paid' => 'Highest amount'],
        'daywise' => ['new' => 'Newest day first', 'old' => 'Oldest day first'],
    ];

    public static function type(?string $t): string
    {
        return isset(self::TYPES[(string) $t]) ? (string) $t : 'appointments';
    }

    public static function statuses(string $type): array
    {
        if (in_array($type, self::MONEY_TYPES, true)) {
            return [];
        }
        return $type === 'inquiries' ? Inquiry::STATUSES : Appointment::STATUSES;
    }

    /** Columns picked in the form, in definition order; falls back to the defaults. */
    public static function pickColumns(string $type, $requested): array
    {
        $all = self::COLUMNS[$type];
        $requested = is_array($requested) ? $requested : [];
        $picked = array_values(array_filter(array_keys($all), static fn ($k) => in_array($k, $requested, true)));
        return $picked ?: self::DEFAULT_COLUMNS[$type];
    }

    public static function filters(string $type, array $src): array
    {
        $date = static function ($v): string {
            $v = trim((string) $v);
            $dt = DateTime::createFromFormat('Y-m-d', $v);
            return ($dt && $dt->format('Y-m-d') === $v) ? $v : '';
        };
        $money = in_array($type, self::MONEY_TYPES, true);
        $f = [
            'from' => $date($src['from'] ?? ''),
            'to' => $date($src['to'] ?? ''),
            'field' => pick($src['field'] ?? '', array_keys(self::DATE_FIELDS[$type]), (string) array_key_first(self::DATE_FIELDS[$type])),
            'status' => pick($src['status'] ?? '', array_keys(self::statuses($type))),
            'service' => ($type === 'inquiries' || $money) ? '' : pick($src['service'] ?? '', Appointment::SERVICES),
            'visit' => ($type === 'inquiries' || $money) ? '' : pick($src['visit'] ?? '', Appointment::TYPES),
            'mode' => $money ? pick($src['mode'] ?? '', Appointment::PAYMENT_MODES) : '',
            'q' => mb_substr(trim((string) ($src['q'] ?? '')), 0, 100),
            'sort' => pick($src['sort'] ?? '', array_keys(self::SORTS[$type]), 'new'),
        ];
        if ($f['from'] !== '' && $f['to'] !== '' && $f['from'] > $f['to']) {
            [$f['from'], $f['to']] = [$f['to'], $f['from']];
        }
        return $f;
    }

    /** Human-readable list of the applied filters (shown on exports). */
    public static function describe(string $type, array $f): array
    {
        $out = [];
        if ($f['from'] !== '' || $f['to'] !== '') {
            $label = self::DATE_FIELDS[$type][$f['field']][0];
            $fmt = static fn (string $d): string => date('d M Y', strtotime($d));
            $out[] = $label . ': ' . ($f['from'] !== '' ? $fmt($f['from']) : 'start') . ' to ' . ($f['to'] !== '' ? $fmt($f['to']) : 'today');
        }
        if ($f['status'] !== '') {
            $out[] = 'Status: ' . self::statuses($type)[$f['status']];
        }
        if (($f['mode'] ?? '') !== '') {
            $out[] = 'Payment mode: ' . $f['mode'];
        }
        if ($f['service'] !== '') {
            $out[] = 'Service: ' . $f['service'];
        }
        if ($f['visit'] !== '') {
            $out[] = 'Visit type: ' . $f['visit'];
        }
        if ($f['q'] !== '') {
            $out[] = 'Search: "' . $f['q'] . '"';
        }
        return $out;
    }

    /**
     * @return array{title:string,headers:string[],kinds:string[],rows:array,totals:?array,count:int,filters:string[]}
     */
    public static function build(string $type, array $f, array $cols, ?int $limit = null): array
    {
        if (in_array($type, self::MONEY_TYPES, true)) {
            return self::buildMoney($type, $f, $cols, $limit);
        }
        $db = Database::getConnection();
        $table = $type === 'inquiries' ? 'inquiries' : 'appointments';
        $where = [];
        $p = [];

        $col = self::DATE_FIELDS[$type][$f['field']][1];
        if ($f['from'] !== '') {
            $where[] = "`$col` >= :from";
            $p['from'] = $f['from'] . ' 00:00:00';
        }
        if ($f['to'] !== '') {
            $where[] = "`$col` < :to";
            $p['to'] = date('Y-m-d', strtotime($f['to'] . ' +1 day')) . ' 00:00:00';
        }
        if ($f['status'] !== '') {
            $where[] = 'status = :status';
            $p['status'] = $f['status'];
        }
        if ($f['service'] !== '') {
            $where[] = 'service = :service';
            $p['service'] = $f['service'];
        }
        if ($f['visit'] !== '') {
            $where[] = 'appointment_type = :visit';
            $p['visit'] = $f['visit'];
        }
        if ($f['q'] !== '') {
            $like = '%' . addcslashes($f['q'], '%_\\') . '%';
            $where[] = '(name LIKE :q1 OR phone LIKE :q2 OR email LIKE :q3)';
            $p += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $meta = self::COLUMNS[$type];
        $headers = array_map(static fn ($k) => $meta[$k][0], $cols);
        $kinds = array_map(static fn ($k) => $meta[$k][1], $cols);

        if ($type === 'clients') {
            $rows = self::clientRows($db, $sqlWhere, $p, $f['sort']);
        } else {
            $order = match ($f['sort']) {
                'old' => 'created_at ASC, id ASC',
                'name' => 'name ASC, id ASC',
                'paid' => 'payment_amount DESC, id DESC',
                default => 'created_at DESC, id DESC',
            };
            $stmt = $db->prepare("SELECT * FROM $table $sqlWhere ORDER BY $order");
            $stmt->execute($p);
            $rows = $stmt->fetchAll();
        }

        $statusMap = self::statuses($type);
        $out = [];
        foreach ($rows as $i => $r) {
            $line = [];
            foreach ($cols as $c) {
                $line[] = self::cell($meta[$c][1], $c, $r, $statusMap);
            }
            $out[] = $line;
        }

        // Totals row for money / count columns, always over every matching row.
        $totals = null;
        if (in_array('money', $kinds, true) || in_array('count', $kinds, true)) {
            $totals = [];
            foreach ($kinds as $i => $k) {
                $totals[] = ($k === 'money' || $k === 'count') ? array_sum(array_column($out, $i)) : null;
            }
        }

        $count = count($out);
        if ($limit !== null) {
            $out = array_slice($out, 0, $limit);
        }

        return [
            'title' => self::TYPES[$type] . ' Report',
            'headers' => $headers,
            'kinds' => $kinds,
            'rows' => $out,
            'totals' => $totals,
            'count' => $count,
            'filters' => self::describe($type, $f),
        ];
    }

    /** Payments ledger reports: every payment, or one row per day. */
    private static function buildMoney(string $type, array $f, array $cols, ?int $limit): array
    {
        $meta = self::COLUMNS[$type];
        $mf = ['from' => $f['from'], 'to' => $f['to'], 'mode' => $f['mode'], 'kind' => '', 'q' => $f['q']];

        if ($type === 'daywise') {
            $rows = Payment::daywise($mf, $f['sort'] === 'old' ? 'ASC' : 'DESC');
        } else {
            $p = [];
            $where = Payment::where($mf, $p);
            $order = match ($f['sort']) {
                'old' => 'p.paid_at ASC, p.id ASC',
                'name' => 'a.name ASC, p.paid_at DESC',
                'paid' => 'p.amount DESC, p.id DESC',
                default => 'p.paid_at DESC, p.id DESC',
            };
            $stmt = Database::getConnection()->prepare(
                "SELECT p.*, (CASE WHEN p.kind = 'refund' THEN -p.amount ELSE p.amount END) AS signed,
                        a.name, a.phone, a.email, a.service
                 FROM payments p JOIN appointments a ON a.id = p.appointment_id $where ORDER BY $order"
            );
            $stmt->execute($p);
            $rows = array_map(static function ($r) {
                $r['amount'] = $r['signed'];
                $r['kind'] = ucfirst((string) $r['kind']);
                return $r;
            }, $stmt->fetchAll());
        }

        $headers = array_map(static fn ($k) => $meta[$k][0], $cols);
        $kinds = array_map(static fn ($k) => $meta[$k][1], $cols);
        $out = [];
        foreach ($rows as $r) {
            $line = [];
            foreach ($cols as $c) {
                $line[] = self::cell($meta[$c][1], $c, $r, []);
            }
            $out[] = $line;
        }

        $totals = null;
        if (in_array('money', $kinds, true) || in_array('count', $kinds, true)) {
            $totals = [];
            foreach ($kinds as $i => $k) {
                $totals[] = ($k === 'money' || $k === 'count') ? array_sum(array_column($out, $i)) : null;
            }
        }

        $count = count($out);
        return [
            'title' => ($type === 'daywise' ? 'Daily Earnings' : 'Payments') . ' Report',
            'headers' => $headers,
            'kinds' => $kinds,
            'rows' => $limit !== null ? array_slice($out, 0, $limit) : $out,
            'totals' => $totals,
            'count' => $count,
            'filters' => self::describe($type, $f),
        ];
    }

    private static function cell(string $kind, string $key, array $r, array $statusMap)
    {
        if ($key === 'id') {
            return (string) $r['id'];
        }
        $v = $r[$key] ?? null;
        return match ($kind) {
            'money' => ($v === null || $v === '') ? null : (float) $v,
            'count' => (int) $v,
            'date' => $v ? date('d M Y', strtotime((string) $v)) : '',
            'datetime' => $v ? date('d M Y, h:i A', strtotime((string) $v)) : '',
            'status' => $statusMap[$v] ?? (string) $v,
            default => trim((string) $v),
        };
    }

    /** Appointments grouped into one row per client (same mobile number, else email, else name). */
    private static function clientRows(PDO $db, string $sqlWhere, array $p, string $sort): array
    {
        $stmt = $db->prepare("SELECT * FROM appointments $sqlWhere ORDER BY created_at ASC, id ASC");
        $stmt->execute($p);

        $groups = [];
        foreach ($stmt->fetchAll() as $a) {
            $digits = substr(preg_replace('/\D+/', '', (string) $a['phone']) ?? '', -10);
            $key = strlen($digits) >= 7 ? 'p' . $digits
                : (trim((string) $a['email']) !== '' ? 'e' . strtolower(trim((string) $a['email'])) : 'n' . mb_strtolower(trim((string) $a['name'])));

            $g = &$groups[$key];
            if ($g === null) {
                $g = ['name' => '', 'phone' => '', 'email' => '', 'total' => 0, 'completed' => 0, 'open' => 0, 'cancelled' => 0,
                    'services' => [], 'types' => [], 'first_booking' => $a['created_at'], 'last_booking' => $a['created_at'],
                    'last_visit' => null, 'total_paid' => 0.0];
            }
            // Rows arrive oldest -> newest, so the latest details win.
            $g['name'] = $a['name'];
            $g['phone'] = $a['phone'] !== '' ? $a['phone'] : $g['phone'];
            $g['email'] = $a['email'] !== '' ? $a['email'] : $g['email'];
            $g['total']++;
            if ($a['status'] === 'completed') {
                $g['completed']++;
                $g['total_paid'] += (float) $a['payment_amount'];
                if ($a['completed_at'] && ($g['last_visit'] === null || $a['completed_at'] > $g['last_visit'])) {
                    $g['last_visit'] = $a['completed_at'];
                }
            } elseif (in_array($a['status'], Appointment::OPEN, true)) {
                $g['open']++;
            } else {
                $g['cancelled']++;
            }
            if ($a['service'] !== '') {
                $g['services'][$a['service']] = true;
            }
            if ($a['appointment_type'] !== '') {
                $g['types'][$a['appointment_type']] = true;
            }
            $g['last_booking'] = $a['created_at'];
            unset($g);
        }

        $rows = array_values(array_map(static function ($g) {
            $g['services'] = implode(', ', array_keys($g['services']));
            $g['types'] = implode(', ', array_keys($g['types']));
            return $g;
        }, $groups));

        usort($rows, match ($sort) {
            'old' => static fn ($a, $b) => strcmp((string) $a['last_booking'], (string) $b['last_booking']),
            'name' => static fn ($a, $b) => strcasecmp($a['name'], $b['name']),
            'visits' => static fn ($a, $b) => [$b['total'], $b['last_booking']] <=> [$a['total'], $a['last_booking']],
            'paid' => static fn ($a, $b) => $b['total_paid'] <=> $a['total_paid'],
            default => static fn ($a, $b) => strcmp((string) $b['last_booking'], (string) $a['last_booking']),
        });
        return $rows;
    }
}
