<?php
/**
 * Seeds DEMO appointments covering every status/service/type, so the admin
 * dashboard, "Today" view and payment flow can be demoed.
 *
 * Run:
 *   - CLI:     php database/seed_appointments.php
 *   - Browser: http://localhost/.../database/seed_appointments.php
 *
 * Safe to re-run: previously seeded demo rows (ip = 'demo-seed') are replaced.
 * Real appointments are never touched.
 */

require __DIR__ . '/../admin/config/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if (!$pdo->query("SHOW TABLES LIKE 'appointments'")->fetch()) {
        exit("The appointments table does not exist yet. Run database/install.php and open /admin once first.\n");
    }

    $pdo->exec("DELETE FROM appointments WHERE ip = 'demo-seed'");

    $today = date('Y-m-d');
    $at = static fn (int $dayOffset, string $time): string => date('Y-m-d', strtotime("$dayOffset days")) . ' ' . $time;
    $on = static fn (int $dayOffset): string => date('Y-m-d', strtotime("$dayOffset days"));

    // name, phone, email, service, type, message, status, preferred_date, scheduled_at, payment_amount, payment_mode, completed_at, notes
    $rows = [
        // --- Today (shows in the "Today" view) ---
        ['Priya Deshmukh', '9823011101', 'priya.deshmukh@example.com', 'Personal Counseling', 'Centre Visit', 'Feeling overwhelmed with work and need someone to talk to.', 'confirmed', $on(0), $at(0, '10:00:00'), null, null, null, 'Confirmed on call. First session.'],
        ['Rahul Patil', '9823011102', 'rahul.patil@example.com', 'Stress and Anxiety Management', 'Office Visit', 'Frequent anxiety before presentations.', 'pending', $on(0), $at(0, '11:30:00'), null, null, null, null],
        ['Sneha Kulkarni', '9823011103', 'sneha.k@example.com', 'Children Counseling', 'Home Visit', 'My 9-year-old has become very withdrawn after changing schools.', 'contacted', $on(0), $at(0, '15:00:00'), null, null, null, 'Spoke to parent, address shared on WhatsApp.'],
        ['Amit Joshi', '9823011104', 'amit.joshi@example.com', 'Marriage Counseling', 'Centre Visit', 'We keep having arguments about finances.', 'rescheduled', $on(0), $at(0, '17:00:00'), null, null, null, 'Moved from yesterday at client request.'],

        // --- Completed today (payment collected) ---
        ['Meera Bhosale', '9823011105', 'meera.bhosale@example.com', 'Depression Support', 'Centre Visit', 'Low mood for the last few months.', 'completed', $on(0), $at(0, '09:00:00'), 1000.00, 'UPI', $at(0, '10:05:00'), 'Follow-up in two weeks.'],

        // --- Upcoming ---
        ['Vikas Thakre', '9823011106', 'vikas.thakre@example.com', 'Financial Counseling', 'Office Visit', 'Debt stress, need guidance on planning.', 'confirmed', $on(1), $at(1, '12:00:00'), null, null, null, null],
        ['Anita Wankhede', '9823011107', 'anita.w@example.com', 'Senior Citizen Counseling', 'Home Visit', 'For my father (72), feels lonely since retirement.', 'confirmed', $on(2), $at(2, '16:00:00'), null, null, null, 'Home visit near Camp Road.'],
        ['Suresh Raut', '9823011108', 'suresh.raut@example.com', 'Relationship Counseling', 'Centre Visit', 'Communication problems with my partner.', 'pending', $on(3), null, null, null, null, null],
        ['Kavita Ingole', '9823011109', 'kavita.ingole@example.com', 'Children Counseling', 'School & College Visit', 'School requested a counseling session for exam stress.', 'pending', $on(4), null, null, null, null, null],
        ['Nilesh Gawande', '9823011110', 'nilesh.g@example.com', 'Personal Counseling', 'Home Visit', '', 'contacted', $on(5), null, null, null, null, 'Call back after 6 pm.'],

        // --- Past: completed with payments ---
        ['Pooja Sawarkar', '9823011111', 'pooja.s@example.com', 'Stress and Anxiety Management', 'Centre Visit', 'Exam anxiety.', 'completed', $on(-1), $at(-1, '11:00:00'), 800.00, 'Cash', $at(-1, '12:00:00'), null],
        ['Ganesh Bhise', '9823011112', 'ganesh.bhise@example.com', 'Marriage Counseling', 'Centre Visit', 'Pre-marital counseling.', 'completed', $on(-3), $at(-3, '14:00:00'), 1500.00, 'Card', $at(-3, '15:10:00'), 'Couple very receptive.'],
        ['Rutuja Pande', '9823011113', 'rutuja.pande@example.com', 'Depression Support', 'Home Visit', '', 'completed', $on(-5), $at(-5, '10:30:00'), 1200.00, 'Bank Transfer', $at(-5, '11:45:00'), null],
        ['Dinesh Kale', '9823011114', 'dinesh.kale@example.com', 'Financial Counseling', 'Office Visit', 'Business loss, feeling hopeless.', 'completed', $on(-8), $at(-8, '17:00:00'), 2000.00, 'UPI', $at(-8, '18:00:00'), null],

        // --- Past: other outcomes ---
        ['Shweta Moon', '9823011115', 'shweta.moon@example.com', 'Personal Counseling', 'Centre Visit', 'Just exploring options.', 'not_interested', $on(-2), null, null, null, null, 'Found it expensive, declined.'],
        ['Harish Dahake', '9823011116', 'harish.d@example.com', 'Relationship Counseling', 'Home Visit', '', 'cancelled', $on(-4), $at(-4, '13:00:00'), null, null, null, 'Cancelled — out of town.'],
        ['Komal Wadekar', '9823011117', 'komal.w@example.com', 'Senior Citizen Counseling', 'Centre Visit', 'For my mother.', 'cancelled', $on(-6), null, null, null, null, null],
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO appointments
            (name, phone, email, service, appointment_type, message, status, preferred_date, scheduled_at,
             payment_amount, payment_mode, completed_at, admin_notes, ip, created_at)
         VALUES
            (:name, :phone, :email, :service, :type, :message, :status, :pref, :sched,
             :amount, :mode, :completed, :notes, 'demo-seed', :created)"
    );

    foreach ($rows as $i => [$name, $phone, $email, $service, $type, $message, $status, $pref, $sched, $amount, $mode, $completed, $notes]) {
        $stmt->execute([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'service' => $service,
            'type' => $type,
            'message' => $message,
            'status' => $status,
            'pref' => $pref,
            'sched' => $sched,
            'amount' => $amount,
            'mode' => $mode,
            'completed' => $completed,
            'notes' => $notes,
            // Spread creation times so the list order looks natural.
            'created' => date('Y-m-d H:i:s', strtotime('-' . (count($rows) - $i) . ' hours')),
        ]);
    }

    echo 'Seeded ' . count($rows) . " demo appointments into \"" . DB_NAME . "\".\n";
    echo "Today's date used: {$today}\n";
    echo "Re-run this script any time to refresh the demo dates relative to today.\n";
    echo "To remove them: DELETE FROM appointments WHERE ip = 'demo-seed';\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Seeding failed: ' . $e->getMessage() . "\n";
}
