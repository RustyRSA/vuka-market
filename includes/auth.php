<?php
/**
 * Authentication + Role-Based Access Control (RBAC).
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Attempt login. Returns the user row on success or null on failure. */
function attempt_login(string $email, string $password): ?array
{
    $stmt = db()->prepare(
        'SELECT u.*, r.role_name, r.is_staff
         FROM users u JOIN roles r ON r.role_id = u.role_id
         WHERE u.email = ? LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] !== 'active') {
            return null; // suspended / pending accounts may not log in
        }
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        $_SESSION['permissions'] = load_permissions((int)$user['role_id']);
        return $user;
    }
    return null;
}

/** Load the permission keys granted to a role. */
function load_permissions(int $roleId): array
{
    $stmt = db()->prepare(
        'SELECT p.perm_key FROM role_permissions rp
         JOIN permissions p ON p.permission_id = rp.permission_id
         WHERE rp.role_id = ?'
    );
    $stmt->execute([$roleId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool { return isset($_SESSION['user']); }

/** Does the current user hold a given permission? */
function has_permission(string $key): bool
{
    return in_array($key, $_SESSION['permissions'] ?? [], true);
}

/** Guard a page: must be logged in. */
function require_login(string $loginUrl = 'login.php'): void
{
    if (!is_logged_in()) { redirect($loginUrl); }
}

/** Guard an admin page: must be staff AND (optionally) hold a permission. */
function require_permission(string $key, string $loginUrl = 'login.php'): void
{
    if (!is_logged_in() || empty($_SESSION['user']['is_staff'])) {
        redirect($loginUrl);
    }
    if (!has_permission($key)) {
        http_response_code(403);
        exit('403 - You do not have permission to access this page.');
    }
}

/** Guard: must be logged in AND be a staff member (any admin role). */
function require_staff(string $loginUrl = 'login.php'): void
{
    if (!is_logged_in() || empty($_SESSION['user']['is_staff'])) {
        redirect($loginUrl);
    }
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}
