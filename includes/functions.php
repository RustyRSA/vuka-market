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
