<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
$me = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' : '' ?>Vuka Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body class="vk-admin">
<nav class="navbar navbar-dark vk-admin-top px-3">
  <span class="navbar-brand mb-0">Vuka<span class="text-warning">Admin</span></span>
  <button class="btn btn-sm btn-outline-light d-lg-none" data-bs-toggle="collapse" data-bs-target="#side">Menu</button>
  <span class="navbar-text text-light ms-auto small">
    <?= e($me['full_name']) ?> &middot; <span class="badge bg-warning text-dark"><?= e($me['role_name']) ?></span>
    &middot; <a class="text-light" href="../public/logout.php">Logout</a>
  </span>
</nav>
<div class="d-flex">
  <aside class="vk-side collapse d-lg-block" id="side">
    <ul class="nav flex-column p-2">
      <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
      <?php if (has_permission('manage_users')): ?>
        <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
      <?php endif; ?>
      <?php if (has_permission('manage_roles')): ?>
        <li class="nav-item"><a class="nav-link" href="roles.php">User Types (Roles)</a></li>
      <?php endif; ?>
      <?php if (has_permission('moderate_listings')): ?>
        <li class="nav-item"><a class="nav-link" href="listings.php">Listings</a></li>
      <?php endif; ?>
    </ul>
  </aside>
  <main class="vk-admin-main flex-grow-1 p-4">
  <?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
  <?php endforeach; ?>
