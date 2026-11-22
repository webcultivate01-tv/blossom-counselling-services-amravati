<?php
/**
 * Endpoints used by the public website (no login):
 *   POST index.php?action=submit   – booking form / inquiry form
 *   GET  index.php?action=api&r=blogs|gallery  – published content as JSON
 */
class PublicController extends Controller
{
    private const MAX_PER_10_MIN = 5;

    public function submit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'Method not allowed.'], 405);
        }

        // Honeypot: real visitors never fill this hidden field.
        if (trim((string) ($_POST['website'] ?? '')) !== '') {
            $this->json(['ok' => true]);
        }

        $type = ($_POST['type'] ?? '') === 'inquiry' ? 'inquiry' : 'appointment';
        $ip = client_ip();
        $model = $type === 'inquiry' ? Inquiry::class : Appointment::class;

        if ($model::recentFromIp($ip) >= self::MAX_PER_10_MIN) {
            $this->json(['ok' => false, 'error' => 'Too many requests. Please try again in a few minutes.'], 429);
        }

        $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 100);
        $phone = mb_substr(trim((string) ($_POST['phone'] ?? '')), 0, 30);
        $email = mb_substr(trim((string) ($_POST['email'] ?? '')), 0, 150);
        $message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 3000);

        if (mb_strlen($name) < 2) {
            $this->json(['ok' => false, 'error' => 'Please enter your full name.'], 422);
        }
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $this->json(['ok' => false, 'error' => 'Please enter a valid phone number.'], 422);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
        }

        if ($type === 'inquiry') {
            if ($message === '') {
                $this->json(['ok' => false, 'error' => 'Please write your message.'], 422);
            }
            Inquiry::create([
                'name' => $name, 'phone' => $phone, 'email' => $email, 'message' => $message,
                'subject' => mb_substr(trim((string) ($_POST['subject'] ?? '')), 0, 150),
                'ip' => $ip,
            ]);
        } else {
            $service = (string) ($_POST['service'] ?? '');
            $apptType = (string) ($_POST['appointment_type'] ?? '');
            if (!in_array($service, Appointment::SERVICES, true)) {
                $this->json(['ok' => false, 'error' => 'Please choose a service.'], 422);
            }
            // The site's option text uses "&amp;" in HTML, which arrives as "&".
            if (!in_array($apptType, Appointment::TYPES, true)) {
                $this->json(['ok' => false, 'error' => 'Please choose an appointment type.'], 422);
            }
            $prefDate = (string) ($_POST['preferred_date'] ?? '');
            $pd = DateTime::createFromFormat('Y-m-d', $prefDate);
            $prefDate = ($pd && $pd->format('Y-m-d') === $prefDate && $prefDate >= date('Y-m-d') && $prefDate <= date('Y-m-d', strtotime('+15 days'))) ? $prefDate : '';
            $id = Appointment::create([
                'name' => $name, 'phone' => $phone, 'email' => $email, 'message' => $message,
                'service' => $service, 'appointment_type' => $apptType, 'preferred_date' => $prefDate, 'ip' => $ip,
            ]);

            // Reply to the visitor first, then email the admin so the form never waits on SMTP.
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('Connection: close');
            $body = json_encode(['ok' => true]);
            header('Content-Length: ' . strlen($body));
            echo $body;
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            ignore_user_abort(true);

            $this->notifyAdminOfAppointment($id, [
                'name' => $name, 'phone' => $phone, 'email' => $email, 'message' => $message,
                'service' => $service, 'type' => $apptType, 'date' => $prefDate,
            ]);
            exit;
        }

        $this->json(['ok' => true]);
    }

    /**
     * Emails the admin about a new booking. Never throws – a mail failure must not
     * affect the booking, which is already saved.
     */
    private function notifyAdminOfAppointment(int $id, array $a): void
    {
        if (!Mailer::isConfigured() || ADMIN_NOTIFY_EMAIL === '') {
            return;
        }

        try {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('action=appointment&id=' . $id);
            $prefDate = $a['date'] !== '' ? date('d M Y', strtotime($a['date'])) : 'Not specified';

            $rows = [
                'Name' => $a['name'],
                'Phone' => $a['phone'],
                'Email' => $a['email'] !== '' ? $a['email'] : '—',
                'Service' => $a['service'],
                'Appointment type' => $a['type'],
                'Preferred date' => $prefDate,
                'Message' => $a['message'] !== '' ? $a['message'] : '—',
            ];

            $text = "New appointment booking received on " . date('d M Y, h:i A') . "\n\n";
            $html = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#222">'
                . '<h2 style="color:#6b3fa0;margin:0 0 4px">New appointment booking</h2>'
                . '<p style="margin:0 0 16px;color:#666">Received ' . h(date('d M Y, h:i A')) . '</p>'
                . '<table style="width:100%;border-collapse:collapse">';
            foreach ($rows as $label => $value) {
                $text .= "$label: $value\n";
                $html .= '<tr><td style="padding:8px;border-bottom:1px solid #eee;color:#666;width:150px;vertical-align:top">'
                    . h($label) . '</td><td style="padding:8px;border-bottom:1px solid #eee">'
                    . nl2br(h($value)) . '</td></tr>';
            }
            $text .= "\nOpen in admin panel: $link\n";
            $html .= '</table><p style="margin-top:20px"><a href="' . h($link) . '" style="background:#6b3fa0;color:#fff;'
                . 'padding:10px 18px;border-radius:6px;text-decoration:none">View in admin panel</a></p></div>';

            Mailer::send(ADMIN_NOTIFY_EMAIL, 'New appointment: ' . $a['name'] . ' – ' . $a['service'], $html, $text);
        } catch (Throwable $e) {
            error_log('Appointment notification email failed: ' . $e->getMessage());
        }
    }

    public function api(): void
    {
        header('Access-Control-Allow-Origin: *');

        switch ($_GET['r'] ?? '') {
            case 'blogs':
                $posts = array_map(static function (array $p): array {
                    $p['image'] = $p['image'] ? site_url($p['image']) : null;
                    $p['date'] = fmt_date($p['created_at'], 'd M Y');
                    unset($p['created_at']);
                    return $p;
                }, BlogPost::published());
                $this->json(['ok' => true, 'posts' => $posts]);
                // no break – json() exits
            case 'post':
                $post = BlogPost::publishedBySlug(mb_substr((string) ($_GET['slug'] ?? ''), 0, 220));
                if (!$post) {
                    $this->json(['ok' => false, 'error' => 'Article not found.'], 404);
                }
                $this->json(['ok' => true, 'post' => [
                    'title' => $post['title'], 'slug' => $post['slug'], 'category' => $post['category'],
                    'excerpt' => $post['excerpt'], 'content' => clean_blog_html((string) $post['content']),
                    'image' => $post['image'] ? site_url($post['image']) : null,
                    'read_time' => (int) $post['read_time'], 'date' => fmt_date($post['created_at'], 'd M Y'),
                ]]);
            case 'gallery':
                $images = [];
                $videos = [];
                foreach (GalleryItem::all() as $item) {
                    if ($item['type'] === 'video') {
                        $videos[] = ['id' => $item['video_id'], 'title' => $item['title'], 'category' => $item['category']];
                    } else {
                        $images[] = ['src' => site_url($item['file']), 'title' => $item['title'], 'category' => $item['category']];
                    }
                }
                $cats = [];
                foreach (Category::map('gallery') as $slug => $name) {
                    $cats[] = ['key' => (string) $slug, 'name' => $name];
                }
                $this->json(['ok' => true, 'images' => $images, 'videos' => $videos, 'categories' => $cats]);
        }

        $this->json(['ok' => false, 'error' => 'Unknown resource.'], 404);
    }
}
