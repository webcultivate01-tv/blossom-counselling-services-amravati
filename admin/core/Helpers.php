<?php
/**
 * Small shared helpers used by controllers and views.
 */

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Admin URL, e.g. url('action=inquiries&page=2') => /admin/index.php?action=inquiries&page=2 */
function url(string $query = ''): string
{
    return ADMIN_URL . 'index.php' . ($query !== '' ? '?' . $query : '');
}

/** URL of a file on the public website (e.g. uploads/blogs/x.jpg). */
function site_url(string $path = ''): string
{
    // Images added by link (e.g. from Google) are stored as full URLs – use them as they are.
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return SITE_URL . ltrim($path, '/');
}

/** Returns a cleaned http(s) URL, or null when the text is not a usable web link. */
function clean_web_url(string $input): ?string
{
    $input = trim($input);
    if ($input === '' || strlen($input) > 1000 || !preg_match('~^https?://~i', $input)) {
        return null;
    }
    return filter_var($input, FILTER_VALIDATE_URL) ? $input : null;
}

function fmt_date(?string $value, string $format = 'd M Y, h:i A'): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : '—';
}

/* ---------------- Flash messages ---------------- */

function flash_set(string $type, string $message): void
{
    $_SESSION[$type] = $message; // 'error' | 'success' (same keys the login pages use)
}

function flash_get(string $type): ?string
{
    $value = $_SESSION[$type] ?? null;
    unset($_SESSION[$type]);
    return $value;
}

/* ---------------- CSRF ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

/* ---------------- Misc ---------------- */

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? substr($text, 0, 120) : 'post';
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
}

/** Keeps only the allowed keys of a value; returns $default when it isn't allowed. */
function pick(?string $value, array $allowed, string $default = ''): string
{
    return in_array($value, $allowed, true) ? $value : $default;
}

/** Extracts the 11-char YouTube video id from a URL (or a bare id). Returns null if invalid. */
function youtube_id(string $input): ?string
{
    $input = trim($input);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
        return $input;
    }
    if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $input, $m)) {
        return $m[1];
    }
    return null;
}

/** Inline SVG icon (Feather-style, 24px grid). */
function icon(string $name, int $size = 18): string
{
    static $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'inbox' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'blog' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="M21 16l-5-5-8 8"/>',
        'tag' => '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3.2 3 14.8 0 18M12 3c-3 3.2-3 14.8 0 18"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'trash' => '<path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.2-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'refresh' => '<path d="M21 12a9 9 0 0 1-15.5 6.2L3 16"/><path d="M3 21v-5h5M3 12A9 9 0 0 1 18.5 5.8L21 8"/><path d="M21 3v5h-5"/>',
        'check' => '<path d="M5 12l5 5 9-10"/>',
        'lock' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="14" r="3.5"/>',
        'video' => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="M16 10l5-3v10l-5-3"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'x' => '<path d="M18 6L6 18M6 6l12 12"/>',
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-9a1 1 0 0 0-1-1H5a2 2 0 0 1-2-2z"/><circle cx="16" cy="14" r="1.2"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
    ];
    $p = $paths[$name] ?? '';
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/** CSS class suffix for a status pill. */
function status_class(string $status): string
{
    return 'st-' . preg_replace('/[^a-z_]/', '', $status);
}

/** "Avatar" markup: uploaded photo, or initial letter. */
function avatar_html(array $admin, string $class = 'avatar'): string
{
    if (!empty($admin['avatar'])) {
        return '<img class="' . $class . ' avatar-img" src="' . h(site_url($admin['avatar'])) . '" alt="">';
    }
    $initial = strtoupper(mb_substr($admin['name'] ?: $admin['email'], 0, 1));
    return '<div class="' . $class . '">' . h($initial) . '</div>';
}

/* ---------------- Blog article HTML ---------------- */

/**
 * Turns whatever the editor sent into safe HTML. Only a small allow-list of formatting tags survives;
 * scripts, event handlers, inline styles (except text-align) and unknown tags are removed.
 */
function clean_blog_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    // Older posts were plain text: keep their line breaks / blank lines.
    if (!preg_match('/<\s*\/?\s*[a-z][^>]*>/i', $html)) {
        $paras = preg_split('/\n{2,}/', str_replace("\r", '', $html)) ?: [];
        $html = '';
        foreach ($paras as $p) {
            $html .= '<p>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES, 'UTF-8'), false) . '</p>';
        }
    }

    $allowed = [
        'p' => [], 'br' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
        'u' => [], 's' => [], 'strike' => [], 'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'hr' => [],
        'a' => ['href'], 'div' => [], 'span' => [], 'sub' => [], 'sup' => [],
    ];
    $dropWithContent = ['script', 'style', 'iframe', 'object', 'embed', 'noscript', 'template', 'svg', 'math', 'form', 'textarea', 'select', 'button'];

    $dom = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }

    $walk = function (DOMNode $node) use (&$walk, $allowed, $dropWithContent, $dom): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);
                continue;
            }
            if (!($child instanceof DOMElement)) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, $dropWithContent, true)) {
                $node->removeChild($child);
                continue;
            }
            $walk($child);
            if (!isset($allowed[$tag])) {
                // Unknown tag: keep its text/children, drop the tag itself.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }
            $style = (string) $child->getAttribute('style');
            $align = preg_match('/text-align\s*:\s*(left|right|center|justify)/i', $style, $m) ? strtolower($m[1]) : '';
            $href = (string) $child->getAttribute('href');
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->name);
            }
            if ($align !== '' && in_array($tag, ['p', 'div', 'h2', 'h3', 'h4', 'li', 'blockquote'], true)) {
                $child->setAttribute('style', 'text-align:' . $align);
            }
            if ($tag === 'a') {
                $link = clean_web_url($href);
                if ($link === null && preg_match('~^(mailto:|tel:)[^\s"<>]+$~i', $href)) {
                    $link = $href;
                }
                if ($link === null) {
                    // Not a safe link: keep the words only.
                    while ($child->firstChild) {
                        $node->insertBefore($child->firstChild, $child);
                    }
                    $node->removeChild($child);
                    continue;
                }
                $child->setAttribute('href', $link);
                $child->setAttribute('target', '_blank');
                $child->setAttribute('rel', 'noopener noreferrer');
            }
        }
    };
    $walk($body);

    $out = '';
    foreach ($body->childNodes as $n) {
        $out .= $dom->saveHTML($n);
    }
    // Loose text directly under the root becomes a paragraph so the page styling applies.
    if (!preg_match('/<(p|h[2-4]|ul|ol|blockquote|div|hr)\b/i', $out) && trim(strip_tags($out)) !== '') {
        $out = '<p>' . $out . '</p>';
    }
    return trim($out);
}
