<?php

/**
 * Manageable categories for the two content areas.
 *  - gallery: items store the category's `slug` (stable key, survives renames)
 *  - blog:    posts store the category's `name` (the website shows that text), so renames are copied to the posts
 */
class Category
{
    public const TYPES = ['gallery' => 'Gallery', 'blog' => 'Blog'];

    private static function db(): PDO
    {
        return Database::getConnection();
    }

    public static function all(string $type): array
    {
        $stmt = self::db()->prepare('SELECT * FROM categories WHERE type = :t ORDER BY name');
        $stmt->execute(['t' => $type]);
        return $stmt->fetchAll();
    }

    /** slug => name */
    public static function map(string $type): array
    {
        $out = [];
        foreach (self::all($type) as $c) {
            $out[$c['slug']] = $c['name'];
        }
        return $out;
    }

    public static function find(int $id): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function nameExists(string $type, string $name, int $ignoreId = 0): bool
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM categories WHERE type = :t AND name = :n AND id <> :id');
        $stmt->execute(['t' => $type, 'n' => $name, 'id' => $ignoreId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** How many gallery items / blog posts use the category. */
    public static function usage(array $cat): int
    {
        if ($cat['type'] === 'gallery') {
            $stmt = self::db()->prepare('SELECT COUNT(*) FROM gallery_items WHERE category = :v');
            $stmt->execute(['v' => $cat['slug']]);
        } else {
            $stmt = self::db()->prepare('SELECT COUNT(*) FROM blog_posts WHERE category = :v');
            $stmt->execute(['v' => $cat['name']]);
        }
        return (int) $stmt->fetchColumn();
    }

    /** Usage count per category id for one type. */
    public static function usageMap(string $type): array
    {
        $out = [];
        foreach (self::all($type) as $c) {
            $out[(int) $c['id']] = self::usage($c);
        }
        return $out;
    }

    public static function create(string $type, string $name): int
    {
        $base = substr(trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-'), 0, 24) ?: 'cat';
        if (ctype_digit($base)) {
            $base = 'c-' . $base; // keep keys non-numeric so PHP never turns them into integer array keys
        }
        $slug = $base;
        $i = 2;
        $exists = self::db()->prepare('SELECT COUNT(*) FROM categories WHERE type = :t AND slug = :s');
        while (true) {
            $exists->execute(['t' => $type, 's' => $slug]);
            if ((int) $exists->fetchColumn() === 0) {
                break;
            }
            $slug = $base . '-' . $i++;
        }
        self::db()->prepare('INSERT INTO categories (type, slug, name) VALUES (:t, :s, :n)')
            ->execute(['t' => $type, 's' => $slug, 'n' => $name]);
        return (int) self::db()->lastInsertId();
    }

    public static function rename(array $cat, string $name): void
    {
        self::db()->prepare('UPDATE categories SET name = :n WHERE id = :id')->execute(['n' => $name, 'id' => $cat['id']]);
        if ($cat['type'] === 'blog' && $cat['name'] !== $name) {
            self::db()->prepare('UPDATE blog_posts SET category = :new WHERE category = :old')
                ->execute(['new' => $name, 'old' => $cat['name']]);
        }
    }

    public static function delete(int $id): void
    {
        self::db()->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
    }
}
