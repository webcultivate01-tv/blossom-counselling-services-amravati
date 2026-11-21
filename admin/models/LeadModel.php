<?php
/**
 * Shared behaviour for records that arrive from the website and are worked
 * through a status pipeline (inquiries and appointments).
 */
abstract class LeadModel
{
    /** Table name – a constant defined by the subclass, never user input. */
    protected const TABLE = '';
    /** status key => label */
    public const STATUSES = [];
    public const DEFAULT_STATUS = '';

    protected static function db(): PDO
    {
        return Database::getConnection();
    }

    public static function isStatus(string $status): bool
    {
        return array_key_exists($status, static::STATUSES);
    }

    /** Extra WHERE conditions for subclass-specific filters. */
    protected static function extraWhere(array $filters, array &$params): array
    {
        return [];
    }

    /**
     * @param array{q?:string,status?:string,sort?:string} $filters
     * @return array{rows:array,total:int,pages:int,page:int}
     */
    public static function paginate(array $filters, int $page, int $perPage = 10): array
    {
        $where = [];
        $params = [];

        $q = trim($filters['q'] ?? '');
        if ($q !== '') {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $where[] = '(name LIKE :q1 OR phone LIKE :q2 OR email LIKE :q3 OR message LIKE :q4)';
            $params += ['q1' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
        }

        $status = $filters['status'] ?? '';
        if ($status !== '' && static::isStatus($status)) {
            $where[] = 'status = :status';
            $params['status'] = $status;
        }

        foreach (static::extraWhere($filters, $params) as $cond) {
            $where[] = $cond;
        }

        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $order = ($filters['sort'] ?? 'new') === 'old' ? 'ASC' : 'DESC';

        $stmt = static::db()->prepare('SELECT COUNT(*) FROM ' . static::TABLE . " $sqlWhere");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = static::db()->prepare(
            'SELECT * FROM ' . static::TABLE . " $sqlWhere ORDER BY created_at $order, id $order LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);

        return ['rows' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public static function find(int $id): array|false
    {
        $stmt = static::db()->prepare('SELECT * FROM ' . static::TABLE . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /** @return array<string,int> status key => count, plus 'total' */
    public static function counts(): array
    {
        $counts = array_fill_keys(array_keys(static::STATUSES), 0);
        $total = 0;
        foreach (static::db()->query('SELECT status, COUNT(*) c FROM ' . static::TABLE . ' GROUP BY status') as $row) {
            $counts[$row['status']] = (int) $row['c'];
            $total += (int) $row['c'];
        }
        $counts['total'] = $total;
        return $counts;
    }

    public static function setStatus(int $id, string $status): void
    {
        $stmt = static::db()->prepare('UPDATE ' . static::TABLE . ' SET status = :s WHERE id = :id');
        $stmt->execute(['s' => $status, 'id' => $id]);
    }

    public static function setNotes(int $id, string $notes): void
    {
        $stmt = static::db()->prepare('UPDATE ' . static::TABLE . ' SET admin_notes = :n WHERE id = :id');
        $stmt->execute(['n' => $notes, 'id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = static::db()->prepare('DELETE FROM ' . static::TABLE . ' WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function recent(int $limit = 5): array
    {
        $stmt = static::db()->query('SELECT * FROM ' . static::TABLE . ' ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit);
        return $stmt->fetchAll();
    }

    /** How many records this IP submitted in the last N minutes (spam throttle for the public forms). */
    public static function recentFromIp(string $ip, int $minutes = 10): int
    {
        $stmt = static::db()->prepare(
            'SELECT COUNT(*) FROM ' . static::TABLE . ' WHERE ip = :ip AND created_at > (NOW() - INTERVAL ' . (int) $minutes . ' MINUTE)'
        );
        $stmt->execute(['ip' => $ip]);
        return (int) $stmt->fetchColumn();
    }
}
