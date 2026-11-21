<?php

class BlogPost
{
    public const STATUSES = ['published' => 'Published', 'draft' => 'Draft'];

    /** Suggestions only – any category text is accepted (the website builds its filters from the tags). */
    public const CATEGORIES = ['Marriage', 'Children', 'Stress', 'Personal', 'Senior', 'Depression', 'Relationship', 'Financial'];

    private static function db(): PDO
    {
        return Database::getConnection();
    }

    /** @return array{rows:array,total:int,pages:int,page:int} */
    public static function paginate(array $filters, int $page, int $perPage = 10): array
    {
        $where = [];
        $params = [];

        $q = trim($filters['q'] ?? '');
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(title LIKE :q1 OR category LIKE :q2 OR excerpt LIKE :q3)';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
        }
        $status = $filters['status'] ?? '';
        if (isset(self::STATUSES[$status])) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = self::db()->prepare("SELECT COUNT(*) FROM blog_posts $sqlWhere");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = self::db()->prepare(
            "SELECT id, title, slug, category, excerpt, image, read_time, status, created_at, updated_at
             FROM blog_posts $sqlWhere ORDER BY created_at DESC, id DESC LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function find(int $id): array|false
    {
        $stmt = self::db()->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public static function counts(): array
    {
        $out = ['total' => 0, 'published' => 0, 'draft' => 0];
        foreach (self::db()->query('SELECT status, COUNT(*) c FROM blog_posts GROUP BY status') as $r) {
            $out[$r['status']] = (int) $r['c'];
            $out['total'] += (int) $r['c'];
        }
        return $out;
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = slugify($title);
        $slug = $base;
        $i = 2;
        $stmt = self::db()->prepare('SELECT COUNT(*) FROM blog_posts WHERE slug = :s AND id <> :id');
        while (true) {
            $stmt->execute(['s' => $slug, 'id' => $ignoreId ?? 0]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }

    public static function create(array $d): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO blog_posts (title, slug, category, excerpt, content, image, read_time, status)
             VALUES (:title, :slug, :category, :excerpt, :content, :image, :read_time, :status)'
        );
        $stmt->execute([
            'title' => $d['title'],
            'slug' => self::uniqueSlug($d['title']),
            'category' => $d['category'],
            'excerpt' => $d['excerpt'],
            'content' => $d['content'],
            'image' => $d['image'],
            'read_time' => $d['read_time'],
            'status' => $d['status'],
        ]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = self::db()->prepare(
            'UPDATE blog_posts SET title = :title, category = :category, excerpt = :excerpt, content = :content,
                    image = :image, read_time = :read_time, status = :status WHERE id = :id'
        );
        $stmt->execute([
            'title' => $d['title'],
            'category' => $d['category'],
            'excerpt' => $d['excerpt'],
            'content' => $d['content'],
            'image' => $d['image'],
            'read_time' => $d['read_time'],
            'status' => $d['status'],
            'id' => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = self::db()->prepare('DELETE FROM blog_posts WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function published(): array
    {
        return self::db()->query(
            "SELECT title, slug, category, excerpt, image, read_time, created_at
             FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC, id DESC"
        )->fetchAll();
    }

    public static function publishedBySlug(string $slug): array|false
    {
        $stmt = self::db()->prepare("SELECT * FROM blog_posts WHERE slug = :s AND status = 'published' LIMIT 1");
        $stmt->execute(['s' => $slug]);
        return $stmt->fetch();
    }
}
