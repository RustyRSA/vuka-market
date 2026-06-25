<?php
require_once __DIR__ . '/../includes/auth.php';
$pageTitle = 'Create your account';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { $errors[] = 'Invalid session token. Please try again.'; }
    $name  = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($name === '')                          { $errors[] = 'Please enter your full name.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Please enter a valid email address.'; }
    if (strlen($pass) < 8)                     { $errors[] = 'Password must be at least 8 characters.'; }

    if (!$errors) {
        $exists = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            $errors[] = 'That email is already registered.';
        } else {
            // New sign-ups receive the "Member" role
            $roleId = (int) db()->query('SELECT role_id FROM roles WHERE role_name = "Member"')->fetchColumn();
            $ins = db()->prepare(
                'INSERT INTO users (role_id, full_name, email, phone, password_hash, status)
                 VALUES (?, ?, ?, ?, ?, "active")'
            );
            $ins->execute([$roleId, $name, $email, $phone, password_hash($pass, PASSWORD_DEFAULT)]);
            set_flash('success', 'Welcome to Vuka Market! You can now log in.');
            redirect('login.php');
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6 col-lg-5">
    <h3 class="mb-3">Create your account</h3>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="card p-4 shadow-sm">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="mb-3"><label class="form-label">Full name</label>
        <input name="full_name" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Email</label>
        <input name="email" type="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Mobile number</label>
        <input name="phone" class="form-control" placeholder="0xx xxx xxxx"></div>
      <div class="mb-3"><label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" minlength="8" required></div>
      <button class="btn btn-success w-100">Sign up</button>
      <p class="small text-center mt-3 mb-0">Already a member? <a href="login.php">Log in</a></p>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
