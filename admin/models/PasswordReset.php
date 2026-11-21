<?php

class PasswordReset
{
    public const CODE_TTL_MINUTES = 10;
    public const MAX_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    private static function ensureTable(): void
    {
        Database::getConnection()->exec(
            'CREATE TABLE IF NOT EXISTS password_resets (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                admin_id INT UNSIGNED NOT NULL,
                code_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    /** Seconds the admin must still wait before another code may be requested (0 = allowed). */
    public static function cooldownRemaining(int $adminId): int
    {
        self::ensureTable();
        $stmt = Database::getConnection()->prepare(
            'SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age
             FROM password_resets WHERE admin_id = :id ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $adminId]);
        $age = $stmt->fetchColumn();

        return $age === false ? 0 : max(0, self::RESEND_COOLDOWN_SECONDS - (int) $age);
    }

    /** Creates a fresh 6-digit code (replacing older ones) and returns it in plain text for emailing. */
    public static function create(int $adminId): string
    {
        self::ensureTable();
        $db = Database::getConnection();

        $db->prepare('DELETE FROM password_resets WHERE admin_id = :id')->execute(['id' => $adminId]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $db->prepare(
            'INSERT INTO password_resets (admin_id, code_hash, expires_at)
             VALUES (:id, :hash, DATE_ADD(NOW(), INTERVAL ' . self::CODE_TTL_MINUTES . ' MINUTE))'
        )->execute(['id' => $adminId, 'hash' => password_hash($code, PASSWORD_DEFAULT)]);

        return $code;
    }

    /** @return string 'ok' | 'invalid' | 'expired' | 'locked' */
    public static function verify(int $adminId, string $code): string
    {
        self::ensureTable();
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'SELECT id, code_hash, attempts, expires_at < NOW() AS expired
             FROM password_resets WHERE admin_id = :id ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute(['id' => $adminId]);
        $row = $stmt->fetch();

        if (!$row) {
            return 'expired';
        }
        if ((int) $row['expired'] === 1) {
            return 'expired';
        }
        if ((int) $row['attempts'] >= self::MAX_ATTEMPTS) {
            return 'locked';
        }

        if (!password_verify($code, $row['code_hash'])) {
            $db->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE id = :id')
               ->execute(['id' => $row['id']]);
            return 'invalid';
        }

        return 'ok';
    }

    public static function clear(int $adminId): void
    {
        self::ensureTable();
        Database::getConnection()
            ->prepare('DELETE FROM password_resets WHERE admin_id = :id')
            ->execute(['id' => $adminId]);
    }
}
