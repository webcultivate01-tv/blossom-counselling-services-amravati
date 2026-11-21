<?php

class CategoryController extends Controller
{
    private const BACK = 'index.php?action=categories';

    public function index(): void
    {
        AuthController::requireLogin();

        $groups = [];
        foreach (Category::TYPES as $type => $label) {
            $groups[$type] = ['label' => $label, 'rows' => Category::all($type), 'usage' => Category::usageMap($type)];
        }

        $this->page('categories/index', [
            'pageTitle' => 'Category Management',
            'pageSubtitle' => 'Add, rename and remove the categories used for gallery photos and blog articles.',
            'active' => 'categories',
            'groups' => $groups,
        ]);
    }

    public function create(): void
    {
        $this->guardPost(self::BACK);

        $type = pick($_POST['type'] ?? '', array_keys(Category::TYPES));
        $name = $this->cleanName();
        if ($type === '' || !$this->validName($type, $name)) {
            $this->redirect(self::BACK);
        }

        Category::create($type, $name);
        flash_set('success', 'Category "' . $name . '" added.');
        $this->redirect(self::BACK);
    }

    public function update(): void
    {
        $this->guardPost(self::BACK);

        $cat = Category::find($this->intParam('id', 'POST'));
        if (!$cat) {
            flash_set('error', 'Category not found.');
            $this->redirect(self::BACK);
        }
        $name = $this->cleanName();
        if (!$this->validName($cat['type'], $name, (int) $cat['id'])) {
            $this->redirect(self::BACK);
        }

        Category::rename($cat, $name);
        flash_set('success', 'Category updated.');
        $this->redirect(self::BACK);
    }

    public function delete(): void
    {
        $this->guardPost(self::BACK);

        $cat = Category::find($this->intParam('id', 'POST'));
        if (!$cat) {
            flash_set('error', 'Category not found.');
            $this->redirect(self::BACK);
        }

        $used = Category::usage($cat);
        if ($used > 0) {
            $what = $cat['type'] === 'gallery' ? ($used === 1 ? 'gallery item uses' : 'gallery items use') : ($used === 1 ? 'article uses' : 'articles use');
            flash_set('error', 'Cannot delete "' . $cat['name'] . '": ' . $used . ' ' . $what . ' it. Move or delete ' . ($used === 1 ? 'it' : 'them') . ' first.');
            $this->redirect(self::BACK);
        }

        Category::delete((int) $cat['id']);
        flash_set('success', 'Category deleted.');
        $this->redirect(self::BACK);
    }

    private function cleanName(): string
    {
        return mb_substr(trim((string) preg_replace('/\s+/', ' ', (string) ($_POST['name'] ?? ''))), 0, 60);
    }

    private function validName(string $type, string $name, int $ignoreId = 0): bool
    {
        if ($name === '') {
            flash_set('error', 'Please enter a category name.');
            return false;
        }
        if (Category::nameExists($type, $name, $ignoreId)) {
            flash_set('error', 'A ' . strtolower(Category::TYPES[$type]) . ' category named "' . $name . '" already exists.');
            return false;
        }
        return true;
    }
}
