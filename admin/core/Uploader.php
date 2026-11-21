<?php
/**
 * Validated image uploads. Files go to <site root>/uploads/<folder>/ with random names.
 */
class Uploader
{
    public const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /** True when the user actually picked a file in this input. */
    public static function hasFile(?array $file): bool
    {
        return $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Validates and stores an uploaded image.
     * @return string relative path such as "uploads/blogs/ab12cd34.jpg"
     * @throws RuntimeException with a message that is safe to show to the admin
     */
    public static function saveImage(array $file, string $folder): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException('That image is too large (max 5 MB).');
        }
        if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('The image could not be uploaded. Please try again.');
        }
        if ($file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('That image is too large (max 5 MB).');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
        }

        $dir = UPLOAD_DIR . '/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('The uploads folder is not writable.');
        }

        $name = bin2hex(random_bytes(10)) . '.' . self::TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('The image could not be saved. Check folder permissions.');
        }

        return 'uploads/' . $folder . '/' . $name;
    }

    /** Deletes a previously uploaded file. Ignores anything outside uploads/. */
    public static function delete(?string $relative): void
    {
        if (!$relative || !str_starts_with($relative, 'uploads/') || str_contains($relative, '..')) {
            return;
        }
        $path = dirname(UPLOAD_DIR) . '/' . $relative;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
