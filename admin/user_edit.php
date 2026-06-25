<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('manage_users');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isNew = ($id === 0);
$pageTitle = $isNew ? 'New user' : 'Edit user';
$errors = [];

$roles = db()->query('SELECT role_id, role_name FROM roles ORDER BY role_name')->fetchAll();
$user = ['full_name' => '', 'email' => '', 'phone' => '', 'role_id' => '', 'status' => 'active', 'id_verified' => 0];

if (!$isNew) {
    $stmt = db()->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch() ?: $user;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { $errors[] = 'Invalid session token.'; }
    $name   = trim($_POST['full_name'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    $verified = isset($_POST['id_verified']) ? 1 : 0;
    $pass   = $_POST['password'] ?? '';

    if ($name === '')                          { $errors[] = 'Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email required.'; }
    if ($roleId <= 0)                          { $errors[] = 'Select a user type.'; }
    if ($isNew && strlen($pass) < 8)           { $errors[] = 'Password (min 8 chars) required for new users.'; }

    if (!$errors) {
        if ($isNew) {
            $ins = db()->prepare(
              'INSERT INTO users (role_id, full_name, email, phone, password_hash, status, id_verified)
               VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $ins->execute([$roleId, $name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT), $status, $verified]);
            set_flash('success', 'User created.');
        } else {
            if ($pass !== '') {
                $up = db()->prepare('UPDATE users SET role_id=?, full_name=?, email=?, phone=?, status=?, id_verified=?, password_hash=? WHERE user_id=?');
                $up->execute([$roleId, $name, $email, $phone, $status, $verified, password_hash($pass, PASSWORD_DEFAULT), $id]);
            } else {
                $up = db()->prepare('UPDATE users SET role_id=?, full_name=?, email=?, phone=?, status=?, id_verified=? WHERE user_id=?');
                $up->execute([$roleId, $name, $email, $phone, $status, $verified, $id]);
            }
            set_flash('success', 'User updated.');
        }
        redirect('users.php');
    }
    $user = compact('name','email','phone') + ['role_id'=>$roleId,'status'=>$status,'id_verified'=>$verified,'full_name'=>$name];
}

require_once __DIR__ . '/_header.php';
?>
<h3 class="mb-3"><?= $isNew ? 'Create user' : 'Edit user' ?></h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card p-4" style="max-width:640px">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="mb-3"><label class="form-label">Full name</label>
    <input name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Email</label>
    <input name="email" type="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
  <div class="mb-3"><label class="form-label">Phone</label>
    <input name="phone" class="form-control" value="<?= e($user['phone']) ?>"></div>
  <div class="row">
    <div class="col-sm-6 mb-3"><label class="form-label">User type (role)</label>
      <select name="role_id" class="form-select" required>
        <option value="">Choose...</option>
        <?php foreach ($roles as $r): ?>
          <option value="<?= (int)$r['role_id'] ?>" <?= ((int)$user['role_id'] === (int)$r['role_id']) ? 'selected' : '' ?>>
            <?= e($r['role_name']) ?>
          </option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-sm-6 mb-3"><label class="form-label">Status</label>
      <select name="status" class="form-select">
        <?php foreach (['active','pending','suspended'] as $s): ?>
          <option value="<?= $s ?>" <?= $user['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select></div>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" name="id_verified" id="ver" <?= $user['id_verified'] ? 'checked' : '' ?>>
    <label class="form-check-label" for="ver">ID / phone verified</label>
  </div>
  <div class="mb-3"><label class="form-label">Password <?= $isNew ? '' : '(leave blank to keep current)' ?></label>
    <input name="password" type="password" class="form-control" <?= $isNew ? 'required minlength="8"' : '' ?>></div>
  <div><button class="btn btn-primary">Save</button> <a class="btn btn-light" href="users.php">Cancel</a></div>
</form>
<?php require_once __DIR__ . '/_footer.php'; ?>
