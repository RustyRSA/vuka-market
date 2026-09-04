<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('moderate_listings');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? null)) {
    $id = (int)($_POST['id'] ?? 0);
    $inUse = db()->prepare('SELECT COUNT(*) FROM listings WHERE category_id = ?');
    $inUse->execute([$id]);
    if ((int)$inUse->fetchColumn() > 0) {
        set_flash('danger', 'Cannot delete a category that still has listings in it.');
    } else {
        db()->prepare('DELETE FROM categories WHERE category_id = ?')->execute([$id]);
        set_flash('success', 'Category deleted.');
    }
}
redirect('categories.php');
