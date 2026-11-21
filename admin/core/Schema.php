<?php
/**
 * Creates the tables the admin sections need if they don't exist yet,
 * so no manual migration is required after pulling these features.
 * (database/schema.sql contains the same definitions for manual setup.)
 */
class Schema
{
    public static function ensure(): void
    {
        $pdo = Database::getConnection();

        // Retired appointment status (no_show) -> not_interested (no-op once migrated).
        try { $pdo->exec("UPDATE `appointments` SET `status`='not_interested' WHERE `status` IN ('no_show')"); } catch (Throwable $e) {}

        // Preferred date + payment columns on appointments (no-op once added).
        try {
            if ($pdo->query("SHOW TABLES LIKE 'appointments'")->fetch() && !$pdo->query("SHOW COLUMNS FROM `appointments` LIKE 'payment_amount'")->fetch()) {
                $pdo->exec("ALTER TABLE `appointments`
                    ADD COLUMN `preferred_date` DATE NULL AFTER `message`,
                    ADD COLUMN `payment_amount` DECIMAL(10,2) NULL,
                    ADD COLUMN `payment_mode` VARCHAR(20) NULL,
                    ADD COLUMN `completed_at` DATETIME NULL");
            }
        } catch (Throwable $e) { error_log('[Schema] ' . $e->getMessage()); }

        self::ensurePayments($pdo);

        // Cheap gate: one query when everything already exists.
        $missing = $pdo->query("SHOW TABLES LIKE 'gallery_items'")->fetch();
        $hasAvatar = $pdo->query("SHOW COLUMNS FROM `admins` LIKE 'avatar'")->fetch();
        $fileCol = $missing ? $pdo->query("SHOW COLUMNS FROM `gallery_items` LIKE 'file'")->fetch() : null;
        $narrow = $fileCol && stripos((string) $fileCol['Type'], '(255)') !== false;
        $hasCats = $pdo->query("SHOW TABLES LIKE 'categories'")->fetch();
        if ($missing && $hasAvatar && !$narrow && $hasCats) {
            self::seedGallery($pdo);
            self::seedBlogs($pdo);
            self::seedCategories($pdo);
            return;
        }

        // Image links (Google etc.) can be longer than 255 characters.
        if ($narrow) {
            $pdo->exec('ALTER TABLE `gallery_items` MODIFY `file` VARCHAR(1000) NULL');
            $pdo->exec('ALTER TABLE `blog_posts` MODIFY `image` VARCHAR(1000) NULL');
            // Old category keys -> service ids used by the website.
            foreach (['children' => 'child', 'marriage' => 'relationship', 'senior' => 'older', 'personal' => 'individual', 'stress' => 'individual', 'depression' => 'individual'] as $old => $new) {
                $pdo->prepare('UPDATE `gallery_items` SET category = :n WHERE category = :o')->execute(['n' => $new, 'o' => $old]);
            }
        }

        $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        if (!$hasAvatar) {
            $pdo->exec('ALTER TABLE `admins` ADD COLUMN `avatar` VARCHAR(255) NULL AFTER `password`');
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS `inquiries` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(30) NOT NULL DEFAULT '',
            `email` VARCHAR(150) NOT NULL DEFAULT '',
            `subject` VARCHAR(150) NOT NULL DEFAULT '',
            `message` TEXT NOT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'new',
            `admin_notes` TEXT NULL,
            `ip` VARCHAR(45) NOT NULL DEFAULT '',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`status`), INDEX (`created_at`), INDEX (`ip`, `created_at`)
        ) $opts");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `appointments` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `phone` VARCHAR(30) NOT NULL DEFAULT '',
            `email` VARCHAR(150) NOT NULL DEFAULT '',
            `service` VARCHAR(100) NOT NULL DEFAULT '',
            `appointment_type` VARCHAR(100) NOT NULL DEFAULT '',
            `message` TEXT NULL,
            `status` VARCHAR(30) NOT NULL DEFAULT 'pending',
            `preferred_date` DATE NULL,
            `scheduled_at` DATETIME NULL,
            `payment_amount` DECIMAL(10,2) NULL,
            `payment_mode` VARCHAR(20) NULL,
            `completed_at` DATETIME NULL,
            `admin_notes` TEXT NULL,
            `ip` VARCHAR(45) NOT NULL DEFAULT '',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`status`), INDEX (`created_at`), INDEX (`ip`, `created_at`)
        ) $opts");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `blog_posts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(200) NOT NULL,
            `slug` VARCHAR(220) NOT NULL UNIQUE,
            `category` VARCHAR(60) NOT NULL DEFAULT 'General',
            `excerpt` VARCHAR(400) NOT NULL DEFAULT '',
            `content` MEDIUMTEXT NOT NULL,
            `image` VARCHAR(1000) NULL,
            `read_time` TINYINT UNSIGNED NOT NULL DEFAULT 5,
            `status` VARCHAR(20) NOT NULL DEFAULT 'published',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`status`), INDEX (`created_at`)
        ) $opts");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `gallery_items` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type` VARCHAR(10) NOT NULL DEFAULT 'image',
            `title` VARCHAR(150) NOT NULL DEFAULT '',
            `category` VARCHAR(30) NOT NULL DEFAULT 'personal',
            `file` VARCHAR(1000) NULL,
            `video_id` VARCHAR(20) NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (`type`), INDEX (`category`)
        ) $opts");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `type` VARCHAR(10) NOT NULL,
            `slug` VARCHAR(30) NOT NULL,
            `name` VARCHAR(60) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `type_slug` (`type`, `slug`)
        ) $opts");

        self::seedGallery($pdo);
        self::seedBlogs($pdo);
        self::seedCategories($pdo);
    }

    /** One-time import of the three articles that used to be static pages, so they can be managed from the admin. */
    private static function seedBlogs(PDO $pdo): void
    {
        $done = $pdo->prepare('SELECT 1 FROM `app_flags` WHERE name = :n');
        $done->execute(['n' => 'blogs_seeded']);
        if ($done->fetch()) {
            return;
        }
        $ins = $pdo->prepare(
            "INSERT INTO `blog_posts` (title, slug, category, excerpt, content, image, read_time, status, created_at)
             VALUES (:title, :slug, :category, :excerpt, :content, :image, :read_time, 'published', :created_at)"
        );
        foreach ((require __DIR__ . '/BlogSeed.php') as $p) {
            $ins->execute([
                'title' => $p['title'], 'slug' => BlogPost::uniqueSlug($p['title']), 'category' => $p['category'],
                'excerpt' => $p['excerpt'], 'content' => $p['content'], 'image' => $p['image'],
                'read_time' => $p['read_time'], 'created_at' => $p['created_at'],
            ]);
        }
        $pdo->prepare('INSERT IGNORE INTO `app_flags` (name) VALUES (:n)')->execute(['n' => 'blogs_seeded']);
    }

    /**
     * Payment ledger: every payment / refund taken against an appointment.
     * Existing single-payment appointments are copied in once so no history is lost.
     */
    private static function ensurePayments(PDO $pdo): void
    {
        try {
            if (!$pdo->query("SHOW TABLES LIKE 'appointments'")->fetch()) {
                return; // created further down on a fresh install
            }
            if ($pdo->query("SHOW TABLES LIKE 'payments'")->fetch()) {
                return;
            }
            $pdo->exec("CREATE TABLE `payments` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `appointment_id` INT UNSIGNED NOT NULL,
                `kind` VARCHAR(10) NOT NULL DEFAULT 'payment',
                `amount` DECIMAL(10,2) NOT NULL,
                `mode` VARCHAR(20) NOT NULL DEFAULT 'Cash',
                `reference` VARCHAR(100) NOT NULL DEFAULT '',
                `note` VARCHAR(255) NOT NULL DEFAULT '',
                `paid_at` DATETIME NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX (`appointment_id`), INDEX (`paid_at`), INDEX (`mode`),
                CONSTRAINT `fk_payments_appointment` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("INSERT INTO `payments` (appointment_id, kind, amount, mode, paid_at)
                SELECT id, 'payment', payment_amount, COALESCE(NULLIF(payment_mode, ''), 'Other'), COALESCE(completed_at, created_at)
                FROM `appointments` WHERE payment_amount IS NOT NULL AND payment_amount > 0");
        } catch (Throwable $e) {
            error_log('[Schema] payments: ' . $e->getMessage());
        }
    }

    /** One-time import of the categories that used to be hard-coded (gallery) or implied by existing posts (blog). */
    private static function seedCategories(PDO $pdo): void
    {
        $done = $pdo->prepare('SELECT 1 FROM `app_flags` WHERE name = :n');
        $done->execute(['n' => 'categories_seeded']);
        if ($done->fetch()) {
            return;
        }
        $ins = $pdo->prepare('INSERT IGNORE INTO `categories` (type, slug, name) VALUES (:t, :s, :n)');
        foreach (GalleryItem::DEFAULT_CATEGORIES as $slug => $name) {
            $ins->execute(['t' => 'gallery', 's' => $slug, 'n' => $name]);
        }
        $blog = array_merge(['General'], BlogPost::CATEGORIES);
        foreach ($pdo->query("SELECT DISTINCT category FROM blog_posts WHERE category <> ''") as $r) {
            $blog[] = $r['category'];
        }
        foreach (array_unique($blog) as $name) {
            $ins->execute(['t' => 'blog', 's' => mb_substr($name, 0, 30), 'n' => $name]);
        }
        $pdo->prepare('INSERT IGNORE INTO `app_flags` (name) VALUES (:n)')->execute(['n' => 'categories_seeded']);
    }

    /** One-time import of the photos that were hard-coded on gallery.html, so they can be managed from the admin. */
    private static function seedGallery(PDO $pdo): void
    {
        if (!$pdo->query("SHOW TABLES LIKE 'app_flags'")->fetch()) {
            $pdo->exec('CREATE TABLE IF NOT EXISTS `app_flags` (`name` VARCHAR(60) PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        }
        $done = $pdo->prepare('SELECT 1 FROM `app_flags` WHERE name = :n');
        $done->execute(['n' => 'gallery_seeded']);
        if ($done->fetch()) {
            return;
        }
        $u = static fn (string $id): string => 'https://images.unsplash.com/photo-' . $id . '?w=600&q=75';
        $rows = [
            ['child', 'Children Counseling Session', '1503454537195-1dcabb73ffb9'],
            ['relationship', 'Marriage Counseling', '1529156069898-49953e39b3ac'],
            ['older', 'Senior Citizen Counseling', '1581579438747-1dc8d17bbce4'],
            ['individual', 'Personal Counseling', '1573497019940-1c28c88b4f3e'],
            ['individual', 'Stress & Anxiety Management', '1506126613408-eca07ce68773'],
            ['individual', 'Depression Support', '1541199249251-f713e6145474'],
            ['child', 'Art Therapy for Children', '1560969184-10fe8719e047'],
            ['relationship', 'Relationship Workshop', '1516589178581-6cd7833ae3b2'],
            ['individual', 'Mindfulness & Relaxation', '1544367567-0f2fcb009e0b'],
            ['individual', 'One-on-One Counseling', '1544027993-37dbfe43562a'],
            ['older', 'Senior Wellness', '1516307365426-bea591f05011'],
            ['individual', 'Healing & Recovery', '1499209974431-9dddcece7f88'],
        ];
        $ins = $pdo->prepare("INSERT INTO gallery_items (type, title, category, file) VALUES ('image', :t, :c, :f)");
        foreach ($rows as [$cat, $title, $id]) {
            $ins->execute(['t' => $title, 'c' => $cat, 'f' => $u($id)]);
        }
        $pdo->prepare('INSERT IGNORE INTO `app_flags` (name) VALUES (:n)')->execute(['n' => 'gallery_seeded']);
    }
}
