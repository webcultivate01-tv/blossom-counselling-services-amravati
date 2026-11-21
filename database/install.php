<?php
/**
 * One-time setup script: creates the database/table (if missing) and
 * seeds the default admin account with a securely hashed password.
 *
 * Run it once:
 *   - CLI:     php database/install.php
 *   - Browser: http://localhost/.../database/install.php
 */

require __DIR__ . '/../admin/config/config.php';

$defaultAdminEmail = 'admin@gmail.com';
$defaultAdminPassword = 'admin123';

try {
    // Connect without selecting a DB yet, so we can create it if needed.
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec(
        "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` " .
        "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $pdo->exec('USE `' . DB_NAME . '`');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admins` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL DEFAULT 'Admin',
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `last_login_at` DATETIME NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $hash = password_hash($defaultAdminPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = :email');
    $stmt->execute(['email' => $defaultAdminEmail]);

    if ($stmt->fetch()) {
        $update = $pdo->prepare('UPDATE admins SET password = :password WHERE email = :email');
        $update->execute(['password' => $hash, 'email' => $defaultAdminEmail]);
        $message = "Admin account already existed — password reset to the default.";
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO admins (name, email, password) VALUES (:name, :email, :password)'
        );
        $insert->execute([
            'name' => 'Admin',
            'email' => $defaultAdminEmail,
            'password' => $hash,
        ]);
        $message = "Admin account created successfully.";
    }

    echo "Database \"" . DB_NAME . "\" is ready.\n";
    echo $message . "\n";
    echo "Login email: {$defaultAdminEmail}\n";
    echo "Login password: {$defaultAdminPassword}\n";
    echo "You can now visit /admin to log in.\n";
} catch (PDOException $e) {
    http_response_code(500);
    echo "Setup failed: " . $e->getMessage() . "\n";
}
