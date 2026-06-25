<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_roles');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? null)) {
    $id = (int)($_POST['id'] ?? 0);
    $inUse = db()->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
    $inUse->execute([$id]);
    if ((int)$inUse->fetchColumn() > 0) {
        set_flash('danger', 'Cannot delete a role that still has users assigned.');
    } else {
        db()->prepare('DELETE FROM roles WHERE role_id = ?')->execute([$id]);
        set_flash('success', 'Role deleted.');
    }
}
redirect('roles.php');
