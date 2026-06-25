<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_users');
$pageTitle = 'Users';

$users = db()->query(
  'SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id = u.role_id ORDER BY u.created_at DESC'
)->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Users</h3>
  <a class="btn btn-primary" href="user_edit.php">+ New user</a>
</div>
<div class="card p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr>
        <th>Name</th><th>Email</th><th>User type</th><th>Status</th><th>Verified</th><th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['full_name']) ?></td>
          <td class="small"><?= e($u['email']) ?></td>
          <td><span class="badge bg-secondary"><?= e($u['role_name']) ?></span></td>
          <td class="text-capitalize"><?= e($u['status']) ?></td>
          <td><?= $u['id_verified'] ? '<span class="text-success">Yes</span>' : '<span class="text-muted">No</span>' ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="user_edit.php?id=<?= (int)$u['user_id'] ?>">Edit</a>
            <form method="post" action="user_delete.php" class="d-inline" onsubmit="return confirm('Delete this user?');">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$u['user_id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
