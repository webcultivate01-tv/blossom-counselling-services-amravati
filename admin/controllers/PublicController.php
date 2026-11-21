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
            Appointment::create([
                'name' => $name, 'phone' => $phone, 'email' => $email, 'message' => $message,
                'service' => $service, 'appointment_type' => $apptType, 'preferred_date' => $prefDate, 'ip' => $ip,
            ]);
        }

        $this->json(['ok' => true]);
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
