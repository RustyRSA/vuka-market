<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_roles');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isNew = ($id === 0);
$pageTitle = $isNew ? 'New role' : 'Edit role';
$errors = [];

$allPerms = db()->query('SELECT * FROM permissions ORDER BY permission_id')->fetchAll();
$role = ['role_name' => '', 'description' => '', 'is_staff' => 0];
$rolePerms = [];

if (!$isNew) {
    $stmt = db()->prepare('SELECT * FROM roles WHERE role_id = ?');
    $stmt->execute([$id]);
    $role = $stmt->fetch() ?: $role;
    $rp = db()->prepare('SELECT permission_id FROM role_permissions WHERE role_id = ?');
    $rp->execute([$id]);
    $rolePerms = $rp->fetchAll(PDO::FETCH_COLUMN);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { $errors[] = 'Invalid session token.'; }
    $name = trim($_POST['role_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $staff = isset($_POST['is_staff']) ? 1 : 0;
    $perms = array_map('intval', $_POST['perms'] ?? []);
    if ($name === '') { $errors[] = 'Role name is required.'; }

    if (!$errors) {
        $pdo = db();
        if ($isNew) {
            $pdo->prepare('INSERT INTO roles (role_name, description, is_staff) VALUES (?, ?, ?)')
                ->execute([$name, $desc, $staff]);
            $id = (int)$pdo->lastInsertId();
        } else {
            $pdo->prepare('UPDATE roles SET role_name=?, description=?, is_staff=? WHERE role_id=?')
                ->execute([$name, $desc, $staff, $id]);
        }
        // Re-sync permissions
        $pdo->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$id]);
        $insP = $pdo->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
        foreach ($perms as $pid) { $insP->execute([$id, $pid]); }

        set_flash('success', $isNew ? 'Role created.' : 'Role updated.');
        redirect('roles.php');
    }
    $role = ['role_name' => $name, 'description' => $desc, 'is_staff' => $staff];
    $rolePerms = $perms;
}

require_once __DIR__ . '/_header.php';
?>
<h3 class="mb-3"><?= $isNew ? 'Create role' : 'Edit role' ?></h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card p-4" style="max-width:640px">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="mb-3"><label class="form-label">Role name</label>
    <input name="role_name" class="form-control" value="<?= e($role['role_name']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Description</label>
    <input name="description" class="form-control" value="<?= e($role['description']) ?>"></div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="is_staff" id="staff" <?= $role['is_staff'] ? 'checked' : '' ?>>
    <label class="form-check-label" for="staff">Can access the admin site</label>
  </div>
  <label class="form-label">Permissions</label>
  <?php foreach ($allPerms as $p): ?>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="perms[]" value="<?= (int)$p['permission_id'] ?>"
        id="p<?= (int)$p['permission_id'] ?>" <?= in_array((int)$p['permission_id'], array_map('intval',$rolePerms), true) ? 'checked' : '' ?>>
      <label class="form-check-label" for="p<?= (int)$p['permission_id'] ?>">
        <code><?= e($p['perm_key']) ?></code> &ndash; <?= e($p['description']) ?>
      </label>
    </div>
  <?php endforeach; ?>
  <div class="mt-3"><button class="btn btn-primary">Save</button> <a class="btn btn-light" href="roles.php">Cancel</a></div>
</form>
<?php require_once __DIR__ . '/_footer.php'; ?>
