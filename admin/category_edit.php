<?php
require_once __DIR__ . '/../includes/auth.php';
require_permission('moderate_listings');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isNew = ($id === 0);
$pageTitle = $isNew ? 'New category' : 'Edit category';
$errors = [];
$cat = ['name' => '', 'slug' => ''];

if (!$isNew) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE category_id = ?');
    $stmt->execute([$id]);
    $cat = $stmt->fetch() ?: $cat;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? null)) { $errors[] = 'Invalid session token.'; }
    $name = trim($_POST['name'] ?? '');
    $slug = strtolower(trim($_POST['slug'] ?? ''));
    if ($slug === '') {                                  // auto-generate from the name
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    }
    if ($name === '') { $errors[] = 'Category name is required.'; }
    if ($slug === '') { $errors[] = 'Slug could not be generated - enter one.'; }

    if (!$errors) {
        try {
            if ($isNew) {
                db()->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
            } else {
                db()->prepare('UPDATE categories SET name = ?, slug = ? WHERE category_id = ?')->execute([$name, $slug, $id]);
            }
            set_flash('success', $isNew ? 'Category created.' : 'Category updated.');
            redirect('categories.php');
        } catch (PDOException $ex) {
            $errors[] = 'That slug is already in use - choose another.';
        }
    }
    $cat = ['name' => $name, 'slug' => $slug];
}
require_once __DIR__ . '/_header.php';
?>
<h3 class="mb-3"><?= $isNew ? 'Create category' : 'Edit category' ?></h3>
<?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" class="card p-4" style="max-width:560px">
  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
  <div class="mb-3"><label class="form-label">Category name</label>
    <input name="name" class="form-control" value="<?= e($cat['name']) ?>" required>
    <div class="form-text">This is the text shown on the home-page menu button.</div></div>
  <div class="mb-3"><label class="form-label">Slug (optional)</label>
    <input name="slug" class="form-control" value="<?= e($cat['slug']) ?>" placeholder="auto-generated from the name">
    <div class="form-text">Lower-case web-friendly name, e.g. <code>home-garden</code>.</div></div>
  <div><button class="btn btn-primary">Save</button> <a class="btn btn-light" href="categories.php">Cancel</a></div>
</form>
<?php require_once __DIR__ . '/_footer.php'; ?>
