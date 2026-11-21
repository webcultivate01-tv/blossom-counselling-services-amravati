<?php

class GalleryController extends Controller
{
    private const MAX_FILES_PER_UPLOAD = 20;

    public function index(): void
    {
        AuthController::requireLogin();

        $type = pick($_GET['type'] ?? '', ['image', 'video']);
        $category = pick($_GET['category'] ?? '', array_keys(Category::map('gallery')));

        $this->page('gallery/index', [
            'pageTitle' => 'Gallery Management',
            'pageSubtitle' => 'Upload photos and add videos shown on your gallery page.',
            'active' => 'gallery',
            'items' => GalleryItem::all($type, $category),
            'counts' => GalleryItem::counts(),
            'type' => $type,
            'category' => $category,
            'catMap' => Category::map('gallery'),
        ]);
    }

    public function upload(): void
    {
        $this->guardPost('index.php?action=gallery');

        $category = pick($_POST['category'] ?? '', array_keys(Category::map('gallery')));
        if ($category === '') {
            flash_set('error', 'Please choose a category.');
            $this->redirect('index.php?action=gallery');
        }
        $title = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 150);

        if (($_POST['kind'] ?? '') === 'video') {
            $videoId = youtube_id((string) ($_POST['video_url'] ?? ''));
            if ($videoId === null) {
                flash_set('error', 'Please paste a valid YouTube link.');
            } else {
                GalleryItem::createVideo($title, $category, $videoId);
                flash_set('success', 'Video added to the gallery.');
            }
            $this->redirect('index.php?action=gallery');
        }

        // Image links (one per line), e.g. copied from Google.
        $urls = [];
        $badUrls = 0;
        foreach (preg_split('/\R+/', (string) ($_POST['image_urls'] ?? '')) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }
            $u = clean_web_url($line);
            $u === null ? $badUrls++ : $urls[] = $u;
        }
        $urls = array_slice(array_values(array_unique($urls)), 0, self::MAX_FILES_PER_UPLOAD);

        $files = $this->normalizeFiles($_FILES['images'] ?? null);
        if (!$files && !$urls) {
            flash_set('error', $badUrls ? 'That is not a valid image link. It must start with http:// or https://' : 'Please choose an image or paste an image link.');
            $this->redirect('index.php?action=gallery');
        }
        if (count($files) > self::MAX_FILES_PER_UPLOAD) {
            flash_set('error', 'You can upload up to ' . self::MAX_FILES_PER_UPLOAD . ' images at a time.');
            $this->redirect('index.php?action=gallery');
        }

        $saved = 0;
        $errors = [];
        if ($badUrls) {
            $errors[] = $badUrls . ' link(s) skipped (not a valid http/https link).';
        }
        foreach ($urls as $u) {
            GalleryItem::createImage($title, $category, $u);
            $saved++;
        }
        foreach ($files as $file) {
            try {
                $path = Uploader::saveImage($file, 'gallery');
                GalleryItem::createImage($title, $category, $path); // caption stays empty unless entered
                $saved++;
            } catch (RuntimeException $e) {
                $errors[] = $file['name'] . ': ' . $e->getMessage();
            }
        }

        if ($saved > 0) {
            flash_set('success', $saved . ($saved === 1 ? ' image' : ' images') . ' added.');
        }
        if ($errors) {
            flash_set('error', implode(' ', array_slice($errors, 0, 3)));
        }
        $this->redirect('index.php?action=gallery');
    }

    public function edit(): void
    {
        AuthController::requireLogin();

        $item = GalleryItem::find($this->intParam('id'));
        if (!$item) {
            flash_set('error', 'Item not found.');
            $this->redirect('index.php?action=gallery');
        }
        $this->page('gallery/edit', [
            'pageTitle' => 'Gallery Management',
            'pageSubtitle' => $item['type'] === 'video' ? 'Edit video' : 'Edit photo',
            'active' => 'gallery',
            'item' => $item,
            'catMap' => Category::map('gallery'),
        ]);
    }

    public function update(): void
    {
        $this->guardPost('index.php?action=gallery');

        $id = $this->intParam('id', 'POST');
        $item = GalleryItem::find($id);
        if (!$item) {
            flash_set('error', 'Item not found.');
            $this->redirect('index.php?action=gallery');
        }
        $back = 'index.php?action=gallery_edit&id=' . $id;

        $category = pick($_POST['category'] ?? '', array_keys(Category::map('gallery')));
        if ($category === '') {
            flash_set('error', 'Please choose a category.');
            $this->redirect($back);
        }
        $title = mb_substr(trim((string) ($_POST['title'] ?? '')), 0, 150);

        if ($item['type'] === 'video') {
            $videoId = youtube_id((string) ($_POST['video_url'] ?? ''));
            if ($videoId === null) {
                flash_set('error', 'Please paste a valid YouTube link.');
                $this->redirect($back);
            }
            GalleryItem::updateVideo($id, $title, $category, $videoId);
        } else {
            $newFile = null;
            $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
            try {
                if (Uploader::hasFile($_FILES['image'] ?? null)) {
                    $newFile = Uploader::saveImage($_FILES['image'], 'gallery');
                } elseif ($imageUrl !== '' && $imageUrl !== $item['file']) {
                    $newFile = clean_web_url($imageUrl);
                    if ($newFile === null) {
                        throw new RuntimeException('That is not a valid image link. It must start with http:// or https://');
                    }
                }
            } catch (RuntimeException $e) {
                flash_set('error', $e->getMessage());
                $this->redirect($back);
            }
            GalleryItem::updateImage($id, $title, $category, $newFile);
            if ($newFile !== null) {
                Uploader::delete($item['file']); // no-op for links
            }
        }

        flash_set('success', 'Item updated.');
        $this->redirect('index.php?action=gallery');
    }

    public function delete(): void
    {
        $this->guardPost('index.php?action=gallery');

        $item = GalleryItem::find($this->intParam('id', 'POST'));
        if (!$item) {
            flash_set('error', 'Item not found.');
        } else {
            GalleryItem::delete((int) $item['id']);
            Uploader::delete($item['file']);
            flash_set('success', 'Item deleted.');
        }
        $this->redirect('index.php?action=gallery');
    }

    /** Turns PHP's $_FILES[...]['name'][] structure into a list of single-file arrays (skipping empty slots). */
    private function normalizeFiles(?array $input): array
    {
        if (!$input || !is_array($input['name'] ?? null)) {
            return [];
        }
        $files = [];
        foreach ($input['name'] as $i => $name) {
            if (($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name' => $name,
                'tmp_name' => $input['tmp_name'][$i],
                'size' => $input['size'][$i],
                'error' => $input['error'][$i],
            ];
        }
        return $files;
    }
}
