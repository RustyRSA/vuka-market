<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Invalid session token.';
    } else {
        $user = attempt_login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        if ($user) {
            redirect(!empty($user['is_staff']) ? '../admin/index.php' : 'dashboard.php');
        }
        $error = 'Incorrect email or password, or the account is not active.';
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-5">
    <h3 class="mb-3">Log in</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="card p-4 shadow-sm">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="mb-3"><label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" required></div>
      <button class="btn btn-success w-100">Log in</button>
      <p class="small text-center mt-3 mb-0">New here? <a href="register.php">Create an account</a></p>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
