<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('moderate_listings');
$pageTitle = 'Listings';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check($_POST['csrf'] ?? null)) {
    $lid = (int)$_POST['id'];
    $new = $_POST['set'] === 'removed' ? 'removed' : 'active';
    db()->prepare('UPDATE listings SET status = ? WHERE listing_id = ?')->execute([$new, $lid]);
    set_flash('success', 'Listing ' . ($new === 'removed' ? 'removed' : 'restored') . '.');
    redirect('listings.php');
}

$listings = db()->query(
  'SELECT l.*, u.full_name AS seller, c.name AS category
   FROM listings l JOIN users u ON u.user_id = l.seller_id
   JOIN categories c ON c.category_id = l.category_id
   ORDER BY l.created_at DESC'
)->fetchAll();

require_once __DIR__ . '/_header.php';
?>
<h3 class="mb-3">Listings moderation</h3>
<div class="card p-0">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr>
        <th>Title</th><th>Seller</th><th>Category</th><th>Price</th><th>Status</th><th class="text-end">Action</th>
      </tr></thead>
      <tbody>
      <?php foreach ($listings as $l): ?>
        <tr>
          <td><?= e($l['title']) ?></td>
          <td class="small"><?= e($l['seller']) ?></td>
          <td><?= e($l['category']) ?></td>
          <td><?= money($l['price']) ?></td>
          <td><span class="badge bg-light text-dark border text-capitalize"><?= e($l['status']) ?></span></td>
          <td class="text-end">
            <form method="post" class="d-inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$l['listing_id'] ?>">
              <?php if ($l['status'] === 'removed'): ?>
                <input type="hidden" name="set" value="active">
                <button class="btn btn-sm btn-outline-success">Restore</button>
              <?php else: ?>
                <input type="hidden" name="set" value="removed">
                <button class="btn btn-sm btn-outline-danger">Remove</button>
              <?php endif; ?>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/_footer.php'; ?>
