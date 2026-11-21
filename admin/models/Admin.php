<?php

class Admin
{
    public static function findByEmail(string $email): array|false
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, name, email, password FROM admins WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        return $stmt->fetch();
    }

    public static function touchLastLogin(int $id): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE admins SET last_login_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function findById(int $id): array|false
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT id, name, email, password, avatar, last_login_at, created_at FROM admins WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch();
    }

    public static function emailTaken(string $email, int $exceptId): bool
    {
        $stmt = Database::getConnection()->prepare('SELECT COUNT(*) FROM admins WHERE email = :e AND id <> :id');
        $stmt->execute(['e' => $email, 'id' => $exceptId]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function updateProfile(int $id, string $name, string $email): void
    {
        $stmt = Database::getConnection()->prepare('UPDATE admins SET name = :n, email = :e WHERE id = :id');
        $stmt->execute(['n' => $name, 'e' => $email, 'id' => $id]);
    }

    public static function updateAvatar(int $id, ?string $path): void
    {
        $stmt = Database::getConnection()->prepare('UPDATE admins SET avatar = :a WHERE id = :id');
        $stmt->execute(['a' => $path, 'id' => $id]);
    }

    public static function updatePassword(int $id, string $plainPassword): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE admins SET password = :password WHERE id = :id'
        );
        $stmt->execute([
            'password' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'id' => $id,
        ]);
    }

    /** Short fingerprint of the current password hash; changing the password invalidates old tokens. */
    public static function passwordStamp(string $passwordHash): string
    {
        return substr(hash('sha256', $passwordHash), 0, 16);
    }
}
