<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('moderate_listings');
$pageTitle = 'Categories (Menu)';

$cats = db()->query(
  'SELECT c.*, (SELECT COUNT(*) FROM listings l WHERE l.category_id = c.category_id) AS listing_count
   FROM categories c ORDER BY c.name'
)->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h3 class="mb-0">Categories (site menu)</h3>
  <a class="btn btn-primary" href="category_edit.php">+ New category</a>
</div>
<p class="text-muted small">These categories are the filter buttons shown on the main website's home page.</p>
<div class="card p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr>
        <th>Name</th><th>Slug</th><th>Listings</th><th class="text-end">Actions</th>
      </tr></thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td class="fw-semibold"><?= e($c['name']) ?></td>
          <td class="small text-muted"><?= e($c['slug']) ?></td>
          <td><?= (int)$c['listing_count'] ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="category_edit.php?id=<?= (int)$c['category_id'] ?>">Edit</a>
            <form method="post" action="category_delete.php" class="d-inline" onsubmit="return confirm('Delete this category?');">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$c['category_id'] ?>">
              <button class="btn btn-sm btn-outline-danger" <?= $c['listing_count'] > 0 ? 'disabled title="In use"' : '' ?>>Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
