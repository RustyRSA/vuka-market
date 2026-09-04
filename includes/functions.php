<?php
/** Small shared helpers. */

/** Escape output to prevent XSS. */
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); }

/** Format an amount as South African Rand. */
function money($amount): string { return 'R' . number_format((float)$amount, 2); }

/** Redirect helper. */
function redirect(string $path): void { header('Location: ' . $path); exit; }

/** One-time flash messages. */
function set_flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function get_flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/** CSRF token helpers. */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_check(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }

/**
 * Validate and store an uploaded listing photo, then record it in listing_images.
 * Returns the stored relative path, or null if nothing valid was uploaded.
 */
function save_listing_image(int $listingId, array $file, bool $primary = true): ?string
{
    if (empty($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;                                   // no file chosen - not an error
    }
    if ($file['size'] > 3 * 1024 * 1024) {             // 3 MB cap
        return null;
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $info = @getimagesize($file['tmp_name']);          // verifies it really is an image
    if (!$info || !isset($allowed[$info['mime']])) {
        return null;
    }
    $dir = __DIR__ . '/../public/assets/img/uploads';
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return null;
    }
    $name = 'listing' . $listingId . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$info['mime']];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        return null;
    }
    $path = 'assets/img/uploads/' . $name;
    if ($primary) {
        db()->prepare('UPDATE listing_images SET is_primary = 0 WHERE listing_id = ?')->execute([$listingId]);
    }
    db()->prepare('INSERT INTO listing_images (listing_id, file_path, is_primary) VALUES (?, ?, ?)')
        ->execute([$listingId, $path, $primary ? 1 : 0]);
    return $path;
}
