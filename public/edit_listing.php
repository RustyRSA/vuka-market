<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pageTitle = 'Edit listing';
$errors = [];
$id  = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['listing_id'] ?? 0);
$uid = current_user()['user_id'];

// Only the seller who owns the listing may edit it
$stmt = db()->prepare('SELECT * FROM listings WHERE listing_id = ? AND seller_id = ?');
$stmt->execute([$id, $uid]);
$l = $stmt->fetch();
if (!$l) { http_response_code(403); exit('You may only edit your own listings.'); }

$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { $errors[] = 'Invalid session token.'; }
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $cat   = (int)($_POST['category_id'] ?? 0);
    $cond  = $_POST['item_condition'] ?? 'good';
    $loc   = trim($_POST['location'] ?? '');
    $status= in_array($_POST['status'] ?? '', ['active','removed'], true) ? $_POST['status'] : $l['status'];

    if ($title === '') { $errors[] = 'Add a title.'; }
    if ($price <= 0)   { $errors[] = 'Enter a valid price.'; }
    if ($cat <= 0)     { $errors[] = 'Choose a category.'; }

    if (!$errors) {
        $up = db()->prepare(
            'UPDATE listings SET title = ?, description = ?, price = ?, category_id = ?,
                    item_condition = ?, location = ?, status = ?
             WHERE listing_id = ? AND seller_id = ?'
        );
        $up->execute([$title, $desc, $price, $cat, $cond, $loc, $status, $id, $uid]);

        if (!empty($_FILES['photo'])) {
            save_listing_image($id, $_FILES['photo'], true);   // replaces the primary photo
        }
        set_flash('success', 'Listing updated.');
        redirect('listing.php?id=' . $id);
    }
    $l = array_merge($l, ['title'=>$title,'description'=>$desc,'price'=>$price,
                          'category_id'=>$cat,'item_condition'=>$cond,'location'=>$loc,'status'=>$status]);
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <h3 class="mb-3">Edit your listing</h3>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
      <div class="mb-3"><label class="form-label">Title</label>
        <input name="title" class="form-control" value="<?= e($l['title']) ?>" required></div>
      <div class="row">
        <div class="col-sm-6 mb-3"><label class="form-label">Price (R)</label>
          <input name="price" type="number" step="0.01" min="0" class="form-control" value="<?= e((string)$l['price']) ?>" required></div>
        <div class="col-sm-6 mb-3"><label class="form-label">Category</label>
          <select name="category_id" class="form-select" required>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['category_id'] ?>" <?= (int)$l['category_id'] === (int)$c['category_id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select></div>
      </div>
      <div class="row">
        <div class="col-sm-4 mb-3"><label class="form-label">Condition</label>
          <select name="item_condition" class="form-select">
            <?php foreach (['new'=>'New','like_new'=>'Like new','good'=>'Good','fair'=>'Fair'] as $k=>$v): ?>
              <option value="<?= $k ?>" <?= $l['item_condition'] === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="col-sm-4 mb-3"><label class="form-label">Location</label>
          <input name="location" class="form-control" value="<?= e($l['location']) ?>"></div>
        <div class="col-sm-4 mb-3"><label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?= $l['status'] === 'active' ? 'selected' : '' ?>>Active (visible)</option>
            <option value="removed" <?= $l['status'] === 'removed' ? 'selected' : '' ?>>Removed (hidden)</option>
          </select></div>
      </div>
      <div class="mb-3"><label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4"><?= e($l['description']) ?></textarea></div>
      <div class="mb-3"><label class="form-label">Replace photo (optional)</label>
        <input type="file" name="photo" class="form-control" accept="image/*"></div>
      <div><button class="btn btn-success">Save changes</button>
           <a class="btn btn-light" href="dashboard.php">Cancel</a></div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
