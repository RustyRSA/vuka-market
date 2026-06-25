<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_users');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? null)) {
    $id = (int)($_POST['id'] ?? 0);
    if ($id === (int)current_user()['user_id']) {
        set_flash('danger', 'You cannot delete your own account.');
    } else {
        db()->prepare('DELETE FROM users WHERE user_id = ?')->execute([$id]);
        set_flash('success', 'User deleted.');
    }
}
redirect('users.php');
