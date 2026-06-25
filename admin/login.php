<?php
require_once __DIR__ . '/../includes/auth.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) {
        $error = 'Invalid session token.';
    } else {
        $user = attempt_login(trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        if ($user && !empty($user['is_staff'])) {
            redirect('index.php');
        }
        $error = 'Invalid staff credentials.';
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin login | Vuka Market</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/admin.css" rel="stylesheet"></head>
<body class="vk-admin d-flex align-items-center" style="min-height:100vh">
<div class="container" style="max-width:380px">
  <div class="card shadow-sm p-4">
    <h4 class="text-center mb-3">Vuka<span class="text-primary">Admin</span></h4>
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="mb-3"><label class="form-label">Email</label><input name="email" type="email" class="form-control" required></div>
      <div class="mb-3"><label class="form-label">Password</label><input name="password" type="password" class="form-control" required></div>
      <button class="btn btn-primary w-100">Sign in</button>
    </form>
  </div>
</div></body></html>
