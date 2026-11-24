<?php

class GalleryItem
{
    /** Starting categories (key => label); after the first run they live in the `categories` table and are managed from the admin. */
    public const DEFAULT_CATEGORIES = [
        'individual' => 'Individual Counselling',
        'child' => 'Child & Adolescent Counselling',
        'relationship' => 'Relationship & Marital Counselling',
        'parenting' => 'Parenting Counselling & Guidance',
        'wellbeing' => "Counselling for Women and Men",
        'older' => 'Emotional Well-being for Seniors',
        'career' => 'Career Guidance & Assessment',
        'workshops' => 'Workshops, Seminars & Motivational Talks',
    ];

    private static function db(): PDO
    {
        return Database::getConnection();
    }

    public static function all(string $type = '', string $category = ''): array
    {
        $where = [];
        $params = [];
        if ($type === 'image' || $type === 'video') {
            $where[] = 'type = :type';
            $params['type'] = $type;
        }
        if ($category !== '') {
            $where[] = 'category = :cat';
            $params['cat'] = $category;
        }
        $sql = 'SELECT * FROM gallery_items' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC, id DESC';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function counts(): array
    {
        $out = ['total' => 0, 'image' => 0, 'video' => 0];
        foreach (self::db()->query('SELECT type, COUNT(*) c FROM gallery_items GROUP BY type') as $r) {
            $out[$r['type']] = (int) $r['c'];
            $out['total'] += (int) $r['c'];
        }
        return $out;
    }

    public static function find(int $id): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM gallery_items WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function createImage(string $title, string $category, string $file): int
    {
        $stmt = self::db()->prepare(
            "INSERT INTO gallery_items (type, title, category, file) VALUES ('image', :t, :c, :f)"
        );
        $stmt->execute(['t' => $title, 'c' => $category, 'f' => $file]);
        return (int) self::db()->lastInsertId();
    }

    public static function createVideo(string $title, string $category, string $videoId): int
    {
        $stmt = self::db()->prepare(
            "INSERT INTO gallery_items (type, title, category, video_id) VALUES ('video', :t, :c, :v)"
        );
        $stmt->execute(['t' => $title, 'c' => $category, 'v' => $videoId]);
        return (int) self::db()->lastInsertId();
    }

    public static function updateImage(int $id, string $title, string $category, ?string $file): void
    {
        $sql = 'UPDATE gallery_items SET title = :t, category = :c' . ($file !== null ? ', file = :f' : '') . " WHERE id = :id AND type = 'image'";
        $params = ['t' => $title, 'c' => $category, 'id' => $id];
        if ($file !== null) {
            $params['f'] = $file;
        }
        self::db()->prepare($sql)->execute($params);
    }

    public static function updateVideo(int $id, string $title, string $category, string $videoId): void
    {
        self::db()->prepare("UPDATE gallery_items SET title = :t, category = :c, video_id = :v WHERE id = :id AND type = 'video'")
            ->execute(['t' => $title, 'c' => $category, 'v' => $videoId, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM gallery_items WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
