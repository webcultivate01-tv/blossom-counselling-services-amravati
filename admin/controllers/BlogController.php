<?php

class BlogController extends Controller
{
    public function index(): void
    {
        AuthController::requireLogin();

        $status = (string) ($_GET['status'] ?? '');
        $filters = [
            'q' => mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 100),
            'status' => isset(BlogPost::STATUSES[$status]) ? $status : '',
        ];
        $result = BlogPost::paginate($filters, max(1, (int) ($_GET['page'] ?? 1)));

        $this->page('blog/index', [
            'pageTitle' => 'Blog Management',
            'pageSubtitle' => 'Write, publish and manage the articles shown on your blog page.',
            'active' => 'blogs',
            'filters' => $filters,
            'result' => $result,
            'counts' => BlogPost::counts(),
        ]);
    }

    public function form(): void
    {
        AuthController::requireLogin();

        $id = $this->intParam('id');
        $post = null;
        if ($id > 0) {
            $post = BlogPost::find($id);
            if (!$post) {
                flash_set('error', 'Blog post not found.');
                $this->redirect('index.php?action=blogs');
            }
        }

        // Values to show again after a validation error.
        $old = $_SESSION['old_blog'] ?? null;
        unset($_SESSION['old_blog']);
        if (is_array($old) && (int) ($old['id'] ?? 0) === $id) {
            $post = array_merge($post ?: ['image' => null], $old);
        }

        $this->page('blog/form', [
            'pageTitle' => 'Blog Management',
            'pageSubtitle' => $id > 0 ? 'Edit article' : 'Write a new article',
            'active' => 'blogs',
            'post' => $post ?: [
                'id' => 0, 'title' => '', 'category' => '', 'excerpt' => '', 'content' => '',
                'image' => null, 'read_time' => 5, 'status' => 'published',
            ],
        ]);
    }

    public function save(): void
    {
        $this->guardPost('index.php?action=blogs');

        $id = $this->intParam('id', 'POST');
        $existing = $id > 0 ? BlogPost::find($id) : false;
        if ($id > 0 && !$existing) {
            flash_set('error', 'Blog post not found.');
            $this->redirect('index.php?action=blogs');
        }

        $data = [
            'title' => mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 200),
            'category' => mb_substr(trim((string) ($_POST['category'] ?? '')), 0, 60),
            'excerpt' => mb_substr(trim((string) ($_POST['excerpt'] ?? '')), 0, 400),
            'content' => clean_blog_html((string) ($_POST['content'] ?? '')),
            'read_time' => min(60, max(1, (int) ($_POST['read_time'] ?? 5))),
            'status' => pick($_POST['status'] ?? '', array_keys(BlogPost::STATUSES), 'draft'),
        ];
        if ($data['category'] === '') {
            $data['category'] = 'General';
        }

        $back = 'index.php?action=blog_form' . ($id > 0 ? '&id=' . $id : '');
        $fail = function (string $message) use ($data, $id, $back): void {
            $_SESSION['old_blog'] = $data + ['id' => $id];
            flash_set('error', $message);
            $this->redirect($back);
        };

        if ($data['title'] === '') {
            $fail('Please enter a title.');
        }
        if (trim(preg_replace('/[\s\x{00A0}]+/u', ' ', html_entity_decode(strip_tags($data['content']))) ?? '') === '' && !str_contains($data['content'], '<hr')) {
            $fail('Please write the article content.');
        }
        if ($data['excerpt'] === '') {
            $plain = trim(preg_replace('/\s+/', ' ', strip_tags($data['content'])) ?? '');
            $data['excerpt'] = mb_strlen($plain) > 160 ? mb_substr($plain, 0, 157) . '…' : $plain;
        }

        // Cover image
        $oldImage = $existing['image'] ?? null;
        $data['image'] = $oldImage;
        $newImage = null;
        try {
            if (Uploader::hasFile($_FILES['image'] ?? null)) {
                $newImage = Uploader::saveImage($_FILES['image'], 'blogs');
                $data['image'] = $newImage;
            } elseif (!empty($_POST['remove_image'])) {
                $data['image'] = null;
            } elseif (trim((string) ($_POST['image_url'] ?? '')) !== '') {
                $link = clean_web_url((string) $_POST['image_url']);
                if ($link === null) {
                    throw new RuntimeException('That is not a valid image link. It must start with http:// or https://');
                }
                $data['image'] = $link;
            } elseif (isset($_POST['image_url']) && $oldImage && preg_match('~^https?://~i', $oldImage)) {
                $data['image'] = null; // link field was cleared
            }
        } catch (RuntimeException $e) {
            $fail($e->getMessage());
        }

        if ($existing) {
            BlogPost::update($id, $data);
            $message = 'Article updated.';
        } else {
            $id = BlogPost::create($data);
            $message = 'Article created.';
        }

        if ($oldImage && $oldImage !== $data['image']) {
            Uploader::delete($oldImage);
        }

        flash_set('success', $message);
        $this->redirect('index.php?action=blogs');
    }

    public function delete(): void
    {
        $this->guardPost('index.php?action=blogs');

        $post = BlogPost::find($this->intParam('id', 'POST'));
        if (!$post) {
            flash_set('error', 'Blog post not found.');
        } else {
            BlogPost::delete((int) $post['id']);
            Uploader::delete($post['image']);
            flash_set('success', 'Article deleted.');
        }
        $this->redirect('index.php?action=blogs');
    }
}
