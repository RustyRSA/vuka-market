<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pageTitle = 'Sell an item';
$errors = [];
$categories = db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { $errors[] = 'Invalid session token.'; }
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $cat   = (int)($_POST['category_id'] ?? 0);
    $cond  = $_POST['item_condition'] ?? 'good';
    $loc   = trim($_POST['location'] ?? '');

    if ($title === '')  { $errors[] = 'Add a title.'; }
    if ($price <= 0)    { $errors[] = 'Enter a valid price.'; }
    if ($cat <= 0)      { $errors[] = 'Choose a category.'; }

    if (!$errors) {
        $ins = db()->prepare(
            'INSERT INTO listings (seller_id, category_id, title, description, price, item_condition, location, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, "active")'
        );
        $ins->execute([current_user()['user_id'], $cat, $title, $desc, $price, $cond, $loc]);
        $newId = (int) db()->lastInsertId();

        // Store the uploaded photo (if any) against the new listing
        if (!empty($_FILES['photo'])) {
            save_listing_image($newId, $_FILES['photo'], true);
        }

        set_flash('success', 'Your listing is now live!');
        redirect('listing.php?id=' . $newId);
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-lg-7">
    <h3 class="mb-3">List an item for sale</h3>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" enctype="multipart/form-data" class="card p-4 shadow-sm">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <div class="mb-3"><label class="form-label">Title</label>
        <input name="title" class="form-control" placeholder="e.g. Samsung A14 in good condition" required></div>
      <div class="row">
        <div class="col-sm-6 mb-3"><label class="form-label">Price (R)</label>
          <input name="price" type="number" step="0.01" min="0" class="form-control" required></div>
        <div class="col-sm-6 mb-3"><label class="form-label">Category</label>
          <select name="category_id" class="form-select" required>
            <option value="">Choose...</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int)$c['category_id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach; ?>
          </select></div>
      </div>
      <div class="row">
        <div class="col-sm-6 mb-3"><label class="form-label">Condition</label>
          <select name="item_condition" class="form-select">
            <option value="new">New</option><option value="like_new">Like new</option>
            <option value="good" selected>Good</option><option value="fair">Fair</option>
          </select></div>
        <div class="col-sm-6 mb-3"><label class="form-label">Location</label>
          <input name="location" class="form-control" placeholder="e.g. Soweto, Gauteng"></div>
      </div>
      <div class="mb-3"><label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4"></textarea></div>
      <div class="mb-3"><label class="form-label">Photo</label>
        <input type="file" name="photo" class="form-control" accept="image/*"></div>
      <button class="btn btn-success">Publish listing</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
