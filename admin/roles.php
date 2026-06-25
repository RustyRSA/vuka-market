<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_roles');
$pageTitle = 'User Types (Roles)';

$roles = db()->query(
  'SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.role_id) AS user_count
   FROM roles r ORDER BY r.role_id'
)->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">User Types (Roles)</h3>
  <a class="btn btn-primary" href="role_edit.php">+ New role</a>
</div>
<div class="card p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr>
        <th>Role</th><th>Description</th><th>Admin access</th><th>Users</th><th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($roles as $r): ?>
        <tr>
          <td class="fw-semibold"><?= e($r['role_name']) ?></td>
          <td class="small text-muted"><?= e($r['description']) ?></td>
          <td><?= $r['is_staff'] ? '<span class="text-success">Yes</span>' : 'No' ?></td>
          <td><?= (int)$r['user_count'] ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="role_edit.php?id=<?= (int)$r['role_id'] ?>">Edit</a>
            <form method="post" action="role_delete.php" class="d-inline" onsubmit="return confirm('Delete this role?');">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$r['role_id'] ?>">
              <button class="btn btn-sm btn-outline-danger" <?= $r['user_count'] > 0 ? 'disabled title="In use"' : '' ?>>Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
